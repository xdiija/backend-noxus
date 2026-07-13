<?php

namespace App\Http\Controllers;

use App\Http\Resources\VaccineResource;
use App\Services\VaccineService;

class VaccineController extends Controller
{
    public function __construct(
        protected VaccineService $vaccineService
    ) {}

    public function index()
    {
        return VaccineResource::collection($this->vaccineService->list());
    }
}
