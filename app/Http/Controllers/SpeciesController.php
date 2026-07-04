<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpeciesResource;
use App\Services\SpeciesService;

class SpeciesController extends Controller
{
    public function __construct(
        protected SpeciesService $speciesService
    ) {}

    public function index()
    {
        return SpeciesResource::collection($this->speciesService->list());
    }
}
