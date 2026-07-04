<?php

namespace App\Services;

use App\DTOs\Tag\TagDTO;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    /**
     * System defaults (organization_id NULL) plus this org's own tags.
     */
    public function list(): Collection
    {
        return $this->readable()->orderBy('name')->get();
    }

    public function find(string $id): Tag
    {
        return $this->readable()->findOrFail($id);
    }

    public function create(TagDTO $dto): Tag
    {
        // organization_id comes from the authenticated user, never from input.
        return Tag::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, TagDTO $dto): Tag
    {
        $tag = $this->owned()->findOrFail($id);
        $tag->update($dto->toArray());

        return $tag;
    }

    public function delete(string $id): void
    {
        $tag = $this->owned()->findOrFail($id);
        $tag->delete();
    }

    /**
     * Readable rows: system defaults + the org's own entries (ADR-0004).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return Tag::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: only the org's own custom entries. System defaults
     * (organization_id NULL) are shared and can't be edited or deleted.
     */
    private function owned(): Builder
    {
        return Tag::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
