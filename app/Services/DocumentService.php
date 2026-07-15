<?php

namespace App\Services;

use App\DTOs\Document\DocumentDTO;
use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * The disk objects are written to / deleted from (internal MinIO endpoint).
     */
    private const DISK = 's3';

    public function list(
        int $perPage = 10,
        string $filter = '',
        ?string $documentableType = null,
        ?int $documentableId = null,
    ): LengthAwarePaginator {
        $query = $this->scoped()->with('documentable');

        if ($documentableType) {
            $query->where('documentable_type', Document::OWNER_TYPES[$documentableType]);

            if ($documentableId) {
                $query->where('documentable_id', $documentableId);
            }
        }

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('type', 'like', "%{$filter}%")
                    ->orWhere('path', 'like', "%{$filter}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function upload(UploadedFile $file, DocumentDTO $dto): Document
    {
        $organizationId = $this->organizationId();
        $owner = $this->resolveOwner($dto);

        // Namespace objects by org + owner so keys never collide across
        // tenants; keep the original filename (behind a unique prefix) so the
        // UI can display it — the schema has no name column by design.
        $segment = $dto->documentableType
            ? "{$dto->documentableType}/{$owner->getKey()}"
            : 'organization';

        $path = $file->storeAs(
            "documents/{$organizationId}/{$segment}",
            uniqid().'_'.$this->safeFilename($file),
            self::DISK
        );

        $document = Document::create([
            'organization_id' => $organizationId,
            'documentable_type' => $dto->documentableType ? Document::OWNER_TYPES[$dto->documentableType] : null,
            'documentable_id' => $owner?->getKey(),
            'path' => $path,
            'type' => $dto->type,
        ]);

        return $document->load('documentable');
    }

    public function update(string $id, ?string $type): Document
    {
        $document = $this->scoped()->findOrFail($id);
        $document->update(['type' => $type]);

        return $document->load('documentable');
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $document = $this->scoped()->findOrFail($id);

            Storage::disk(self::DISK)->delete($document->path);
            $document->delete();
        });
    }

    /**
     * Resolve the owning record scoped to the authenticated user's
     * organization (404 otherwise), so a tenant can never attach a document
     * to another org's record. Null when the document is org-level.
     */
    private function resolveOwner(DocumentDTO $dto): ?Model
    {
        if (! $dto->documentableType) {
            return null;
        }

        $class = Document::OWNER_TYPES[$dto->documentableType];

        return $class::query()
            ->where('organization_id', $this->organizationId())
            ->findOrFail($dto->documentableId);
    }

    /**
     * The client filename slugged down to something object-store friendly,
     * keeping the extension.
     */
    private function safeFilename(UploadedFile $file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = strtolower($file->getClientOriginalExtension());

        return Str::limit($name ?: 'documento', 80, '').($extension ? ".{$extension}" : '');
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's documents.
     */
    private function scoped(): Builder
    {
        return Document::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
