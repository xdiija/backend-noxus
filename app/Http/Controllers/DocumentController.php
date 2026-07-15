<?php

namespace App\Http\Controllers;

use App\DTOs\Document\DocumentDTO;
use App\Http\Requests\Document\IndexDocumentsRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Services\DocumentService;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * No ETag/304 here: every payload embeds fresh presigned URLs, so the
     * body (and its hash) changes on every request by design.
     */
    public function index(IndexDocumentsRequest $request)
    {
        return DocumentResource::collection(
            $this->documentService->list(
                $request->input('per_page'),
                $request->input('filter'),
                $request->input('documentable_type'),
                $request->input('documentable_id') !== null ? (int) $request->input('documentable_id') : null,
            )
        );
    }

    public function store(StoreDocumentRequest $request)
    {
        return new DocumentResource(
            $this->documentService->upload($request->file('file'), DocumentDTO::fromRequest($request))
        );
    }

    public function update(UpdateDocumentRequest $request, string $id)
    {
        return new DocumentResource(
            $this->documentService->update($id, $request->validated()['type'] ?? null)
        );
    }

    public function destroy(string $id)
    {
        $this->documentService->delete($id);

        return response()->noContent();
    }
}
