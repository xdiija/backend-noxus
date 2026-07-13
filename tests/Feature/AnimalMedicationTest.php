<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\MedicationsTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 4 — medication courses (animal_medications).
 *
 * Courses are nested under an animal (org scoping via the parent in
 * AnimalMedicationService); end_date null = ongoing. The `medications`
 * catalog is global reference data, read-only and shared by all orgs.
 */
class AnimalMedicationTest extends TestCase
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
        $this->seed(MedicationsTableSeeder::class);
    }

    public function test_medication_catalog_lists_seeded_defaults(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->getJson('/api/medications')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Vermífugo']);
    }

    public function test_store_creates_ongoing_course_under_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $id = $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/medications", [
                'medication_id' => $this->medicationId('Antibiótico'),
                'dosage' => '250mg, 2x ao dia',
                'start_date' => '2026-07-01',
                'veterinarian' => 'Dr. Paulo',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('animal_medications', [
            'id' => $id,
            'animal_id' => $animal->id,
            'dosage' => '250mg, 2x ao dia',
            'end_date' => null,
        ]);
    }

    public function test_update_closes_the_course(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $course = $animal->medications()->create([
            'medication_id' => $this->medicationId('Vermífugo'),
            'start_date' => '2026-06-15',
        ]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}/medications/{$course->id}", [
                'medication_id' => $this->medicationId('Vermífugo'),
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-30',
            ])
            ->assertOk();

        $this->assertDatabaseHas('animal_medications', [
            'id' => $course->id,
            'end_date' => '2026-06-30 00:00:00',
        ]);
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/medications", [
                'medication_id' => $this->medicationId('Vermífugo'),
                'start_date' => '2026-07-01',
                'end_date' => '2026-06-01',
            ])
            ->assertStatus(400)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_course_on_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAnimal = $this->makeAnimal($orgB);

        $this->actingAs($adminA, 'api')
            ->postJson("/api/animals/{$theirAnimal->id}/medications", [
                'medication_id' => $this->medicationId('Vermífugo'),
                'start_date' => '2026-07-01',
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

    private function medicationId(string $name): int
    {
        return (int) DB::table('medications')->where('name', $name)->value('id');
    }
}
