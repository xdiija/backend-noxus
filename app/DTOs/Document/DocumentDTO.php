<?php

namespace App\DTOs\Document;

use App\Http\Requests\Document\StoreDocumentRequest;

/**
 * Metadata for an upload. The file itself travels separately (UploadedFile is
 * an HTTP type, so it goes straight from controller to service like the
 * animal-images flow); `documentableType` is the friendly API key
 * (Document::OWNER_TYPES) — the Service resolves it to a model class.
 */
class DocumentDTO
{
    public function __construct(
        public readonly ?string $type,
        public readonly ?string $documentableType,
        public readonly ?int $documentableId,
    ) {}

    public static function fromRequest(StoreDocumentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            type: $validated['type'] ?? null,
            documentableType: $validated['documentable_type'] ?? null,
            documentableId: isset($validated['documentable_id']) ? (int) $validated['documentable_id'] : null,
        );
    }
}
