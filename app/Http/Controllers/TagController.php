<?php

namespace App\Http\Controllers;

use App\DTOs\Tag\TagDTO;
use App\Http\Requests\Tag\StoreUpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Services\TagService;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {}

    public function index()
    {
        return TagResource::collection($this->tagService->list());
    }

    public function store(StoreUpdateTagRequest $request)
    {
        return new TagResource(
            $this->tagService->create(TagDTO::fromRequest($request))
        );
    }

    public function show(string $id)
    {
        return new TagResource($this->tagService->find($id));
    }

    public function update(StoreUpdateTagRequest $request, string $id)
    {
        return new TagResource(
            $this->tagService->update($id, TagDTO::fromRequest($request))
        );
    }

    public function destroy(string $id)
    {
        $this->tagService->delete($id);

        return response()->noContent();
    }
}
