<?php

namespace App\DTOs\Appointment;

use App\Http\Requests\Appointment\StoreUpdateAppointmentRequest;

class AppointmentDTO
{
    public function __construct(
        public readonly string $appointment_date,
        public readonly ?string $veterinarian,
        public readonly ?string $clinic,
        public readonly ?string $purpose,
        public readonly ?string $result,
        public readonly ?string $next_visit,
    ) {}

    public static function fromRequest(StoreUpdateAppointmentRequest $request): self
    {
        $data = $request->validated();

        return new self(
            appointment_date: $data['appointment_date'],
            veterinarian: $data['veterinarian'] ?? null,
            clinic: $data['clinic'] ?? null,
            purpose: $data['purpose'] ?? null,
            result: $data['result'] ?? null,
            next_visit: $data['next_visit'] ?? null,
        );
    }

    /**
     * Model-ready payload for `appointments` (no animal_id/organization_id —
     * the Service sets both from the parent animal).
     */
    public function toArray(): array
    {
        return [
            'appointment_date' => $this->appointment_date,
            'veterinarian' => $this->veterinarian,
            'clinic' => $this->clinic,
            'purpose' => $this->purpose,
            'result' => $this->result,
            'next_visit' => $this->next_visit,
        ];
    }
}
