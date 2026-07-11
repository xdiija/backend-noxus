<?php

namespace App\Http\Controllers;

use App\DTOs\Adoption\AdoptionDTO;
use App\Http\Requests\Adoption\CancelAdoptionRequest;
use App\Http\Requests\Adoption\IndexAdoptionsRequest;
use App\Http\Requests\Adoption\ReturnAdoptionRequest;
use App\Http\Requests\Adoption\StoreUpdateAdoptionRequest;
use App\Http\Resources\AdoptionResource;
use App\Services\AdoptionService;

class AdoptionController extends Controller
{
    public function __construct(
        protected AdoptionService $adoptionService
    ) {}

    public function index(IndexAdoptionsRequest $request)
    {
        $response = AdoptionResource::collection(
            $this->adoptionService->list(
                $request->input('per_page'),
                $request->input('filter'),
                $request->input('status_id'),
                $request->input('animal_id'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateAdoptionRequest $request)
    {
        return new AdoptionResource(
            $this->adoptionService->create(AdoptionDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new AdoptionResource($this->adoptionService->find($id));
    }

    public function update(StoreUpdateAdoptionRequest $request, string $id)
    {
        return new AdoptionResource(
            $this->adoptionService->update($id, AdoptionDTO::fromRequest($request))
        );
    }

    public function return(ReturnAdoptionRequest $request, string $id)
    {
        return new AdoptionResource(
            $this->adoptionService->return(
                $id,
                $request->input('return_date'),
                $request->input('return_reason'),
            )
        );
    }

    public function cancel(CancelAdoptionRequest $request, string $id)
    {
        return new AdoptionResource(
            $this->adoptionService->cancel($id, $request->input('notes'))
        );
    }

    public function destroy(string $id)
    {
        $this->adoptionService->delete($id);

        return response()->noContent();
    }
}
