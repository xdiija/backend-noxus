<?php

namespace App\Services;

use App\DTOs\Adoption\AdoptionDTO;
use App\Models\Adoption;
use App\Models\AdoptionStatus;
use App\Models\AnimalStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdoptionService
{
    private const WITH = ['animal', 'adopter', 'status'];

    /**
     * Adoption lifecycle statuses (adoption_statuses lookup, ADR-0002).
     */
    private const STATUS_PENDING = 'Pendente';

    private const STATUS_COMPLETED = 'Concluída';

    private const STATUS_RETURNED = 'Devolvida';

    private const STATUS_CANCELLED = 'Cancelada';

    /** Statuses that make an adoption "active" (blocks a second one per animal). */
    private const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_COMPLETED];

    /**
     * Animal statuses (animal_statuses lookup) the workflow keeps in sync.
     */
    private const ANIMAL_ADOPTED = 'Adotado';

    private const ANIMAL_RETURNED = 'Devolvido';

    private const ANIMAL_AVAILABLE = 'Disponível';

    public function __construct(
        protected AnimalService $animalService
    ) {}

    public function list(int $perPage = 10, string $filter = '', ?int $statusId = null, ?int $animalId = null): LengthAwarePaginator
    {
        $query = $this->scoped()->with(self::WITH);

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->whereHas('animal', fn (Builder $a) => $a->where('name', 'like', "%{$filter}%"))
                    ->orWhereHas('adopter', fn (Builder $a) => $a->where('name', 'like', "%{$filter}%"));
            });
        }

        $query->when($statusId, fn (Builder $q) => $q->where('status_id', $statusId))
            ->when($animalId, fn (Builder $q) => $q->where('animal_id', $animalId));

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Slim {id, name} rows for host-gated select fields (document owner
     * search). Adoptions have no name of their own, so the label mirrors the
     * "#id — animal / adopter" format the documents dialog shows.
     */
    public function options(string $filter = '', int $limit = 20): Collection
    {
        return $this->scoped()
            ->with(['animal:id,name', 'adopter:id,name'])
            ->when($filter !== '', fn (Builder $q) => $q->where(function (Builder $sub) use ($filter) {
                $sub->whereHas('animal', fn (Builder $a) => $a->where('name', 'like', "%{$filter}%"))
                    ->orWhereHas('adopter', fn (Builder $a) => $a->where('name', 'like', "%{$filter}%"));
            }))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => "#{$row->id} — ".($row->animal?->name ?? 'animal').' / '.($row->adopter?->name ?? 'adotante'),
            ]);
    }

    public function find(string $id): Adoption
    {
        return $this->scoped()->with(self::WITH)->with('followUps.status')->findOrFail($id);
    }

    /**
     * Adopt: create an adoption row and move the animal to "Adotado".
     * Enforces the "one active adoption per animal" rule (ADR-0002).
     */
    public function create(AdoptionDTO $dto): Adoption
    {
        return DB::transaction(function () use ($dto) {
            $this->assertNoActiveAdoption($dto->animal_id);

            $statusId = $dto->status_id ?? $this->defaultAdoptionStatusId();

            $adoption = Adoption::create($dto->toArray() + [
                'organization_id' => $this->organizationId(),
                'status_id' => $statusId,
            ]);

            // Keep the animal's status in sync (+ animal_status_history row).
            $this->syncAnimalStatus($adoption->animal_id, self::ANIMAL_ADOPTED, 'Adoção registrada');

            return $adoption->load(self::WITH);
        });
    }

    /**
     * Edit adoption details (adopter, date, agreement, notes). Status transitions
     * go through return()/cancel(); animal_id is immutable after creation.
     */
    public function update(string $id, AdoptionDTO $dto): Adoption
    {
        $adoption = $this->scoped()->findOrFail($id);

        $adoption->update([
            'adopter_id' => $dto->adopter_id,
            'adoption_date' => $dto->adoption_date,
            'agreement_signed' => $dto->agreement_signed,
            'notes' => $dto->notes,
        ]);

        return $adoption->load(self::WITH);
    }

    /**
     * Return: mark the adoption Devolvida with a date/reason and move the animal
     * back to "Devolvido".
     */
    public function return(string $id, string $returnDate, ?string $returnReason = null): Adoption
    {
        return DB::transaction(function () use ($id, $returnDate, $returnReason) {
            $adoption = $this->scoped()->findOrFail($id);

            $adoption->update([
                'status_id' => $this->adoptionStatusId(self::STATUS_RETURNED),
                'return_date' => $returnDate,
                'return_reason' => $returnReason,
            ]);

            $this->syncAnimalStatus($adoption->animal_id, self::ANIMAL_RETURNED, 'Animal devolvido'.($returnReason ? ": {$returnReason}" : ''));

            return $adoption->load(self::WITH);
        });
    }

    /**
     * Cancel: mark the adoption Cancelada and make the animal available again.
     */
    public function cancel(string $id, ?string $notes = null): Adoption
    {
        return DB::transaction(function () use ($id, $notes) {
            $adoption = $this->scoped()->findOrFail($id);

            $adoption->update([
                'status_id' => $this->adoptionStatusId(self::STATUS_CANCELLED),
                'notes' => $notes ?? $adoption->notes,
            ]);

            $this->syncAnimalStatus($adoption->animal_id, self::ANIMAL_AVAILABLE, 'Adoção cancelada');

            return $adoption->load(self::WITH);
        });
    }

    public function delete(string $id): void
    {
        $adoption = $this->scoped()->findOrFail($id);
        $adoption->delete();
    }

    /**
     * The "one active adoption per animal" invariant is application-enforced
     * (ADR-0002): reject a new adoption while an active one exists.
     */
    private function assertNoActiveAdoption(int $animalId): void
    {
        $activeIds = AdoptionStatus::query()
            ->whereIn('name', self::ACTIVE_STATUSES)
            ->pluck('id');

        $hasActive = $this->scoped()
            ->where('animal_id', $animalId)
            ->whereNull('return_date')
            ->whereIn('status_id', $activeIds)
            ->exists();

        if ($hasActive) {
            throw ValidationException::withMessages([
                'animal_id' => 'Este animal já possui uma adoção ativa.',
            ]);
        }
    }

    /**
     * Move the animal to the named status (+ history) via AnimalService, which is
     * org-scoped and writes the animal_status_history row. No-op if the target
     * status doesn't exist for this org (defensive — seeded defaults always do).
     */
    private function syncAnimalStatus(int $animalId, string $statusName, ?string $notes): void
    {
        $statusId = $this->animalStatusId($statusName);

        if ($statusId !== null) {
            $this->animalService->changeStatus((string) $animalId, $statusId, $notes);
        }
    }

    private function defaultAdoptionStatusId(): int
    {
        $default = AdoptionStatus::query()
            ->where(fn (Builder $q) => $q->whereNull('organization_id')->orWhere('organization_id', $this->organizationId()))
            ->where('is_default', true)
            ->value('id');

        return (int) ($default ?? $this->adoptionStatusId(self::STATUS_PENDING));
    }

    private function adoptionStatusId(string $name): int
    {
        return (int) AdoptionStatus::query()
            ->where(fn (Builder $q) => $q->whereNull('organization_id')->orWhere('organization_id', $this->organizationId()))
            ->where('name', $name)
            ->value('id');
    }

    private function animalStatusId(string $name): ?int
    {
        $id = AnimalStatus::query()
            ->where(fn (Builder $q) => $q->whereNull('organization_id')->orWhere('organization_id', $this->organizationId()))
            ->where('name', $name)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's adoptions.
     */
    private function scoped(): Builder
    {
        return Adoption::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
