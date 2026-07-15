<?php

namespace Tests\Feature;

use App\Models\Adopter;
use App\Models\Adoption;
use App\Models\Animal;
use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Rescue;
use App\Models\Role;
use App\Models\User;
use App\Models\Volunteer;
use Database\Seeders\AdoptionStatusesTableSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 7 — dashboard/report endpoints.
 *
 * Pre-aggregated, org-scoped numbers for the HomePage panel, replacing the
 * old client-side "paginate through everything and count in the browser"
 * approach. The routes are auth-only (no check.permissions): the panel is the
 * landing page every authenticated org user sees, and the payloads carry only
 * aggregates from the user's own organization.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
        $this->seed(AdoptionStatusesTableSeeder::class);
    }

    public function test_summary_returns_org_scoped_counts(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->makeAnimal($org, 'Rex', 'Cachorro', 'Disponível');
        $this->makeAnimal($org, 'Bolt', 'Cachorro', 'Disponível');
        $this->makeAnimal($org, 'Mia', 'Gato', 'Em Tratamento');
        $this->makeAnimal($orgB, 'Alheio', 'Cachorro', 'Disponível');

        Volunteer::create(['organization_id' => $org->id, 'name' => 'Ativa', 'status' => 1]);
        Volunteer::create(['organization_id' => $org->id, 'name' => 'Inativa', 'status' => 2]);
        Volunteer::create(['organization_id' => $orgB->id, 'name' => 'Deles', 'status' => 1]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.active_volunteers', 1)
            ->json('data');

        $byStatus = collect($response['animals_by_status'])->keyBy('name');
        $this->assertSame(2, $byStatus['Disponível']['count']);
        $this->assertSame(1, $byStatus['Em Tratamento']['count']);
        $this->assertSame(0, $byStatus['Adotado']['count']);

        $bySpecies = collect($response['animals_by_species'])->keyBy('name');
        $this->assertSame(2, $bySpecies['Cachorro']['count']);
        $this->assertSame(1, $bySpecies['Gato']['count']);
    }

    public function test_monthly_trends_buckets_adoptions_and_rescues(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $rex = $this->makeAnimal($org, 'Rex', 'Cachorro', 'Disponível');
        $mia = $this->makeAnimal($org, 'Mia', 'Gato', 'Disponível');
        $theirs = $this->makeAnimal($orgB, 'Alheio', 'Cachorro', 'Disponível');

        $adopter = Adopter::create(['organization_id' => $org->id, 'name' => 'Maria']);
        $statusId = (int) DB::table('adoption_statuses')->value('id');

        $this->makeAdoption($org, $rex, $adopter, $statusId, now()->startOfMonth()->addDays(2));
        $this->makeAdoption($org, $mia, $adopter, $statusId, now()->subMonthNoOverflow()->startOfMonth()->addDays(2));

        Rescue::create([
            'organization_id' => $org->id,
            'animal_id' => $rex->id,
            'rescue_date' => now()->subMonthsNoOverflow(2)->startOfMonth()->addDays(2),
        ]);
        Rescue::create([
            'organization_id' => $orgB->id,
            'animal_id' => $theirs->id,
            'rescue_date' => now()->startOfMonth()->addDays(2),
        ]);

        $data = $this->actingAs($admin, 'api')
            ->getJson('/api/dashboard/monthly-trends?months=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->json('data');

        $this->assertSame(now()->subMonthsNoOverflow(2)->format('Y-m'), $data[0]['month']);
        $this->assertSame(1, $data[0]['rescues']);
        $this->assertSame(0, $data[0]['adoptions']);

        $this->assertSame(1, $data[1]['adoptions']);

        $this->assertSame(now()->format('Y-m'), $data[2]['month']);
        $this->assertSame(1, $data[2]['adoptions']);
        // Org B's rescue this month must not leak into org A's series.
        $this->assertSame(0, $data[2]['rescues']);
    }

    public function test_upcoming_appointments_covers_the_window_across_animals(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $rex = $this->makeAnimal($org, 'Rex', 'Cachorro', 'Disponível');
        $mia = $this->makeAnimal($org, 'Mia', 'Gato', 'Disponível');
        $theirs = $this->makeAnimal($orgB, 'Alheio', 'Cachorro', 'Disponível');

        // Inside the window: a scheduled appointment and a suggested return.
        Appointment::create([
            'organization_id' => $org->id,
            'animal_id' => $rex->id,
            'appointment_date' => now()->addDays(3)->setTime(10, 0),
        ]);
        Appointment::create([
            'organization_id' => $org->id,
            'animal_id' => $mia->id,
            'appointment_date' => now()->subDays(20),
            'next_visit' => now()->addDays(10)->toDateString(),
        ]);

        // Outside the window / other org: none of these may show up.
        Appointment::create([
            'organization_id' => $org->id,
            'animal_id' => $rex->id,
            'appointment_date' => now()->addDays(60),
        ]);
        Appointment::create([
            'organization_id' => $orgB->id,
            'animal_id' => $theirs->id,
            'appointment_date' => now()->addDays(3),
        ]);

        $data = $this->actingAs($admin, 'api')
            ->getJson('/api/dashboard/upcoming-appointments?days=30')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data');

        // Date-ordered: the day-3 appointment before the day-10 return visit.
        $this->assertSame('appointment', $data[0]['kind']);
        $this->assertSame('Rex', $data[0]['animal']['name']);
        $this->assertSame('next_visit', $data[1]['kind']);
        $this->assertSame('Mia', $data[1]['animal']['name']);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/summary')->assertUnauthorized();
    }

    // ---- helpers ---------------------------------------------------------

    /**
     * @return array{0: Organization, 1: User}
     */
    private function orgWithAdmin(): array
    {
        $org = Organization::create([
            'name' => 'Org '.Str::random(6),
            'tax_id' => (string) Str::uuid(),
        ]);

        $admin = User::create([
            'organization_id' => $org->id,
            'name' => 'User '.Str::random(6),
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'role_id' => Role::ADMIN_ROLE,
        ]);

        return [$org, $admin];
    }

    private function makeAnimal(Organization $org, string $name, string $species, string $status): Animal
    {
        return Animal::create([
            'organization_id' => $org->id,
            'name' => $name,
            'species_id' => (int) DB::table('species')->where('name', $species)->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => (int) DB::table('animal_statuses')->where('name', $status)->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);
    }

    private function makeAdoption(Organization $org, Animal $animal, Adopter $adopter, int $statusId, $date): Adoption
    {
        return Adoption::create([
            'organization_id' => $org->id,
            'animal_id' => $animal->id,
            'adopter_id' => $adopter->id,
            'adoption_date' => $date->toDateString(),
            'status_id' => $statusId,
        ]);
    }
}
