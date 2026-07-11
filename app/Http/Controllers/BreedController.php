<?php

namespace App\Http\Controllers;

use App\DTOs\Breed\BreedDTO;
use App\Http\Requests\Breed\IndexBreedsRequest;
use App\Http\Requests\Breed\StoreUpdateBreedRequest;
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

    public function store(StoreUpdateBreedRequest $request)
    {
        return new BreedResource(
            $this->breedService->create(BreedDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new BreedResource($this->breedService->find($id));
    }

    public function update(StoreUpdateBreedRequest $request, string $id)
    {
        return new BreedResource(
            $this->breedService->update($id, BreedDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->breedService->delete($id);

        return response()->noContent();
    }
}
