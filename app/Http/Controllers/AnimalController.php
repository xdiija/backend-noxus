<?php

namespace App\Http\Controllers;

use App\DTOs\Animal\AnimalDTO;
use App\Http\Requests\Animal\ChangeAnimalStatusRequest;
use App\Http\Requests\Animal\IndexAnimalsRequest;
use App\Http\Requests\Animal\StoreUpdateAnimalRequest;
use App\Http\Resources\AnimalResource;
use App\Http\Resources\AnimalSizeResource;
use App\Http\Resources\AnimalStatusResource;
use App\Http\Resources\BreedResource;
use App\Http\Resources\SpeciesResource;
use App\Http\Resources\TagResource;
use App\Services\AnimalService;
use App\Services\AnimalSizeService;
use App\Services\AnimalStatusService;
use App\Services\BreedService;
use App\Services\SpeciesService;
use App\Services\TagService;

class AnimalController extends Controller
{
    public function __construct(
        protected AnimalService $animalService
    ) {}

    /**
     * Status options for animal consumers (list page dialog, form selects),
     * exposed under the `animals` permission so those pages don't depend on
     * the animal-statuses lookup grant (see /animal-statuses for management).
     */
    public function statusOptions(AnimalStatusService $animalStatusService)
    {
        return AnimalStatusResource::collection($animalStatusService->list());
    }

    /**
     * All the global lookups the Animal form needs, in one call gated by the
     * `animals` permission — the form works without the per-lookup menu
     * grants. Breeds come unfiltered (they carry species_id); the form
     * narrows them per species client-side.
     */
    public function formOptions(
        SpeciesService $speciesService,
        BreedService $breedService,
        AnimalSizeService $animalSizeService,
        AnimalStatusService $animalStatusService,
        TagService $tagService,
    ) {
        return response()->json([
            'data' => [
                'species' => SpeciesResource::collection($speciesService->list()),
                'breeds' => BreedResource::collection($breedService->list()),
                'sizes' => AnimalSizeResource::collection($animalSizeService->list()),
                'statuses' => AnimalStatusResource::collection($animalStatusService->list()),
                'tags' => TagResource::collection($tagService->list()),
            ],
        ]);
    }

    public function index(IndexAnimalsRequest $request)
    {
        $response = AnimalResource::collection(
            $this->animalService->list(
                $request->input('per_page'),
                $request->input('filter'),
                $request->input('status_id'),
                $request->input('species_id'),
                $request->input('sex'),
                $request->input('size_id'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function store(StoreUpdateAnimalRequest $request)
    {
        return new AnimalResource(
            $this->animalService->create(AnimalDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new AnimalResource($this->animalService->find($id));
    }

    public function update(StoreUpdateAnimalRequest $request, string $id)
    {
        return new AnimalResource(
            $this->animalService->update($id, AnimalDTO::fromRequest($request))
        );
    }

    public function changeStatus(ChangeAnimalStatusRequest $request, string $id)
    {
        $this->animalService->changeStatus(
            $id,
            (int) $request->input('status_id'),
            $request->input('notes'),
        );

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }

    public function destroy(string $id)
    {
        $this->animalService->delete($id);

        return response()->noContent();
    }
}
