<?php

namespace App\Http\Controllers;

use App\DTOs\AnimalStatus\AnimalStatusDTO;
use App\Http\Requests\AnimalStatus\StoreUpdateAnimalStatusRequest;
use App\Http\Resources\AnimalStatusResource;
use App\Services\AnimalStatusService;

class AnimalStatusController extends Controller
{
    public function __construct(
        protected AnimalStatusService $animalStatusService
    ) {}

    public function index()
    {
        return AnimalStatusResource::collection($this->animalStatusService->list());
    }

    public function store(StoreUpdateAnimalStatusRequest $request)
    {
        return new AnimalStatusResource(
            $this->animalStatusService->create(AnimalStatusDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new AnimalStatusResource($this->animalStatusService->find($id));
    }

    public function update(StoreUpdateAnimalStatusRequest $request, string $id)
    {
        return new AnimalStatusResource(
            $this->animalStatusService->update($id, AnimalStatusDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->animalStatusService->delete($id);

        return response()->noContent();
    }
}
