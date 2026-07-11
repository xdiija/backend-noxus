<?php

namespace App\Http\Controllers;

use App\DTOs\Adopter\AdopterDTO;
use App\Http\Requests\Adopter\IndexAdoptersRequest;
use App\Http\Requests\Adopter\StoreUpdateAdopterRequest;
use App\Http\Resources\AdopterResource;
use App\Services\AdopterService;

class AdopterController extends Controller
{
    public function __construct(
        protected AdopterService $adopterService
    ) {}

    public function index(IndexAdoptersRequest $request)
    {
        $response = AdopterResource::collection(
            $this->adopterService->list(
                $request->input('per_page'),
                $request->input('filter'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateAdopterRequest $request)
    {
        return new AdopterResource(
            $this->adopterService->create(AdopterDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new AdopterResource($this->adopterService->find($id));
    }

    public function update(StoreUpdateAdopterRequest $request, string $id)
    {
        return new AdopterResource(
            $this->adopterService->update($id, AdopterDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->adopterService->delete($id);

        return response()->noContent();
    }
}
