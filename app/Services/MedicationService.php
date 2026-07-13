<?php

namespace App\Services;

use App\Models\Medication;
use Illuminate\Database\Eloquent\Collection;

class MedicationService
{
    /**
     * Global reference data — shared by all organizations, not org-scoped.
     */
    public function list(): Collection
    {
        return Medication::query()->orderBy('name')->get();
    }
}
