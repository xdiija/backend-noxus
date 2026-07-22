<?php

namespace App\Services;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read side of the animal status log. Rows are written by AnimalService on
 * every status change; this service only lists them (append-only — no
 * create/update/delete surface here, see animal_status_history.md).
 */
class AnimalStatusHistoryService
{
    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)
            ->statusHistory()
            ->with(['oldStatus', 'newStatus', 'changedBy'])
            ->latest('id')
            ->get();
    }

    /**
     * Resolve the parent animal scoped to the authenticated user's org, so a
     * tenant can only ever reach status history of its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
