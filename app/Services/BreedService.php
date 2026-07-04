<?php

namespace App\Services;

use App\Models\Breed;
use Illuminate\Database\Eloquent\Collection;

class BreedService
{
    /**
     * Global reference data — shared by all organizations, not org-scoped.
     * Optionally narrowed to a single species for the Animal form's cascade.
     */
    public function list(?int $speciesId = null): Collection
    {
        return Breed::query()
            ->when($speciesId, fn ($q) => $q->where('species_id', $speciesId))
            ->orderBy('name')
            ->get();
    }
}
