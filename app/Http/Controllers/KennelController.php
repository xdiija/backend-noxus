<?php

namespace App\Http\Controllers;

use App\DTOs\Kennel\KennelDTO;
use App\Http\Requests\Kennel\IndexKennelsRequest;
use App\Http\Requests\Kennel\StoreUpdateKennelRequest;
use App\Http\Resources\AnimalSizeResource;
use App\Http\Resources\KennelResource;
use App\Http\Resources\KennelStatusResource;
use App\Services\AnimalService;
use App\Services\AnimalSizeService;
use App\Services\KennelService;
use App\Services\KennelStatusService;
use Illuminate\Http\Request;

class KennelController extends Controller
{
    public function __construct(
        protected KennelService $kennelService
    ) {}

    /**
     * The lookups the kennel form needs (statuses + sizes), gated by the
     * kennels permission so it works without the lookup menu grants.
     */
    public function formOptions(
        KennelStatusService $kennelStatusService,
        AnimalSizeService $animalSizeService,
    ) {
        return response()->json([
            'data' => [
                'statuses' => KennelStatusResource::collection($kennelStatusService->list()),
                'sizes' => AnimalSizeResource::collection($animalSizeService->list()),
            ],
        ]);
    }

    /**
     * Slim {id, name} animal picker for the stay dialog, gated by the
     * kennels permission so it works without the animals menu grant.
     */
    public function animalOptions(Request $request, AnimalService $animalService)
    {
        return response()->json([
            'data' => $animalService->options((string) $request->input('filter', '')),
        ]);
    }

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
