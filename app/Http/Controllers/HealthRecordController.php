<?php

namespace App\Http\Controllers;

use App\DTOs\HealthRecord\HealthRecordDTO;
use App\Http\Requests\HealthRecord\StoreUpdateHealthRecordRequest;
use App\Http\Resources\HealthRecordResource;
use App\Services\HealthRecordService;

class HealthRecordController extends Controller
{
    public function __construct(
        protected HealthRecordService $healthRecordService
    ) {}

    public function index(string $animal)
    {
        return HealthRecordResource::collection(
            $this->healthRecordService->list($animal)
        );
    }

    public function store(StoreUpdateHealthRecordRequest $request, string $animal)
    {
        return new HealthRecordResource(
            $this->healthRecordService->create($animal, HealthRecordDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateHealthRecordRequest $request, string $animal, string $healthRecord)
    {
        return new HealthRecordResource(
            $this->healthRecordService->update($animal, $healthRecord, HealthRecordDTO::fromRequest($request))
        );
    }

    public function destroy(string $animal, string $healthRecord)
    {
        $this->healthRecordService->delete($animal, $healthRecord);

        return response()->noContent();
    }
}
