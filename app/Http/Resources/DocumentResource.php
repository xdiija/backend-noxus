<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentResource extends JsonResource
{
    /**
     * How long a presigned URL stays valid.
     */
    private const URL_TTL_MINUTES = 30;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Original filename: the object key is "{uniqid}_{name.ext}".
            'name' => Str::after(basename($this->path), '_'),
            'type' => $this->type,
            // Temporary, signed URL against the browser-facing endpoint. The
            // object stays private in the bucket; the link expires.
            'url' => Storage::disk('s3_public')->temporaryUrl(
                $this->path,
                now()->addMinutes(self::URL_TTL_MINUTES)
            ),
            'documentable' => $this->when($this->documentable_type !== null, fn () => [
                'type' => $this->ownerKey(),
                'id' => $this->documentable_id,
                // Adoptions have no name column; the frontend labels those by id.
                'name' => $this->whenLoaded('documentable', fn () => $this->documentable?->name ?? null, null),
            ]),
            'created_at' => DateHelper::toBR($this->created_at, true),
        ];
    }
}
