<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdoptionStatusResource;
use App\Services\AdoptionStatusService;

class AdoptionStatusController extends Controller
{
    public function __construct(
        protected AdoptionStatusService $adoptionStatusService
    ) {}

    public function index()
    {
        return AdoptionStatusResource::collection($this->adoptionStatusService->list());
    }
}
