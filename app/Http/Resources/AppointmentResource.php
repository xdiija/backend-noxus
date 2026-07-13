<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_date' => DateHelper::toBR($this->appointment_date, true),
            'veterinarian' => $this->veterinarian,
            'clinic' => $this->clinic,
            'purpose' => $this->purpose,
            'result' => $this->result,
            'next_visit' => DateHelper::toBR($this->next_visit),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
