<?php

namespace App\Http\Controllers;

use App\DTOs\Animal\AnimalDTO;
use App\Http\Requests\Animal\ChangeAnimalStatusRequest;
use App\Http\Requests\Animal\IndexAnimalsRequest;
use App\Http\Requests\Animal\StoreUpdateAnimalRequest;
use App\Http\Resources\AnimalResource;
use App\Services\AnimalService;

class AnimalController extends Controller
{
    public function __construct(
        protected AnimalService $animalService
    ) {}

    public function index(IndexAnimalsRequest $request)
    {
        $response = AnimalResource::collection(
            $this->animalService->list(
                $request->input('per_page'),
                $request->input('filter'),
                $request->input('status_id'),
                $request->input('species_id'),
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
