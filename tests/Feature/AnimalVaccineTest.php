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
use Database\Seeders\VaccinesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 4 — applied vaccine doses (animal_vaccines).
 *
 * Doses are nested under an animal (org scoping via the parent in
 * AnimalVaccineService); the `vaccines` catalog itself is global reference
 * data, read-only and shared by all orgs (like species).
 */
class AnimalVaccineTest extends TestCase
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
        $this->seed(VaccinesTableSeeder::class);
    }

    public function test_vaccine_catalog_lists_seeded_defaults(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->getJson('/api/vaccines')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Antirrábica']);
    }

    public function test_store_creates_applied_dose_under_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $id = $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/vaccines", [
                'vaccine_id' => $this->vaccineId('V10 (Déctupla)'),
                'application_date' => '2026-07-01',
                'dose' => '1ª dose',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('animal_vaccines', [
            'id' => $id,
            'animal_id' => $animal->id,
            'dose' => '1ª dose',
        ]);
    }

    public function test_index_lists_doses_with_nested_vaccine(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $animal->vaccines()->create([
            'vaccine_id' => $this->vaccineId('Antirrábica'),
            'application_date' => '2026-06-15',
        ]);

        $this->actingAs($admin, 'api')
            ->getJson("/api/animals/{$animal->id}/vaccines")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Antirrábica']);
    }

    public function test_destroy_hard_deletes_dose(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $dose = $animal->vaccines()->create([
            'vaccine_id' => $this->vaccineId('Antirrábica'),
            'application_date' => '2026-06-15',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animals/{$animal->id}/vaccines/{$dose->id}")
            ->assertNoContent();

        // Pivot-like log: no soft deletes.
        $this->assertDatabaseMissing('animal_vaccines', ['id' => $dose->id]);
    }

    public function test_store_rejects_unknown_vaccine(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/vaccines", [
                'vaccine_id' => 999999,
                'application_date' => '2026-07-01',
            ])
            ->assertStatus(400)
            ->assertJsonValidationErrors(['vaccine_id']);
    }

    public function test_dose_on_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAnimal = $this->makeAnimal($orgB);

        $this->actingAs($adminA, 'api')
            ->postJson("/api/animals/{$theirAnimal->id}/vaccines", [
                'vaccine_id' => $this->vaccineId('Antirrábica'),
                'application_date' => '2026-07-01',
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

    private function vaccineId(string $name): int
    {
        return (int) DB::table('vaccines')->where('name', $name)->value('id');
    }
}
