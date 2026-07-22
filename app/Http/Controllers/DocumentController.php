<?php

namespace App\Http\Controllers;

use App\DTOs\Document\DocumentDTO;
use App\Http\Requests\Document\IndexDocumentsRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Services\AdopterService;
use App\Services\AdoptionService;
use App\Services\AnimalService;
use App\Services\DocumentService;
use App\Services\FosterHomeService;
use App\Services\VolunteerService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Owner-type keys (Document::OWNER_TYPES) mapped to the service whose
     * options() feeds the upload dialog's owner picker.
     */
    private const OWNER_OPTION_SERVICES = [
        'animal' => AnimalService::class,
        'adopter' => AdopterService::class,
        'adoption' => AdoptionService::class,
        'volunteer' => VolunteerService::class,
        'foster_home' => FosterHomeService::class,
    ];

    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Slim {id, name} owner picker for the upload dialog, gated by the
     * documents permission so it works without the owner resources' own
     * menu grants.
     */
    public function ownerOptions(Request $request)
    {
        $type = (string) $request->input('type');

        abort_unless(
            isset(self::OWNER_OPTION_SERVICES[$type]),
            400,
            'Tipo de vínculo inválido.'
        );

        return response()->json([
            'data' => app(self::OWNER_OPTION_SERVICES[$type])
                ->options((string) $request->input('filter', '')),
        ]);
    }

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
