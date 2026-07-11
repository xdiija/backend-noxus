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
        // Noxus-managed global reference (ADR-0008): rows are shared across all
        // orgs, so they carry no organization_id. The `noxus` middleware ensures
        // only system operators reach this.
        return Tag::create($dto->toArray() + [
            'organization_id' => null,
        ]);
    }

    public function update(string $id, TagDTO $dto): Tag
    {
        $tag = $this->writable()->findOrFail($id);
        $tag->update($dto->toArray());

        return $tag;
    }

    public function delete(string $id): void
    {
        $tag = $this->writable()->findOrFail($id);
        $tag->delete();
    }

    /**
     * Readable rows: the shared global entries (organization_id NULL). The
     * org-scoped predicate is retained defensively in case per-org rows ever
     * exist again (ADR-0004 history).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return Tag::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: the shared global entries. Noxus manages these directly
     * (ADR-0008); there are no org-owned tags to scope to.
     */
    private function writable(): Builder
    {
        return Tag::query()->whereNull('organization_id');
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
