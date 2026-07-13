<?php

namespace App\DTOs\AnimalMedication;

use App\Http\Requests\AnimalMedication\StoreUpdateAnimalMedicationRequest;

class AnimalMedicationDTO
{
    public function __construct(
        public readonly int $medication_id,
        public readonly ?string $dosage,
        public readonly string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $veterinarian,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateAnimalMedicationRequest $request): self
    {
        $data = $request->validated();

        return new self(
            medication_id: (int) $data['medication_id'],
            dosage: $data['dosage'] ?? null,
            start_date: $data['start_date'],
            end_date: $data['end_date'] ?? null,
            veterinarian: $data['veterinarian'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `animal_medications` (no animal_id — the Service
     * sets it from the parent route).
     */
    public function toArray(): array
    {
        return [
            'medication_id' => $this->medication_id,
            'dosage' => $this->dosage,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'veterinarian' => $this->veterinarian,
            'notes' => $this->notes,
        ];
    }
}
