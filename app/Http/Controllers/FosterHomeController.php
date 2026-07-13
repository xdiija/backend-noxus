<?php

namespace App\Http\Controllers;

use App\DTOs\FosterHome\FosterHomeDTO;
use App\Http\Requests\FosterHome\IndexFosterHomesRequest;
use App\Http\Requests\FosterHome\StoreUpdateFosterHomeRequest;
use App\Http\Resources\FosterHomeResource;
use App\Services\FosterHomeService;

class FosterHomeController extends Controller
{
    public function __construct(
        protected FosterHomeService $fosterHomeService
    ) {}

    public function index(IndexFosterHomesRequest $request)
    {
        $response = FosterHomeResource::collection(
            $this->fosterHomeService->list(
                $request->input('per_page'),
                $request->input('filter'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateFosterHomeRequest $request)
    {
        return new FosterHomeResource(
            $this->fosterHomeService->create(FosterHomeDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new FosterHomeResource($this->fosterHomeService->find($id));
    }

    public function update(StoreUpdateFosterHomeRequest $request, string $id)
    {
        return new FosterHomeResource(
            $this->fosterHomeService->update($id, FosterHomeDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->fosterHomeService->delete($id);

        return response()->noContent();
    }
}
