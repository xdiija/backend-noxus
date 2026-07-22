<?php

namespace App\Http\Controllers;

use App\DTOs\Donation\DonationDTO;
use App\Http\Requests\Donation\IndexDonationsRequest;
use App\Http\Requests\Donation\StoreUpdateDonationRequest;
use App\Http\Resources\DonationResource;
use App\Services\DonationService;
use App\Services\DonorService;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function __construct(
        protected DonationService $donationService
    ) {}

    /**
     * Slim {id, name} donor picker for the donation form, gated by the
     * donations permission so it works without the donors menu grant.
     */
    public function donorOptions(Request $request, DonorService $donorService)
    {
        return response()->json([
            'data' => $donorService->options((string) $request->input('filter', '')),
        ]);
    }

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
