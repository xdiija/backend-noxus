<?php

namespace App\Services;

use App\DTOs\Surgery\SurgeryDTO;
use App\Models\Animal;
use App\Models\Surgery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SurgeryService
{
    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)->surgeries()->latest('surgery_date')->latest('id')->get();
    }

    public function create(string $animalId, SurgeryDTO $dto): Surgery
    {
        $animal = $this->animal($animalId);

        return DB::transaction(function () use ($animal, $dto) {
            $surgery = $animal->surgeries()->create(
                $dto->toArray() + ['organization_id' => $animal->organization_id]
            );

            $this->syncNeutered($animal, $dto);

            return $surgery;
        });
    }

    public function update(string $animalId, string $id, SurgeryDTO $dto): Surgery
    {
        $animal = $this->animal($animalId);

        return DB::transaction(function () use ($animal, $id, $dto) {
            $surgery = $animal->surgeries()->findOrFail($id);
            $surgery->update($dto->toArray());

            $this->syncNeutered($animal, $dto);

            return $surgery;
        });
    }

    public function delete(string $animalId, string $id): void
    {
        $surgery = $this->animal($animalId)->surgeries()->findOrFail($id);
        $surgery->delete();
    }

    /**
     * Invariant (health.md): the surgery is the clinical record of a
     * castration; animals.neutered/neuter_date are the queryable summary —
     * keep them consistent. Never un-neuters: the flag only drives the sync
     * when a castration is being recorded.
     */
    private function syncNeutered(Animal $animal, SurgeryDTO $dto): void
    {
        if ($dto->is_castration) {
            $animal->update([
                'neutered' => true,
                'neuter_date' => $dto->surgery_date,
            ]);
        }
    }

    /**
     * Resolve the parent animal scoped to the authenticated user's org, so a
     * tenant can only ever reach surgeries of its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
