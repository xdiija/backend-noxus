<?php

namespace App\DTOs\Donation;

use App\Http\Requests\Donation\StoreUpdateDonationRequest;

class DonationDTO
{
    public function __construct(
        public readonly ?int $donorId,
        public readonly ?string $donorName,
        public readonly ?string $donorEmail,
        public readonly ?string $donorPhone,
        public readonly int $donationTypeId,
        public readonly ?float $amount,
        public readonly string $donationDate,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateDonationRequest $request): self
    {
        $data = $request->validated();

        return new self(
            donorId: isset($data['donor_id']) ? (int) $data['donor_id'] : null,
            donorName: $data['donor_name'] ?? null,
            donorEmail: $data['donor_email'] ?? null,
            donorPhone: $data['donor_phone'] ?? null,
            donationTypeId: (int) $data['donation_type_id'],
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            donationDate: $data['donation_date'],
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `donations` (no organization_id — the Service
     * sets it from the authenticated user).
     */
    public function toArray(): array
    {
        return [
            'donor_id' => $this->donorId,
            'donor_name' => $this->donorName,
            'donor_email' => $this->donorEmail,
            'donor_phone' => $this->donorPhone,
            'donation_type_id' => $this->donationTypeId,
            'amount' => $this->amount,
            'donation_date' => $this->donationDate,
            'notes' => $this->notes,
        ];
    }
}
