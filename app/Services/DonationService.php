<?php

namespace App\Services;

use App\DTOs\Donation\DonationDTO;
use App\Models\Donation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DonationService
{
    public function list(int $perPage = 10, string $filter = ''): LengthAwarePaginator
    {
        $query = $this->scoped()->with(['donor', 'donationType']);

        if (! empty($filter)) {
            // Group the OR-filter so it can't escape the organization scope.
            $query->where(function (Builder $q) use ($filter) {
                $q->where('donor_name', 'like', "%{$filter}%")
                    ->orWhere('notes', 'like', "%{$filter}%")
                    ->orWhereHas('donor', function (Builder $donor) use ($filter) {
                        $donor->where('name', 'like', "%{$filter}%");
                    });
            });
        }

        return $query->latest('donation_date')->latest('id')->paginate($perPage);
    }

    public function find(string $id): Donation
    {
        return $this->scoped()->with(['donor', 'donationType'])->findOrFail($id);
    }

    public function create(DonationDTO $dto): Donation
    {
        // organization_id comes from the authenticated user, never from input.
        $donation = Donation::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);

        return $donation->load(['donor', 'donationType']);
    }

    public function update(string $id, DonationDTO $dto): Donation
    {
        $donation = $this->scoped()->findOrFail($id);
        $donation->update($dto->toArray());

        return $donation->load(['donor', 'donationType']);
    }

    public function delete(string $id): void
    {
        $donation = $this->scoped()->findOrFail($id);
        $donation->delete();
    }

    /**
     * Base query scoped to the authenticated user's organization. Every read
     * goes through here so a tenant can never reach another org's donations.
     */
    private function scoped(): Builder
    {
        return Donation::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
