<?php

namespace App\Http\Controllers;

use App\DTOs\KennelStay\KennelStayDTO;
use App\Http\Requests\KennelStay\CloseKennelStayRequest;
use App\Http\Requests\KennelStay\StoreUpdateKennelStayRequest;
use App\Http\Requests\KennelStay\TransferKennelStayRequest;
use App\Http\Resources\KennelStayResource;
use App\Services\KennelStayService;

class KennelStayController extends Controller
{
    public function __construct(
        protected KennelStayService $kennelStayService
    ) {}

    public function index(string $kennel)
    {
        return KennelStayResource::collection(
            $this->kennelStayService->list($kennel)
        );
    }

    public function store(StoreUpdateKennelStayRequest $request, string $kennel)
    {
        return new KennelStayResource(
            $this->kennelStayService->create($kennel, KennelStayDTO::fromRequest($request))
        );
    }

    public function update(StoreUpdateKennelStayRequest $request, string $kennel, string $stay)
    {
        return new KennelStayResource(
            $this->kennelStayService->update($kennel, $stay, KennelStayDTO::fromRequest($request))
        );
    }

    public function close(CloseKennelStayRequest $request, string $kennel, string $stay)
    {
        return new KennelStayResource(
            $this->kennelStayService->close($kennel, $stay, $request->input('exit_date'))
        );
    }

    public function transfer(TransferKennelStayRequest $request, string $kennel, string $stay)
    {
        return new KennelStayResource(
            $this->kennelStayService->transfer(
                $kennel,
                $stay,
                (int) $request->input('kennel_id'),
                $request->input('date'),
                $request->input('notes'),
            )
        );
    }

    public function destroy(string $kennel, string $stay)
    {
        $this->kennelStayService->delete($kennel, $stay);

        return response()->noContent();
    }
}
