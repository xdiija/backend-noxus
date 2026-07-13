<?php

namespace App\Http\Controllers;

use App\DTOs\AnimalVaccine\AnimalVaccineDTO;
use App\Http\Requests\AnimalVaccine\StoreUpdateAnimalVaccineRequest;
use App\Http\Resources\AnimalVaccineResource;
use App\Services\AnimalVaccineService;

class AnimalVaccineController extends Controller
{
    public function __construct(
        protected AnimalVaccineService $animalVaccineService
    ) {}

    public function index(string $animal)
    {
        return AnimalVaccineResource::collection(
            $this->animalVaccineService->list($animal)
        );
    }

    public function store(StoreUpdateAnimalVaccineRequest $request, string $animal)
    {
        return new AnimalVaccineResource(
            $this->animalVaccineService->create($animal, AnimalVaccineDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateAnimalVaccineRequest $request, string $animal, string $vaccine)
    {
        return new AnimalVaccineResource(
            $this->animalVaccineService->update($animal, $vaccine, AnimalVaccineDTO::fromRequest($request))
        );
    }

    public function destroy(string $animal, string $vaccine)
    {
        $this->animalVaccineService->delete($animal, $vaccine);

        return response()->noContent();
    }
}
