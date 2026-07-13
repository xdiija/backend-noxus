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
 * Module 4 — surgeries.
 *
 * Surgical procedures nested under an animal. The health.md invariant: a
 * castration surgery is the clinical record and animals.neutered/neuter_date
 * are the queryable summary — SurgeryService syncs them when the request
 * carries is_castration = true.
 */
class SurgeryTest extends TestCase
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

    public function test_store_creates_surgery_under_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $id = $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/surgeries", [
                'surgery_date' => '2026-07-05',
                'procedure' => 'Remoção de tumor',
                'veterinarian' => 'Dr. Paulo',
                'cost' => 350.50,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('surgeries', [
            'id' => $id,
            'animal_id' => $animal->id,
            'organization_id' => $org->id,
            'procedure' => 'Remoção de tumor',
            'cost' => '350.50',
        ]);
    }

    public function test_castration_marks_the_animal_as_neutered(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/surgeries", [
                'surgery_date' => '2026-07-05',
                'procedure' => 'Castração',
                'is_castration' => true,
            ])
            ->assertCreated();

        $animal->refresh();
        $this->assertTrue($animal->neutered);
        $this->assertSame('2026-07-05', $animal->neuter_date->toDateString());
    }

    public function test_non_castration_surgery_does_not_touch_neutered(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $this->actingAs($admin, 'api')
            ->postJson("/api/animals/{$animal->id}/surgeries", [
                'surgery_date' => '2026-07-05',
                'procedure' => 'Sutura de ferimento',
            ])
            ->assertCreated();

        $animal->refresh();
        $this->assertFalse($animal->neutered);
        $this->assertNull($animal->neuter_date);
    }

    public function test_update_with_castration_flag_syncs_the_new_date(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $surgery = $animal->surgeries()->create([
            'organization_id' => $org->id,
            'surgery_date' => '2026-07-05',
            'procedure' => 'Castração',
        ]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}/surgeries/{$surgery->id}", [
                'surgery_date' => '2026-07-08',
                'procedure' => 'Castração',
                'is_castration' => true,
            ])
            ->assertOk();

        $animal->refresh();
        $this->assertTrue($animal->neutered);
        $this->assertSame('2026-07-08', $animal->neuter_date->toDateString());
    }

    public function test_destroy_soft_deletes_surgery(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org);

        $surgery = $animal->surgeries()->create([
            'organization_id' => $org->id,
            'surgery_date' => '2026-07-05',
            'procedure' => 'Sutura',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animals/{$animal->id}/surgeries/{$surgery->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('surgeries', ['id' => $surgery->id]);
    }

    public function test_surgery_on_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAnimal = $this->makeAnimal($orgB);

        $this->actingAs($adminA, 'api')
            ->postJson("/api/animals/{$theirAnimal->id}/surgeries", [
                'surgery_date' => '2026-07-05',
                'procedure' => 'Castração',
                'is_castration' => true,
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
