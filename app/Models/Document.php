<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A polymorphic file attachment (ADR-0005). `path` is the object key on the
 * `s3` disk (MinIO), stored presigned-private like animal images. Both morph
 * columns null = an org-level document not tied to a specific record. No soft
 * deletes — removing a row means the file is gone (the Service deletes the
 * underlying object too).
 */
class Document extends Model
{
    use Auditable;

    /**
     * Friendly owner keys accepted by the API mapped to their models. Every
     * owner is organization-scoped, so ownership checks are uniform.
     */
    public const OWNER_TYPES = [
        'animal' => Animal::class,
        'adopter' => Adopter::class,
        'adoption' => Adoption::class,
        'volunteer' => Volunteer::class,
        'foster_home' => FosterHome::class,
    ];

    protected $table = 'documents';

    protected $fillable = [
        'organization_id',
        'documentable_type',
        'documentable_id',
        'path',
        'type',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The friendly API key for the owner model class (e.g. "animal"), or null
     * for an org-level document.
     */
    public function ownerKey(): ?string
    {
        $key = array_search($this->documentable_type, self::OWNER_TYPES, true);

        return $key === false ? null : $key;
    }
}
