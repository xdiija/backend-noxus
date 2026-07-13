<?php

namespace App\DTOs\AnimalVaccine;

use App\Http\Requests\AnimalVaccine\StoreUpdateAnimalVaccineRequest;

class AnimalVaccineDTO
{
    public function __construct(
        public readonly int $vaccine_id,
        public readonly string $application_date,
        public readonly ?string $dose,
    ) {}

    public static function fromRequest(StoreUpdateAnimalVaccineRequest $request): self
    {
        $data = $request->validated();

        return new self(
            vaccine_id: (int) $data['vaccine_id'],
            application_date: $data['application_date'],
            dose: $data['dose'] ?? null,
        );
    }

    /**
     * Model-ready payload for `animal_vaccines` (no animal_id — the Service
     * sets it from the parent route).
     */
    public function toArray(): array
    {
        return [
            'vaccine_id' => $this->vaccine_id,
            'application_date' => $this->application_date,
            'dose' => $this->dose,
        ];
    }
}
