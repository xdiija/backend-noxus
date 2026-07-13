<?php

namespace App\Services;

use App\DTOs\FosterPlacement\FosterPlacementDTO;
use App\Models\AnimalFosterHome;
use App\Models\AnimalStatus;
use App\Models\FosterHome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FosterPlacementService
{
    /**
     * Animal statuses (animal_statuses lookup) the fostering workflow keeps in
     * sync.
     */
    private const ANIMAL_FOSTERED = 'Em Lar Temporário';

    private const ANIMAL_AVAILABLE = 'Disponível';

    public function __construct(
        protected AnimalService $animalService
    ) {}

    public function list(string $fosterHomeId): Collection
    {
        return $this->fosterHome($fosterHomeId)
            ->placements()
            ->with('animal')
            ->latest('entry_date')
            ->latest('id')
            ->get();
    }

    /**
     * Open a stay: create the placement and move the animal to
     * "Em Lar Temporário". Enforces the fostering invariants (fostering.md):
     * at most one open stay per animal, and the home's `capacity` blocks
     * over-capacity placements.
     */
    public function create(string $fosterHomeId, FosterPlacementDTO $dto): AnimalFosterHome
    {
        return DB::transaction(function () use ($fosterHomeId, $dto) {
            $fosterHome = $this->fosterHome($fosterHomeId);

            $this->assertNoOpenStay($dto->animal_id);
            $this->assertCapacity($fosterHome);

            $placement = $fosterHome->placements()->create($dto->toArray());

            // Keep the animal's status in sync (+ animal_status_history row).
            $this->syncAnimalStatus($dto->animal_id, self::ANIMAL_FOSTERED, 'Acolhido em lar temporário');

            return $placement->load('animal');
        });
    }

    /**
     * Correct a stay's dates. The animal is immutable after creation — a wrong
     * animal means deleting the placement and opening a new one.
     */
    public function update(string $fosterHomeId, string $id, FosterPlacementDTO $dto): AnimalFosterHome
    {
        $placement = $this->fosterHome($fosterHomeId)->placements()->findOrFail($id);

        if ($placement->exit_date !== null && $dto->entry_date > $placement->exit_date->toDateString()) {
            throw ValidationException::withMessages([
                'entry_date' => 'A data de entrada não pode ser posterior à data de saída.',
            ]);
        }

        $placement->update(['entry_date' => $dto->entry_date]);

        return $placement->load('animal');
    }

    /**
     * Close a stay: set the exit_date and, when the animal is still marked
     * "Em Lar Temporário", move it back to "Disponível". The guard keeps a
     * close from clobbering a status set meanwhile by another workflow (e.g.
     * adopted straight from the foster home).
     */
    public function close(string $fosterHomeId, string $id, string $exitDate): AnimalFosterHome
    {
        return DB::transaction(function () use ($fosterHomeId, $id, $exitDate) {
            $placement = $this->fosterHome($fosterHomeId)->placements()->findOrFail($id);

            if ($placement->exit_date !== null) {
                throw ValidationException::withMessages([
                    'exit_date' => 'Esta estadia já foi encerrada.',
                ]);
            }

            if ($exitDate < $placement->entry_date->toDateString()) {
                throw ValidationException::withMessages([
                    'exit_date' => 'A data de saída não pode ser anterior à data de entrada.',
                ]);
            }

            $placement->update(['exit_date' => $exitDate]);

            if ($this->animalStatusIs($placement->animal_id, self::ANIMAL_FOSTERED)) {
                $this->syncAnimalStatus($placement->animal_id, self::ANIMAL_AVAILABLE, 'Saída do lar temporário');
            }

            return $placement->load('animal');
        });
    }

    public function delete(string $fosterHomeId, string $id): void
    {
        $placement = $this->fosterHome($fosterHomeId)->placements()->findOrFail($id);
        $placement->delete();
    }

    /**
     * At most one open foster stay per animal, org-wide (fostering.md,
     * application-enforced). The animal is already validated as belonging to
     * the org, so scoping by animal_id is safe.
     */
    private function assertNoOpenStay(int $animalId): void
    {
        $hasOpen = AnimalFosterHome::query()
            ->where('animal_id', $animalId)
            ->whereNull('exit_date')
            ->exists();

        if ($hasOpen) {
            throw ValidationException::withMessages([
                'animal_id' => 'Este animal já possui um lar temporário ativo.',
            ]);
        }
    }

    /**
     * `capacity` is advisory in the DB (fostering.md); block over-capacity
     * placements here. Null capacity = unlimited.
     */
    private function assertCapacity(FosterHome $fosterHome): void
    {
        if ($fosterHome->capacity === null) {
            return;
        }

        if ($fosterHome->openPlacements()->count() >= $fosterHome->capacity) {
            throw ValidationException::withMessages([
                'foster_home_id' => 'Este lar temporário já atingiu sua capacidade máxima.',
            ]);
        }
    }

    /**
     * Move the animal to the named status (+ history) via AnimalService, which
     * is org-scoped and writes the animal_status_history row. No-op if the
     * target status doesn't exist for this org (defensive — seeded defaults
     * always do).
     */
    private function syncAnimalStatus(int $animalId, string $statusName, ?string $notes): void
    {
        $statusId = $this->animalStatusId($statusName);

        if ($statusId !== null) {
            $this->animalService->changeStatus((string) $animalId, $statusId, $notes);
        }
    }

    private function animalStatusIs(int $animalId, string $statusName): bool
    {
        $statusId = $this->animalStatusId($statusName);

        return $statusId !== null
            && (int) DB::table('animals')->where('id', $animalId)->value('status_id') === $statusId;
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
     * Resolve the parent foster home scoped to the authenticated user's org, so
     * a tenant can only ever reach placements on its own foster homes.
     */
    private function fosterHome(string $fosterHomeId): FosterHome
    {
        return FosterHome::query()
            ->where('organization_id', $this->organizationId())
            ->findOrFail($fosterHomeId);
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
