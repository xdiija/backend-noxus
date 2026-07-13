<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 4 — vet appointments.
 *
 * Scheduled/attended visits nested under an animal. Org scoping is enforced
 * in AppointmentService via the parent animal; organization_id is set from
 * the animal, never from request input.
 */
class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(AnimalMenusSeeder::class);
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
    }

    public function test_store_creates_appointment_under_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $id = $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/appointments", [
                'appointment_date' => '2026-07-10 14:30:00',
                'veterinarian' => 'Dra. Ana',
                'clinic' => 'Clínica Vet Vida',
                'purpose' => 'Consulta de rotina',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('appointments', [
            'id' => $id,
            'animal_id' => $animal->id,
            'organization_id' => $org->id,
            'clinic' => 'Clínica Vet Vida',
        ]);
    }

    public function test_index_lists_appointments_of_the_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $animal->appointments()->create([
            'organization_id' => $org->id,
            'appointment_date' => '2026-07-10 14:30:00',
            'purpose' => 'Vacinação',
        ]);

        $this->actingAs($admin, 'api')
            ->getJson("/api/animals/{$animal->id}/appointments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['purpose' => 'Vacinação']);
    }

    public function test_update_records_the_visit_outcome(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $appointment = $animal->appointments()->create([
            'organization_id' => $org->id,
            'appointment_date' => '2026-07-10 14:30:00',
        ]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}/appointments/{$appointment->id}", [
                'appointment_date' => '2026-07-10 14:30:00',
                'result' => 'Animal saudável, sem alterações',
                'next_visit' => '2026-08-10',
            ])
            ->assertOk();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'result' => 'Animal saudável, sem alterações',
        ]);
    }

    public function test_destroy_soft_deletes_appointment(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $appointment = $animal->appointments()->create([
            'organization_id' => $org->id,
            'appointment_date' => '2026-07-10 14:30:00',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animals/{$animal->id}/appointments/{$appointment->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    }

    public function test_appointment_on_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAnimal = $this->makeAnimal($orgB);

        $this->actingAs($adminA, 'api')
            ->postJson("/api/animals/{$theirAnimal->id}/appointments", [
                'appointment_date' => '2026-07-10 14:30:00',
            ])
            ->assertNotFound();
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

    private function makeAnimal(Organization $org): Animal
    {
        return Animal::create([
            'organization_id' => $org->id,
            'name' => 'Rex',
            'species_id' => (int) DB::table('species')->where('name', 'Cachorro')->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => (int) DB::table('animal_statuses')->where('name', 'Em Tratamento')->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);
    }
}
