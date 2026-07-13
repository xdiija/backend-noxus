<?php

namespace App\Services;

use App\DTOs\HealthRecord\HealthRecordDTO;
use App\Models\Animal;
use App\Models\HealthRecord;
use Illuminate\Database\Eloquent\Collection;

class HealthRecordService
{
    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)->healthRecords()->latest('event_date')->latest('id')->get();
    }

    public function create(string $animalId, HealthRecordDTO $dto): HealthRecord
    {
        $animal = $this->animal($animalId);

        return $animal->healthRecords()->create(
            $dto->toArray() + ['organization_id' => $animal->organization_id]
        );
    }

    public function update(string $animalId, string $id, HealthRecordDTO $dto): HealthRecord
    {
        $record = $this->animal($animalId)->healthRecords()->findOrFail($id);
        $record->update($dto->toArray());

        return $record;
    }

    public function delete(string $animalId, string $id): void
    {
        $record = $this->animal($animalId)->healthRecords()->findOrFail($id);
        $record->delete();
    }

    /**
     * Resolve the parent animal scoped to the authenticated user's org, so a
     * tenant can only ever reach health records of its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
