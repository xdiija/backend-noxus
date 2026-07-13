<?php

namespace App\Http\Controllers;

use App\DTOs\AnimalMedication\AnimalMedicationDTO;
use App\Http\Requests\AnimalMedication\StoreUpdateAnimalMedicationRequest;
use App\Http\Resources\AnimalMedicationResource;
use App\Services\AnimalMedicationService;

class AnimalMedicationController extends Controller
{
    public function __construct(
        protected AnimalMedicationService $animalMedicationService
    ) {}

    public function index(string $animal)
    {
        return AnimalMedicationResource::collection(
            $this->animalMedicationService->list($animal)
        );
    }

    public function store(StoreUpdateAnimalMedicationRequest $request, string $animal)
    {
        return new AnimalMedicationResource(
            $this->animalMedicationService->create($animal, AnimalMedicationDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateAnimalMedicationRequest $request, string $animal, string $medication)
    {
        return new AnimalMedicationResource(
            $this->animalMedicationService->update($animal, $medication, AnimalMedicationDTO::fromRequest($request))
        );
    }

    public function destroy(string $animal, string $medication)
    {
        $this->animalMedicationService->delete($animal, $medication);

        return response()->noContent();
    }
}
