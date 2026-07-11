<?php

namespace App\Http\Controllers;

use App\Http\Requests\Animal\StoreAnimalImageRequest;
use App\Http\Resources\AnimalImageResource;
use App\Services\AnimalImageService;

class AnimalImageController extends Controller
{
    public function __construct(
        protected AnimalImageService $animalImageService
    ) {}

    public function index(string $animal)
    {
        return AnimalImageResource::collection(
            $this->animalImageService->list($animal)
        );
    }

    public function store(StoreAnimalImageRequest $request, string $animal)
    {
        return new AnimalImageResource(
            $this->animalImageService->upload($animal, $request->file('image'))
        );
    }

    public function destroy(string $animal, string $image)
    {
        $this->animalImageService->delete($animal, $image);

        return response()->noContent();
    }
}
