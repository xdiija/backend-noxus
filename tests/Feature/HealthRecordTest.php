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
 * Module 4 — health records.
 *
 * Generic clinical events nested under an animal. Org scoping is enforced in
 * HealthRecordService via the parent animal; organization_id is set from the
 * animal, never from request input.
 */
class HealthRecordTest extends TestCase
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

    public function test_store_creates_health_record_under_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $id = $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/health-records", [
                'type' => 'Exame',
                'description' => 'Hemograma completo',
                'event_date' => '2026-07-01',
                'veterinarian' => 'Dra. Ana',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('health_records', [
            'id' => $id,
            'animal_id' => $animal->id,
            'organization_id' => $org->id,
            'type' => 'Exame',
        ]);
    }

    public function test_index_lists_health_records_of_the_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $animal->healthRecords()->create([
            'organization_id' => $org->id,
            'type' => 'Vermifugação',
            'event_date' => '2026-06-15',
        ]);

        $this->actingAs($admin, 'api')
            ->getJson("/api/animals/{$animal->id}/health-records")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['type' => 'Vermifugação']);
    }

    public function test_update_changes_health_record(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $record = $animal->healthRecords()->create([
            'organization_id' => $org->id,
            'type' => 'Observação',
            'event_date' => '2026-06-15',
        ]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}/health-records/{$record->id}", [
                'type' => 'Observação',
                'event_date' => '2026-06-15',
                'notes' => 'Apetite normalizado',
            ])
            ->assertOk();

        $this->assertDatabaseHas('health_records', [
            'id' => $record->id,
            'notes' => 'Apetite normalizado',
        ]);
    }

    public function test_destroy_soft_deletes_health_record(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $record = $animal->healthRecords()->create([
            'organization_id' => $org->id,
            'type' => 'Exame',
            'event_date' => '2026-06-15',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animals/{$animal->id}/health-records/{$record->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('health_records', ['id' => $record->id]);
    }

    public function test_store_requires_type_and_event_date(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/health-records", [])
            ->assertStatus(400)
            ->assertJsonValidationErrors(['type', 'event_date']);
    }

    public function test_health_record_on_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAnimal = $this->makeAnimal($orgB);

        $this->actingAs($adminA, 'api')
            ->postJson("/api/animals/{$theirAnimal->id}/health-records", [
                'type' => 'Exame',
                'event_date' => '2026-07-01',
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
