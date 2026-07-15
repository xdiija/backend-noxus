<?php

namespace App\Services;

use App\Enums\Status;
use App\Helpers\DateHelper;
use App\Models\Adoption;
use App\Models\Animal;
use App\Models\AnimalStatus;
use App\Models\Appointment;
use App\Models\Rescue;
use App\Models\Volunteer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pre-aggregated numbers for the HomePage panel (Module 7), replacing the
 * old client-side "fetch every page and count in the browser" approach.
 * Everything is scoped to the authenticated user's organization.
 */
class DashboardService
{
    /**
     * Animal counts grouped by status and by species, plus the active
     * volunteer count — everything the KPI tiles and the two bar charts need.
     */
    public function summary(): array
    {
        return [
            'animals_by_status' => $this->animalsByStatus(),
            'animals_by_species' => $this->animalsBySpecies(),
            'active_volunteers' => Volunteer::query()
                ->where('organization_id', $this->organizationId())
                ->where('status', Status::ACTIVE->value)
                ->count(),
        ];
    }

    /**
     * Adoptions and rescues bucketed per calendar month, oldest first. Months
     * with zero events are included so charts render a zero point instead of
     * silently skipping the month. Counts every non-deleted adoption row
     * (including cancelled/returned ones), matching what the panel showed
     * when it aggregated the index endpoints client-side.
     *
     * @return array<int, array{month: string, adoptions: int, rescues: int}>
     */
    public function monthlyTrends(int $months): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        // Pull only the date column and bucket in PHP: the volumes inside the
        // window are small and it keeps the grouping portable across drivers.
        $adoptionsByMonth = $this->countByMonth(
            Adoption::query()
                ->where('organization_id', $this->organizationId())
                ->where('adoption_date', '>=', $start->toDateString())
                ->pluck('adoption_date')
        );

        $rescuesByMonth = $this->countByMonth(
            Rescue::query()
                ->where('organization_id', $this->organizationId())
                ->where('rescue_date', '>=', $start)
                ->pluck('rescue_date')
        );

        $trends = [];

        for ($i = 0; $i < $months; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');

            $trends[] = [
                'month' => $key,
                'adoptions' => $adoptionsByMonth[$key] ?? 0,
                'rescues' => $rescuesByMonth[$key] ?? 0,
            ];
        }

        return $trends;
    }

    /**
     * Cross-animal agenda: appointments scheduled inside the window plus
     * suggested return visits (next_visit) falling due, flattened into one
     * date-ordered list. This is the org-wide "what's due in the next N days"
     * view that the per-animal routes can't answer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function upcomingAppointments(int $days): array
    {
        $from = now()->startOfDay();
        $to = now()->addDays($days)->endOfDay();

        $appointments = Appointment::query()
            ->where('organization_id', $this->organizationId())
            ->with('animal:id,name')
            ->where(function (Builder $q) use ($from, $to) {
                $q->whereBetween('appointment_date', [$from, $to])
                    ->orWhereBetween('next_visit', [$from->toDateString(), $to->toDateString()]);
            })
            ->get();

        $items = [];

        foreach ($appointments as $appointment) {
            if ($appointment->appointment_date->between($from, $to)) {
                $items[] = $this->upcomingItem($appointment, 'appointment', $appointment->appointment_date);
            }

            if ($appointment->next_visit?->between($from, $to)) {
                $items[] = $this->upcomingItem($appointment, 'next_visit', $appointment->next_visit);
            }
        }

        usort($items, fn (array $a, array $b) => $a['sort_date'] <=> $b['sort_date']);

        return array_map(function (array $item) {
            unset($item['sort_date']);

            return $item;
        }, $items);
    }

    private function upcomingItem(Appointment $appointment, string $kind, Carbon $date): array
    {
        return [
            'id' => $appointment->id,
            'kind' => $kind,
            'date' => DateHelper::toBR($date, $kind === 'appointment'),
            'sort_date' => $date->format('Y-m-d H:i:s'),
            'animal' => [
                'id' => $appointment->animal?->id,
                'name' => $appointment->animal?->name,
            ],
            'veterinarian' => $appointment->veterinarian,
            'clinic' => $appointment->clinic,
            'purpose' => $appointment->purpose,
        ];
    }

    /**
     * Statuses visible to the org (system defaults + its own, ADR-0004) in
     * sort order, each with its animal count — zero-count statuses included
     * so the chart keeps a stable, complete axis.
     */
    private function animalsByStatus(): array
    {
        $counts = Animal::query()
            ->where('organization_id', $this->organizationId())
            ->select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        return AnimalStatus::query()
            ->where(function (Builder $q) {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', $this->organizationId());
            })
            ->orderBy('sort_order')
            ->get()
            ->map(fn (AnimalStatus $status) => [
                'id' => $status->id,
                'name' => $status->name,
                'color' => $status->color,
                'sort_order' => $status->sort_order,
                'count' => (int) ($counts[$status->id] ?? 0),
            ])
            ->all();
    }

    private function animalsBySpecies(): array
    {
        return Animal::query()
            ->where('animals.organization_id', $this->organizationId())
            ->join('species', 'species.id', '=', 'animals.species_id')
            ->groupBy('species.id', 'species.name')
            ->orderByRaw('count(*) desc')
            ->get([
                'species.id',
                'species.name',
                DB::raw('count(*) as count'),
            ])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Dates bucketed as "YYYY-MM" => count. Handles both raw strings and
     * Carbon instances (Eloquent pluck applies date casts).
     */
    private function countByMonth(Collection $dates): Collection
    {
        return $dates->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'));
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
