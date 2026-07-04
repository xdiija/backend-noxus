<?php

namespace App\Http\Controllers;

use App\DTOs\AnimalSize\AnimalSizeDTO;
use App\Http\Requests\AnimalSize\StoreUpdateAnimalSizeRequest;
use App\Http\Resources\AnimalSizeResource;
use App\Services\AnimalSizeService;

class AnimalSizeController extends Controller
{
    public function __construct(
        protected AnimalSizeService $animalSizeService
    ) {}

    public function index()
    {
        return AnimalSizeResource::collection($this->animalSizeService->list());
    }

    public function store(StoreUpdateAnimalSizeRequest $request)
    {
        return new AnimalSizeResource(
            $this->animalSizeService->create(AnimalSizeDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new AnimalSizeResource($this->animalSizeService->find($id));
    }

    public function update(StoreUpdateAnimalSizeRequest $request, string $id)
    {
        return new AnimalSizeResource(
            $this->animalSizeService->update($id, AnimalSizeDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->animalSizeService->delete($id);

        return response()->noContent();
    }
}
