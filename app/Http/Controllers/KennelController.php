<?php

namespace App\Http\Controllers;

use App\DTOs\Kennel\KennelDTO;
use App\Http\Requests\Kennel\IndexKennelsRequest;
use App\Http\Requests\Kennel\StoreUpdateKennelRequest;
use App\Http\Resources\KennelResource;
use App\Services\KennelService;

class KennelController extends Controller
{
    public function __construct(
        protected KennelService $kennelService
    ) {}

    public function index(IndexKennelsRequest $request)
    {
        $response = KennelResource::collection(
            $this->kennelService->list(
                $request->input('per_page'),
                $request->input('filter'),
                $request->input('status_id'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateKennelRequest $request)
    {
        return new KennelResource(
            $this->kennelService->create(KennelDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new KennelResource($this->kennelService->find($id));
    }

    public function update(StoreUpdateKennelRequest $request, string $id)
    {
        return new KennelResource(
            $this->kennelService->update($id, KennelDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->kennelService->delete($id);

        return response()->noContent();
    }
}
