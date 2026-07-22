<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnimalStatusHistoryResource;
use App\Services\AnimalStatusHistoryService;

class AnimalStatusHistoryController extends Controller
{
    public function __construct(
        protected AnimalStatusHistoryService $animalStatusHistoryService
    ) {}

    public function index(string $animal)
    {
        return AnimalStatusHistoryResource::collection(
            $this->animalStatusHistoryService->list($animal)
        );
    }
}
