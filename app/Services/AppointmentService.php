<?php

namespace App\Services;

use App\DTOs\Appointment\AppointmentDTO;
use App\Models\Animal;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

class AppointmentService
{
    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)->appointments()->latest('appointment_date')->latest('id')->get();
    }

    public function create(string $animalId, AppointmentDTO $dto): Appointment
    {
        $animal = $this->animal($animalId);

        return $animal->appointments()->create(
            $dto->toArray() + ['organization_id' => $animal->organization_id]
        );
    }

    public function update(string $animalId, string $id, AppointmentDTO $dto): Appointment
    {
        $appointment = $this->animal($animalId)->appointments()->findOrFail($id);
        $appointment->update($dto->toArray());

        return $appointment;
    }

    public function delete(string $animalId, string $id): void
    {
        $appointment = $this->animal($animalId)->appointments()->findOrFail($id);
        $appointment->delete();
    }

    /**
     * Resolve the parent animal scoped to the authenticated user's org, so a
     * tenant can only ever reach appointments of its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
