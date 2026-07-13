<?php

namespace App\Http\Controllers;

use App\DTOs\Organization\OrganizationDTO;
use App\Http\Requests\Organization\IndexOrganizationsRequest;
use App\Http\Requests\Organization\StoreUpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Services\OrganizationService;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

    public function index(IndexOrganizationsRequest $request)
    {
        return OrganizationResource::collection(
            $this->organizationService->list(
                $request->input('per_page'), $request->input('filter')
            )
        );
    }

    public function store(StoreUpdateOrganizationRequest $request)
    {
        return new OrganizationResource(
            $this->organizationService->create(OrganizationDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new OrganizationResource($this->organizationService->find($id));
    }

    public function update(StoreUpdateOrganizationRequest $request, string $id)
    {
        return new OrganizationResource(
            $this->organizationService->update($id, OrganizationDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->organizationService->delete($id);

        return response()->noContent();
    }
}
