<?php

namespace App\Http\Controllers;

use App\Http\Requests\Breed\IndexBreedsRequest;
use App\Http\Resources\BreedResource;
use App\Services\BreedService;

class BreedController extends Controller
{
    public function __construct(
        protected BreedService $breedService
    ) {}

    public function index(IndexBreedsRequest $request)
    {
        return BreedResource::collection(
            $this->breedService->list($request->input('species_id'))
        );
    }
}
