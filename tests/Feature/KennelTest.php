<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalKennelStay;
use App\Models\Kennel;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\KennelsMenusSeeder;
use Database\Seeders\KennelStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 8 — kennels (physical housing).
 *
 * Kennels are org-scoped operational data (suppliers pattern). Their status is
 * the kennel_statuses configurable lookup; occupancy is derived from open
 * stays, never stored. A kennel housing animals can't be deleted (kennels.md).
 */
class KennelTest extends TestCase
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
        $this->seed(KennelsMenusSeeder::class);

        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
        $this->seed(KennelStatusesTableSeeder::class);
    }

    public function test_store_creates_kennel_with_default_status(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/kennels', ['name' => 'A1', 'capacity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.name', 'A1')
            // No status sent → the is_default lookup row ("Disponível").
            ->assertJsonPath('data.status.name', 'Disponível')
            ->assertJsonPath('data.open_stays_count', 0);
    }

    public function test_store_rejects_duplicate_name_in_same_org(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $this->makeKennel($org, 'A1');

        $this->actingAs($admin, 'api')
            ->postJson('/api/kennels', ['name' => 'A1', 'capacity' => 1])
            ->assertStatus(400)
            ->assertJsonPath('errors.name.0', 'Já existe um canil com este nome.');
    }

    public function test_same_name_is_allowed_in_another_org(): void
    {
        [$orgB] = $this->orgWithAdmin();
        [, $adminA] = $this->orgWithAdmin();

        $this->makeKennel($orgB, 'A1');

        $this->actingAs($adminA, 'api')
            ->postJson('/api/kennels', ['name' => 'A1', 'capacity' => 1])
            ->assertCreated();
    }

    public function test_index_lists_only_own_orgs_kennels_with_occupancy(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $kennel = $this->makeKennel($org, 'A1');
        $this->makeKennel($orgB, 'Alheio');
        $this->openStay($kennel, $this->makeAnimal($org, 'Rex'));

        $this->actingAs($admin, 'api')
            ->getJson('/api/kennels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'A1')
            ->assertJsonPath('data.0.open_stays_count', 1);
    }

    public function test_update_changes_status(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}", [
                'name' => 'A1',
                'capacity' => 1,
                'status_id' => $this->kennelStatusId('Em Manutenção'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status.name', 'Em Manutenção');
    }

    public function test_destroy_is_blocked_while_kennel_has_open_stays(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $this->openStay($kennel, $this->makeAnimal($org, 'Rex'));

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/kennels/{$kennel->id}")
            ->assertStatus(400)
            ->assertJsonPath('errors.id.0', 'Não é possível excluir um canil com animais alocados.');
    }

    public function test_destroy_soft_deletes_an_empty_kennel(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/kennels/{$kennel->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('kennels', ['id' => $kennel->id]);
    }

    public function test_another_orgs_kennel_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeKennel($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')->getJson("/api/kennels/{$theirs->id}")->assertNotFound();
        $this->actingAs($adminA, 'api')->deleteJson("/api/kennels/{$theirs->id}")->assertNotFound();
    }

    public function test_kennel_statuses_lookup_lists_system_defaults(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->getJson('/api/kennel-statuses')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Disponível')
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonPath('data.0.is_custom', false);
    }

    public function test_form_and_animal_options_only_require_the_kennels_permission(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $this->makeAnimal($org, 'Rex');

        // This setup seeds no lookup menu grants, so the raw lookup route is
        // denied…
        $this->actingAs($admin, 'api')
            ->getJson('/api/animal-sizes')
            ->assertForbidden();

        // …while the kennels-gated bundle still serves the form lookups.
        $this->actingAs($admin, 'api')
            ->getJson('/api/kennels/form-options')
            ->assertOk()
            ->assertJsonStructure(['data' => ['statuses', 'sizes']])
            ->assertJsonFragment(['name' => 'Médio']);

        $this->actingAs($admin, 'api')
            ->getJson('/api/kennels/animal-options?filter=Re')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Rex']);
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

    private function makeKennel(Organization $org, string $name, int $capacity = 1): Kennel
    {
        return Kennel::create([
            'organization_id' => $org->id,
            'name' => $name,
            'capacity' => $capacity,
            'status_id' => $this->kennelStatusId('Disponível'),
        ]);
    }

    private function makeAnimal(Organization $org, string $name): Animal
    {
        return Animal::create([
            'organization_id' => $org->id,
            'name' => $name,
            'species_id' => (int) DB::table('species')->where('name', 'Cachorro')->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => (int) DB::table('animal_statuses')->where('name', 'Disponível')->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);
    }

    private function kennelStatusId(string $name): int
    {
        return (int) DB::table('kennel_statuses')->where('name', $name)->value('id');
    }

    private function openStay(Kennel $kennel, Animal $animal): AnimalKennelStay
    {
        return AnimalKennelStay::create([
            'animal_id' => $animal->id,
            'kennel_id' => $kennel->id,
            'entry_date' => '2026-06-01',
        ]);
    }
}
