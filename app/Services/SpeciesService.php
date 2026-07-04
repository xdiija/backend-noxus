<?php

namespace App\Services;

use App\Models\Species;
use Illuminate\Database\Eloquent\Collection;

class SpeciesService
{
    /**
     * Global reference data — shared by all organizations, not org-scoped.
     */
    public function list(): Collection
    {
        return Species::query()->orderBy('name')->get();
    }
}
