<?php

namespace App\Http\Controllers;

use App\DTOs\Donor\DonorDTO;
use App\Http\Requests\Donor\IndexDonorsRequest;
use App\Http\Requests\Donor\StoreUpdateDonorRequest;
use App\Http\Resources\DonorResource;
use App\Services\DonorService;

class DonorController extends Controller
{
    public function __construct(
        protected DonorService $donorService
    ) {}

    public function index(IndexDonorsRequest $request)
    {
        $response = DonorResource::collection(
            $this->donorService->list(
                $request->input('per_page'),
                $request->input('filter'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateDonorRequest $request)
    {
        return new DonorResource(
            $this->donorService->create(DonorDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new DonorResource($this->donorService->find($id));
    }

    public function update(StoreUpdateDonorRequest $request, string $id)
    {
        return new DonorResource(
            $this->donorService->update($id, DonorDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->donorService->delete($id);

        return response()->noContent();
    }
}
