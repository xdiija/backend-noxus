<?php

namespace App\Services;

use App\DTOs\Kennel\KennelDTO;
use App\Models\Kennel;
use App\Models\KennelStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class KennelService
{
    private const WITH = ['status', 'size'];

    public function list(int $perPage = 10, string $filter = '', ?int $statusId = null): LengthAwarePaginator
    {
        $query = $this->scoped()->with(self::WITH)->withCount('openStays');

        if (! empty($filter)) {
            $query->where('name', 'like', "%{$filter}%");
        }

        $query->when($statusId, fn (Builder $q) => $q->where('status_id', $statusId));

        return $query->orderBy('name')->paginate($perPage);
    }

    public function find(string $id): Kennel
    {
        return $this->scoped()->with(self::WITH)->withCount('openStays')->findOrFail($id);
    }

    public function create(KennelDTO $dto): Kennel
    {
        // organization_id comes from the authenticated user, never from input.
        $kennel = Kennel::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
            'status_id' => $dto->status_id ?? $this->defaultStatusId(),
        ]);

        return $kennel->load(self::WITH)->loadCount('openStays');
    }

    public function update(string $id, KennelDTO $dto): Kennel
    {
        $kennel = $this->scoped()->findOrFail($id);

        $kennel->update($dto->toArray() + [
            'status_id' => $dto->status_id ?? $kennel->status_id,
        ]);

        return $kennel->load(self::WITH)->loadCount('openStays');
    }

    /**
     * A kennel that still houses animals (open stays) can't be removed —
     * close or transfer the stays first (kennels.md).
     */
    public function delete(string $id): void
    {
        $kennel = $this->scoped()->findOrFail($id);

        if ($kennel->openStays()->exists()) {
            throw ValidationException::withMessages([
                'id' => 'Não é possível excluir um canil com animais alocados.',
            ]);
        }

        $kennel->delete();
    }

    /**
     * The is_default row among the org's readable statuses ("Disponível" in the
     * seeded defaults) — used when a kennel is created without an explicit
     * status.
     */
    private function defaultStatusId(): int
    {
        return (int) KennelStatus::query()
            ->where(fn (Builder $q) => $q->whereNull('organization_id')->orWhere('organization_id', $this->organizationId()))
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->value('id');
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's kennels.
     */
    private function scoped(): Builder
    {
        return Kennel::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
