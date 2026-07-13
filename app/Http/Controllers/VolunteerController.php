<?php

namespace App\Http\Controllers;

use App\DTOs\Volunteer\VolunteerDTO;
use App\Http\Requests\Volunteer\IndexVolunteersRequest;
use App\Http\Requests\Volunteer\StoreUpdateVolunteerRequest;
use App\Http\Resources\VolunteerResource;
use App\Services\VolunteerService;

class VolunteerController extends Controller
{
    public function __construct(
        protected VolunteerService $volunteerService
    ) {}

    public function index(IndexVolunteersRequest $request)
    {
        $response = VolunteerResource::collection(
            $this->volunteerService->list(
                $request->input('per_page'),
                $request->input('filter'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateVolunteerRequest $request)
    {
        return new VolunteerResource(
            $this->volunteerService->create(VolunteerDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new VolunteerResource($this->volunteerService->find($id));
    }

    public function update(StoreUpdateVolunteerRequest $request, string $id)
    {
        return new VolunteerResource(
            $this->volunteerService->update($id, VolunteerDTO::fromRequest($request))
        );
    }

    public function changeStatus(string $id)
    {
        $this->volunteerService->changeStatus($id);

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }

    public function destroy(string $id)
    {
        $this->volunteerService->delete($id);

        return response()->noContent();
    }
}
