<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\FosterHome;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\FosteringMenusSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 5 — foster homes.
 *
 * Foster homes are org-scoped operational data (suppliers pattern). The list
 * and show endpoints also expose how many stays are currently open
 * (open_placements_count) so the UI can show occupancy against `capacity`.
 */
class FosterHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(FosteringMenusSeeder::class);
    }

    public function test_store_creates_foster_home_scoped_to_the_users_org(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/foster-homes', [
                'responsible_name' => 'Casa da Ana',
                'phone' => '(11) 98888-7777',
                'address' => 'Rua das Flores, 10',
                'capacity' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.responsible_name', 'Casa da Ana');

        $this->assertDatabaseHas('foster_homes', [
            'responsible_name' => 'Casa da Ana',
            'organization_id' => $org->id,
            'capacity' => 3,
        ]);
    }

    public function test_index_lists_only_own_orgs_foster_homes_with_open_count(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->makeFosterHome($org, 'Casa da Ana');
        $this->makeFosterHome($orgB, 'Casa Alheia');

        $this->actingAs($admin, 'api')
            ->getJson('/api/foster-homes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.responsible_name', 'Casa da Ana')
            ->assertJsonPath('data.0.open_placements_count', 0);
    }

    public function test_update_edits_foster_home(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org, 'Casa da Ana');

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}", [
                'responsible_name' => 'Casa da Ana Paula',
                'capacity' => 5,
            ])
            ->assertOk();

        $this->assertDatabaseHas('foster_homes', [
            'id' => $home->id,
            'responsible_name' => 'Casa da Ana Paula',
            'capacity' => 5,
        ]);
    }

    public function test_destroy_soft_deletes_foster_home(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org, 'Casa da Ana');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/foster-homes/{$home->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('foster_homes', ['id' => $home->id]);
    }

    public function test_foster_home_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeFosterHome($orgB, 'Casa Alheia');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/foster-homes/{$theirs->id}")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->putJson("/api/foster-homes/{$theirs->id}", ['responsible_name' => 'Hijack'])
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->deleteJson("/api/foster-homes/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_animal_options_only_requires_the_foster_homes_permission(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        // Animal reference data isn't part of this suite's setup; seed just
        // enough to create a pickable animal.
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);

        Animal::create([
            'organization_id' => $org->id,
            'name' => 'Rex',
            'species_id' => (int) DB::table('species')->where('name', 'Cachorro')->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => (int) DB::table('animal_statuses')->where('name', 'Disponível')->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);

        // Only the fostering menus are granted here, so the raw animals route
        // is denied…
        $this->actingAs($admin, 'api')
            ->getJson('/api/animals')
            ->assertForbidden();

        // …while the foster-homes-gated picker still serves it.
        $this->actingAs($admin, 'api')
            ->getJson('/api/foster-homes/animal-options?filter=Re')
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

    private function makeFosterHome(Organization $org, string $name, ?int $capacity = null): FosterHome
    {
        return FosterHome::create([
            'organization_id' => $org->id,
            'responsible_name' => $name,
            'capacity' => $capacity,
        ]);
    }
}
