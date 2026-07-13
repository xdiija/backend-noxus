<?php

namespace App\Services;

use App\Models\Vaccine;
use Illuminate\Database\Eloquent\Collection;

class VaccineService
{
    /**
     * Global reference data — shared by all organizations, not org-scoped.
     */
    public function list(): Collection
    {
        return Vaccine::query()->orderBy('name')->get();
    }
}
