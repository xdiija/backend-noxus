<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdoptionFollowUpStatusResource;
use App\Services\AdoptionFollowUpStatusService;

class AdoptionFollowUpStatusController extends Controller
{
    public function __construct(
        protected AdoptionFollowUpStatusService $adoptionFollowUpStatusService
    ) {}

    public function index()
    {
        return AdoptionFollowUpStatusResource::collection($this->adoptionFollowUpStatusService->list());
    }
}
