<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'donor' => new DonorResource($this->whenLoaded('donor')),
            'donor_name' => $this->donor_name,
            'donor_email' => $this->donor_email,
            'donor_phone' => $this->donor_phone,
            'donation_type' => new DonationTypeResource($this->whenLoaded('donationType')),
            'amount' => $this->amount !== null ? (float) $this->amount : null,
            'donation_date' => DateHelper::toBR($this->donation_date),
            'notes' => $this->notes,
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
