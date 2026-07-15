<?php

namespace App\Services;

use App\DTOs\KennelStay\KennelStayDTO;
use App\Models\Animal;
use App\Models\AnimalFosterHome;
use App\Models\AnimalKennelStay;
use App\Models\Kennel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KennelStayService
{
    /**
     * Kennel status (kennel_statuses lookup) that blocks new stays. Any other
     * status — including "Isolamento" — accepts animals; only a kennel out of
     * service can't (kennels.md).
     */
    private const KENNEL_MAINTENANCE = 'Em Manutenção';

    /**
     * Animal statuses (animal_statuses lookup) that mean the animal is not at
     * the shelter — it can't be placed in a kennel while in one of these.
     * The same list drives the automatic stay close in AnimalService.
     */
    public const OFF_SITE_ANIMAL_STATUSES = ['Em Lar Temporário', 'Adotado', 'Óbito'];

    /** Reason stamped on the stay a transfer opens. */
    private const REASON_TRANSFER = 'Transferência';

    public function list(string $kennelId): Collection
    {
        return $this->kennel($kennelId)
            ->stays()
            ->with('animal')
            ->latest('entry_date')
            ->latest('id')
            ->get();
    }

    /**
     * Open a stay. Enforces the kennel invariants (kennels.md): the kennel is
     * in service and has room, and the animal is at the shelter with no other
     * open kennel or foster stay.
     */
    public function create(string $kennelId, KennelStayDTO $dto): AnimalKennelStay
    {
        return DB::transaction(function () use ($kennelId, $dto) {
            $kennel = $this->kennel($kennelId);

            $this->assertKennelInService($kennel);
            $this->assertCapacity($kennel);
            $this->assertNoOpenStay($dto->animal_id);
            $this->assertNoOpenFosterStay($dto->animal_id);
            $this->assertAnimalOnSite($dto->animal_id);

            return $kennel->stays()->create($dto->toArray())->load('animal');
        });
    }

    /**
     * Correct an open stay's details. Closed stays are immutable history
     * (kennels.md: never edit previous stays), and the animal is immutable
     * after creation — a wrong animal means deleting the stay and opening a
     * new one.
     */
    public function update(string $kennelId, string $id, KennelStayDTO $dto): AnimalKennelStay
    {
        $stay = $this->kennel($kennelId)->stays()->findOrFail($id);

        $this->assertOpen($stay);

        $stay->update([
            'entry_date' => $dto->entry_date,
            'reason' => $dto->reason,
            'notes' => $dto->notes,
        ]);

        return $stay->load('animal');
    }

    /**
     * Close a stay: set the exit_date. The animal's status is untouched —
     * leaving a kennel says nothing about the lifecycle; status-driven exits
     * (adoption, fostering, death) are closed automatically by AnimalService.
     */
    public function close(string $kennelId, string $id, string $exitDate): AnimalKennelStay
    {
        $stay = $this->kennel($kennelId)->stays()->findOrFail($id);

        $this->assertOpen($stay);

        if ($exitDate < $stay->entry_date->toDateString()) {
            throw ValidationException::withMessages([
                'exit_date' => 'A data de saída não pode ser anterior à data de entrada.',
            ]);
        }

        $stay->update(['exit_date' => $exitDate]);

        return $stay->load('animal');
    }

    /**
     * Move the animal to another kennel: close the current stay and open a new
     * one on the same date, in one transaction (kennels.md: every move creates
     * a new record; old stays are never reopened).
     */
    public function transfer(string $kennelId, string $id, int $targetKennelId, string $date, ?string $notes = null): AnimalKennelStay
    {
        return DB::transaction(function () use ($kennelId, $id, $targetKennelId, $date, $notes) {
            $stay = $this->kennel($kennelId)->stays()->findOrFail($id);

            $this->assertOpen($stay);

            if ($date < $stay->entry_date->toDateString()) {
                throw ValidationException::withMessages([
                    'date' => 'A data da transferência não pode ser anterior à data de entrada.',
                ]);
            }

            if ($targetKennelId === $stay->kennel_id) {
                throw ValidationException::withMessages([
                    'kennel_id' => 'O animal já está neste canil.',
                ]);
            }

            $target = $this->kennel((string) $targetKennelId);

            $this->assertKennelInService($target);
            $this->assertCapacity($target);

            $stay->update(['exit_date' => $date]);

            return $target->stays()->create([
                'animal_id' => $stay->animal_id,
                'entry_date' => $date,
                'reason' => self::REASON_TRANSFER,
                'notes' => $notes,
            ])->load(['animal', 'kennel']);
        });
    }

    /**
     * Hard delete, for correcting a stay opened by mistake — not part of the
     * normal flow (history is append-only; real exits close the stay).
     */
    public function delete(string $kennelId, string $id): void
    {
        $stay = $this->kennel($kennelId)->stays()->findOrFail($id);
        $stay->delete();
    }

    private function assertOpen(AnimalKennelStay $stay): void
    {
        if ($stay->exit_date !== null) {
            throw ValidationException::withMessages([
                'id' => 'Esta alocação já foi encerrada.',
            ]);
        }
    }

    private function assertKennelInService(Kennel $kennel): void
    {
        if ($kennel->status()->value('name') === self::KENNEL_MAINTENANCE) {
            throw ValidationException::withMessages([
                'kennel_id' => 'Este canil está em manutenção e não pode receber animais.',
            ]);
        }
    }

    private function assertCapacity(Kennel $kennel): void
    {
        if ($kennel->openStays()->count() >= $kennel->capacity) {
            throw ValidationException::withMessages([
                'kennel_id' => 'Este canil já atingiu sua capacidade máxima.',
            ]);
        }
    }

    /**
     * At most one open kennel stay per animal, org-wide (kennels.md,
     * application-enforced). The animal is already validated as belonging to
     * the org, so scoping by animal_id is safe.
     */
    private function assertNoOpenStay(int $animalId): void
    {
        $hasOpen = AnimalKennelStay::query()
            ->where('animal_id', $animalId)
            ->whereNull('exit_date')
            ->exists();

        if ($hasOpen) {
            throw ValidationException::withMessages([
                'animal_id' => 'Este animal já possui uma alocação ativa em canil.',
            ]);
        }
    }

    /**
     * Kennel = at the shelter; foster home = away. The two placements are
     * mutually exclusive — the foster stay must be closed first (which also
     * moves the animal back to "Disponível").
     */
    private function assertNoOpenFosterStay(int $animalId): void
    {
        $hasOpen = AnimalFosterHome::query()
            ->where('animal_id', $animalId)
            ->whereNull('exit_date')
            ->exists();

        if ($hasOpen) {
            throw ValidationException::withMessages([
                'animal_id' => 'Este animal está em um lar temporário. Encerre essa estadia antes de alocá-lo em um canil.',
            ]);
        }
    }

    /**
     * Only animals physically at the shelter can occupy a kennel. Blocks the
     * off-site lifecycle statuses; any other status (including org-custom
     * ones) is eligible.
     */
    private function assertAnimalOnSite(int $animalId): void
    {
        $statusName = Animal::query()
            ->where('animals.id', $animalId)
            ->join('animal_statuses', 'animal_statuses.id', '=', 'animals.status_id')
            ->value('animal_statuses.name');

        if (in_array($statusName, self::OFF_SITE_ANIMAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'animal_id' => 'O status atual do animal não permite alocação em canil.',
            ]);
        }
    }

    /**
     * Resolve the parent kennel scoped to the authenticated user's org, so a
     * tenant can only ever reach stays on its own kennels.
     */
    private function kennel(string $kennelId): Kennel
    {
        return Kennel::query()
            ->where('organization_id', $this->organizationId())
            ->findOrFail($kennelId);
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
