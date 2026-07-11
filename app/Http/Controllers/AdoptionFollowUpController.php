<?php

namespace App\Http\Controllers;

use App\DTOs\AdoptionFollowUp\AdoptionFollowUpDTO;
use App\Http\Requests\AdoptionFollowUp\StoreUpdateAdoptionFollowUpRequest;
use App\Http\Resources\AdoptionFollowUpResource;
use App\Services\AdoptionFollowUpService;

class AdoptionFollowUpController extends Controller
{
    public function __construct(
        protected AdoptionFollowUpService $adoptionFollowUpService
    ) {}

    public function index(string $adoption)
    {
        return AdoptionFollowUpResource::collection(
            $this->adoptionFollowUpService->list($adoption)
        );
    }

    public function store(StoreUpdateAdoptionFollowUpRequest $request, string $adoption)
    {
        return new AdoptionFollowUpResource(
            $this->adoptionFollowUpService->create($adoption, AdoptionFollowUpDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateAdoptionFollowUpRequest $request, string $adoption, string $followUp)
    {
        return new AdoptionFollowUpResource(
            $this->adoptionFollowUpService->update($adoption, $followUp, AdoptionFollowUpDTO::fromRequest($request))
        );
    }

    public function destroy(string $adoption, string $followUp)
    {
        $this->adoptionFollowUpService->delete($adoption, $followUp);

        return response()->noContent();
    }
}
