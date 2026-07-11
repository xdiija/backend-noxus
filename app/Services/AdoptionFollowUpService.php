<?php

namespace App\Services;

use App\DTOs\AdoptionFollowUp\AdoptionFollowUpDTO;
use App\Models\Adoption;
use App\Models\AdoptionFollowUp;
use Illuminate\Database\Eloquent\Collection;

class AdoptionFollowUpService
{
    public function list(string $adoptionId): Collection
    {
        return $this->adoption($adoptionId)->followUps()->with('status')->latest('follow_up_date')->get();
    }

    public function create(string $adoptionId, AdoptionFollowUpDTO $dto): AdoptionFollowUp
    {
        $adoption = $this->adoption($adoptionId);

        return $adoption->followUps()->create($dto->toArray())->load('status');
    }

    public function update(string $adoptionId, string $id, AdoptionFollowUpDTO $dto): AdoptionFollowUp
    {
        $followUp = $this->adoption($adoptionId)->followUps()->findOrFail($id);
        $followUp->update($dto->toArray());

        return $followUp->load('status');
    }

    public function delete(string $adoptionId, string $id): void
    {
        $followUp = $this->adoption($adoptionId)->followUps()->findOrFail($id);
        $followUp->delete();
    }

    /**
     * Resolve the parent adoption scoped to the authenticated user's org, so a
     * tenant can only ever reach follow-ups on its own adoptions.
     */
    private function adoption(string $adoptionId): Adoption
    {
        return Adoption::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($adoptionId);
    }
}
