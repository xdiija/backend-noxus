<?php

namespace App\Services;

use App\DTOs\Animal\AnimalDTO;
use App\Models\Animal;
use App\Models\AnimalStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnimalService
{
    /**
     * Relationships eager-loaded for every read so the Resource can nest the
     * lookups without N+1 queries.
     */
    private const WITH = ['species', 'breed', 'size', 'status', 'rescue', 'tags'];

    public function list(
        int $perPage = 10,
        string $filter = '',
        ?int $statusId = null,
        ?int $speciesId = null,
        ?string $sex = null,
        ?int $sizeId = null,
    ): LengthAwarePaginator {
        $query = $this->scoped()->with(self::WITH);

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('microchip', 'like', "%{$filter}%")
                    ->orWhere('color', 'like', "%{$filter}%");
            });
        }

        // All filters apply simultaneously (AND'ed): a null value is simply
        // skipped, so "todos"/"todas" on the frontend means "no constraint".
        $query->when($statusId, fn (Builder $q) => $q->where('status_id', $statusId))
            ->when($speciesId, fn (Builder $q) => $q->where('species_id', $speciesId))
            ->when($sex, fn (Builder $q) => $q->where('sex', $sex))
            ->when($sizeId, fn (Builder $q) => $q->where('size_id', $sizeId));

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Slim {id, name} rows for host-gated select fields (adoption, kennel and
     * foster forms), so consumers don't need this resource's own menu grant.
     */
    public function options(string $filter = '', int $limit = 20): Collection
    {
        return $this->scoped()
            ->when($filter !== '', fn (Builder $q) => $q->where('name', 'like', "%{$filter}%"))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name]);
    }

    public function find(string $id): Animal
    {
        // `images` is loaded only here (not in list): each image resolves to a
        // presigned URL, so we generate them for a single animal, not a page.
        // The open stays give the Resource the animal's current location
        // (kennel or foster home).
        return $this->scoped()->with(self::WITH)
            ->with(['images', 'openKennelStay.kennel', 'openFosterStay.fosterHome'])
            ->findOrFail($id);
    }

    public function create(AnimalDTO $dto): Animal
    {
        return DB::transaction(function () use ($dto) {
            $orgId = $this->organizationId();

            // organization_id comes from the authenticated user, never from input.
            $animal = Animal::create($dto->toArray() + ['organization_id' => $orgId]);

            // Seed the status log with the initial status (old = null, ADR + spec).
            $this->recordStatusChange($animal, null, $animal->status_id, null);

            if ($dto->tags !== null) {
                $animal->tags()->sync($dto->tags);
            }

            if ($dto->rescue !== null) {
                $animal->rescue()->create($dto->rescue + ['organization_id' => $orgId]);
            }

            return $animal->load(self::WITH);
        });
    }

    public function update(string $id, AnimalDTO $dto): Animal
    {
        return DB::transaction(function () use ($id, $dto) {
            $animal = $this->scoped()->findOrFail($id);

            $previousStatusId = $animal->status_id;

            $animal->update($dto->toArray());

            // Invariant: write a history row whenever status_id changes.
            if ($animal->status_id !== $previousStatusId) {
                $this->recordStatusChange($animal, $previousStatusId, $animal->status_id, null);
                $this->closeKennelStayIfOffSite($animal);
            }

            if ($dto->tags !== null) {
                $animal->tags()->sync($dto->tags);
            }

            if ($dto->rescue !== null) {
                // 1:1 intake (ADR-0003): create it once, then keep it in sync.
                $animal->rescue()->updateOrCreate(
                    ['animal_id' => $animal->id],
                    $dto->rescue + ['organization_id' => $animal->organization_id],
                );
            }

            return $animal->load(self::WITH);
        });
    }

    /**
     * Dedicated lifecycle transition: move the animal to a new status and log
     * it (with an optional reason). No-op history is avoided when unchanged.
     */
    public function changeStatus(string $id, int $statusId, ?string $notes = null): Animal
    {
        return DB::transaction(function () use ($id, $statusId, $notes) {
            $animal = $this->scoped()->findOrFail($id);

            if ($animal->status_id !== $statusId) {
                $previousStatusId = $animal->status_id;
                $animal->update(['status_id' => $statusId]);
                $this->recordStatusChange($animal, $previousStatusId, $statusId, $notes);
                $this->closeKennelStayIfOffSite($animal);
            }

            return $animal->load(self::WITH);
        });
    }

    /**
     * Animal deletes are soft, so the FK cascades never fire — cascade the
     * soft delete to the children that surface outside the animal's own
     * routes (rescues feed the dashboard trends; appointments feed the
     * agenda) and to the rest of the soft-deletable clinical history for
     * consistency. Vaccine doses and medication courses have no deleted_at
     * and only render nested in the animal, so they are left untouched.
     */
    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $animal = $this->scoped()->findOrFail($id);

            $animal->rescue()->delete();
            $animal->healthRecords()->delete();
            $animal->appointments()->delete();
            $animal->surgeries()->delete();

            $animal->delete();
        });
    }

    /**
     * Kennel invariant (kennels.md): moving to a status that means the animal
     * left the shelter (adopted, fostered, deceased) automatically closes its
     * open kennel stay. Called from every status-changing path so adoption,
     * fostering and manual edits all free the kennel; the status-history row
     * written alongside documents why.
     */
    private function closeKennelStayIfOffSite(Animal $animal): void
    {
        $statusName = AnimalStatus::query()->whereKey($animal->status_id)->value('name');

        if (in_array($statusName, KennelStayService::OFF_SITE_ANIMAL_STATUSES, true)) {
            $animal->openKennelStay()->update(['exit_date' => now()->toDateString()]);
        }
    }

    private function recordStatusChange(Animal $animal, ?int $oldStatusId, int $newStatusId, ?string $notes): void
    {
        $animal->statusHistory()->create([
            'old_status_id' => $oldStatusId,
            'new_status_id' => $newStatusId,
            'changed_by' => auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's animals.
     */
    private function scoped(): Builder
    {
        return Animal::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
