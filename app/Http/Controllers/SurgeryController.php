<?php

namespace App\Http\Controllers;

use App\DTOs\Surgery\SurgeryDTO;
use App\Http\Requests\Surgery\StoreUpdateSurgeryRequest;
use App\Http\Resources\SurgeryResource;
use App\Services\SurgeryService;

class SurgeryController extends Controller
{
    public function __construct(
        protected SurgeryService $surgeryService
    ) {}

    public function index(string $animal)
    {
        return SurgeryResource::collection(
            $this->surgeryService->list($animal)
        );
    }

    public function store(StoreUpdateSurgeryRequest $request, string $animal)
    {
        return new SurgeryResource(
            $this->surgeryService->create($animal, SurgeryDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateSurgeryRequest $request, string $animal, string $surgery)
    {
        return new SurgeryResource(
            $this->surgeryService->update($animal, $surgery, SurgeryDTO::fromRequest($request))
        );
    }

    public function destroy(string $animal, string $surgery)
    {
        $this->surgeryService->delete($animal, $surgery);

        return response()->noContent();
    }
}
