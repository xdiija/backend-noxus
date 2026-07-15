<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    private const ACTION_LABELS = [
        'created' => 'Criação',
        'updated' => 'Atualização',
        'deleted' => 'Exclusão',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => [
                'id' => $this->action,
                'name' => self::ACTION_LABELS[$this->action] ?? $this->action,
            ],
            'entity' => $this->entity,
            'entity_id' => $this->entity_id,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'created_at' => DateHelper::toBR($this->created_at, true),
        ];
    }
}
