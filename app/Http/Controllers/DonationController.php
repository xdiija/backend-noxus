<?php

namespace App\Http\Controllers;

use App\DTOs\Donation\DonationDTO;
use App\Http\Requests\Donation\IndexDonationsRequest;
use App\Http\Requests\Donation\StoreUpdateDonationRequest;
use App\Http\Resources\DonationResource;
use App\Services\DonationService;

class DonationController extends Controller
{
    public function __construct(
        protected DonationService $donationService
    ) {}

    public function index(IndexDonationsRequest $request)
    {
        $response = DonationResource::collection(
            $this->donationService->list(
                $request->input('per_page'),
                $request->input('filter'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateDonationRequest $request)
    {
        return new DonationResource(
            $this->donationService->create(DonationDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new DonationResource($this->donationService->find($id));
    }

    public function update(StoreUpdateDonationRequest $request, string $id)
    {
        return new DonationResource(
            $this->donationService->update($id, DonationDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->donationService->delete($id);

        return response()->noContent();
    }
}
