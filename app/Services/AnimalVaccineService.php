<?php

namespace App\Services;

use App\DTOs\AnimalVaccine\AnimalVaccineDTO;
use App\Models\Animal;
use App\Models\AnimalVaccine;
use Illuminate\Database\Eloquent\Collection;

class AnimalVaccineService
{
    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)->vaccines()->with('vaccine')->latest('application_date')->latest('id')->get();
    }

    public function create(string $animalId, AnimalVaccineDTO $dto): AnimalVaccine
    {
        $animal = $this->animal($animalId);

        return $animal->vaccines()->create($dto->toArray())->load('vaccine');
    }

    public function update(string $animalId, string $id, AnimalVaccineDTO $dto): AnimalVaccine
    {
        $dose = $this->animal($animalId)->vaccines()->findOrFail($id);
        $dose->update($dto->toArray());

        return $dose->load('vaccine');
    }

    public function delete(string $animalId, string $id): void
    {
        $dose = $this->animal($animalId)->vaccines()->findOrFail($id);
        $dose->delete();
    }

    /**
     * Resolve the parent animal scoped to the authenticated user's org, so a
     * tenant can only ever reach vaccine doses of its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
