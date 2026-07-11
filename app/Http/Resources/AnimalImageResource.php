<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AnimalImageResource extends JsonResource
{
    /**
     * How long a presigned URL stays valid.
     */
    private const URL_TTL_MINUTES = 30;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Temporary, signed URL against the browser-facing endpoint. The
            // object stays private in the bucket; the link expires.
            'url' => Storage::disk('s3_public')->temporaryUrl(
                $this->path,
                now()->addMinutes(self::URL_TTL_MINUTES)
            ),
            'created_at' => DateHelper::toBR($this->created_at, true),
        ];
    }
}
