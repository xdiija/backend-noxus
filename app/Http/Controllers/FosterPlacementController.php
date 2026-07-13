<?php

namespace App\Http\Controllers;

use App\DTOs\FosterPlacement\FosterPlacementDTO;
use App\Http\Requests\FosterPlacement\CloseFosterPlacementRequest;
use App\Http\Requests\FosterPlacement\StoreUpdateFosterPlacementRequest;
use App\Http\Resources\FosterPlacementResource;
use App\Services\FosterPlacementService;

class FosterPlacementController extends Controller
{
    public function __construct(
        protected FosterPlacementService $fosterPlacementService
    ) {}

    public function index(string $fosterHome)
    {
        return FosterPlacementResource::collection(
            $this->fosterPlacementService->list($fosterHome)
        );
    }

    public function store(StoreUpdateFosterPlacementRequest $request, string $fosterHome)
    {
        return new FosterPlacementResource(
            $this->fosterPlacementService->create($fosterHome, FosterPlacementDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateFosterPlacementRequest $request, string $fosterHome, string $placement)
    {
        return new FosterPlacementResource(
            $this->fosterPlacementService->update($fosterHome, $placement, FosterPlacementDTO::fromRequest($request))
        );
    }

    public function close(CloseFosterPlacementRequest $request, string $fosterHome, string $placement)
    {
        return new FosterPlacementResource(
            $this->fosterPlacementService->close($fosterHome, $placement, $request->input('exit_date'))
        );
    }

    public function destroy(string $fosterHome, string $placement)
    {
        $this->fosterPlacementService->delete($fosterHome, $placement);

        return response()->noContent();
    }
}
