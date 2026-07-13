<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationResource;
use App\Services\MedicationService;

class MedicationController extends Controller
{
    public function __construct(
        protected MedicationService $medicationService
    ) {}

    public function index()
    {
        return MedicationResource::collection($this->medicationService->list());
    }
}
