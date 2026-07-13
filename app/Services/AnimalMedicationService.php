<?php

namespace App\Services;

use App\DTOs\AnimalMedication\AnimalMedicationDTO;
use App\Models\Animal;
use App\Models\AnimalMedication;
use Illuminate\Database\Eloquent\Collection;

class AnimalMedicationService
{
    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)->medications()->with('medication')->latest('start_date')->latest('id')->get();
    }

    public function create(string $animalId, AnimalMedicationDTO $dto): AnimalMedication
    {
        $animal = $this->animal($animalId);

        return $animal->medications()->create($dto->toArray())->load('medication');
    }

    public function update(string $animalId, string $id, AnimalMedicationDTO $dto): AnimalMedication
    {
        $course = $this->animal($animalId)->medications()->findOrFail($id);
        $course->update($dto->toArray());

        return $course->load('medication');
    }

    public function delete(string $animalId, string $id): void
    {
        $course = $this->animal($animalId)->medications()->findOrFail($id);
        $course->delete();
    }

    /**
     * Resolve the parent animal scoped to the authenticated user's org, so a
     * tenant can only ever reach medication courses of its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
