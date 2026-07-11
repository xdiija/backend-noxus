<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\BreedsTableSeeder;
use Database\Seeders\ShelterMenusSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Database\Seeders\TagsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 1 — animal reference data (lookups).
 *
 * `species` is global read-only reference. `breeds`, `animal_sizes`,
 * `animal_statuses` and `tags` are Noxus-managed global lookups (ADR-0008), all
 * permission-gated via `check.permissions`. Every role is granted `view`
 * (ShelterMenusSeeder), so any authenticated user reads the same shared list;
 * `create`/`update` are granted to no role, so only Noxus (who bypasses the
 * check) writes — other roles get 403. All rows carry `organization_id = NULL`.
 */
class LookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // users.role_id FK + PermissionService expect these roles to exist.
        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        // The lookup menus + view grants: reads are gated by
        // check.permissions:<route>,view, so every role needs the can_view grant
        // (write grants stay 0 → Noxus-only). Seeded after the roles exist.
        $this->seed(ShelterMenusSeeder::class);
    }

    // ---- Reads: any authenticated user sees the shared list --------------

    public function test_species_returns_seeded_defaults(): void
    {
        $this->seed(SpeciesTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $this->actingAs($user, 'api')
            ->getJson('/api/species')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cachorro'])
            ->assertJsonFragment(['name' => 'Gato']);
    }

    public function test_breeds_can_be_filtered_by_species(): void
    {
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(BreedsTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $dogId = DB::table('species')->where('name', 'Cachorro')->value('id');

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/breeds?species_id={$dogId}")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Labrador']);

        foreach ($response->json('data') as $breed) {
            $this->assertSame($dogId, $breed['species_id']);
        }
    }

    public function test_animal_sizes_returns_seeded_defaults(): void
    {
        $this->seed(AnimalSizesTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $this->actingAs($user, 'api')
            ->getJson('/api/animal-sizes')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Pequeno', 'is_custom' => false])
            ->assertJsonFragment(['name' => 'Gigante', 'is_custom' => false]);
    }

    public function test_animal_statuses_returns_seeded_defaults(): void
    {
        $this->seed(AnimalStatusesTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $this->actingAs($user, 'api')
            ->getJson('/api/animal-statuses')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Disponível', 'is_custom' => false]);
    }

    public function test_tags_returns_seeded_defaults(): void
    {
        $this->seed(TagsTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $this->actingAs($user, 'api')
            ->getJson('/api/tags')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Idoso', 'is_custom' => false]);
    }

    // ---- Noxus writes: creates global rows visible to every org ----------

    public function test_noxus_creates_a_global_tag_visible_to_all_orgs(): void
    {
        $noxus = $this->userWithRole(Role::NOXUS_ROLE);
        $otherOrgUser = $this->userWithRole(Role::ADMIN_ROLE);

        $this->actingAs($noxus, 'api')
            ->postJson('/api/tags', ['name' => 'Medroso'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Medroso', 'is_custom' => false]);

        // Stored as a shared global row (no organization_id).
        $this->assertDatabaseHas('tags', ['name' => 'Medroso', 'organization_id' => null]);

        // A user from a different org sees it.
        $this->actingAs($otherOrgUser, 'api')
            ->getJson('/api/tags')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Medroso']);
    }

    public function test_noxus_creates_a_global_breed(): void
    {
        $this->seed(SpeciesTableSeeder::class);
        $noxus = $this->userWithRole(Role::NOXUS_ROLE);
        $dogId = DB::table('species')->where('name', 'Cachorro')->value('id');

        $this->actingAs($noxus, 'api')
            ->postJson('/api/breeds', ['species_id' => $dogId, 'name' => 'Caramelo'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Caramelo', 'is_custom' => false]);

        $this->assertDatabaseHas('breeds', [
            'name' => 'Caramelo',
            'species_id' => $dogId,
            'organization_id' => null,
        ]);
    }

    public function test_noxus_can_edit_and_delete_a_seeded_default(): void
    {
        $this->seed(AnimalSizesTableSeeder::class);
        $noxus = $this->userWithRole(Role::NOXUS_ROLE);

        $ids = DB::table('animal_sizes')->whereNull('organization_id')->pluck('id');
        $editId = $ids->first();
        $deleteId = $ids->last();

        $this->actingAs($noxus, 'api')
            ->putJson("/api/animal-sizes/{$editId}", ['name' => 'Miúdo', 'sort_order' => 1])
            ->assertOk();
        $this->assertDatabaseHas('animal_sizes', ['id' => $editId, 'name' => 'Miúdo']);

        $this->actingAs($noxus, 'api')
            ->deleteJson("/api/animal-sizes/{$deleteId}")
            ->assertNoContent();
        $this->assertDatabaseMissing('animal_sizes', ['id' => $deleteId]);
    }

    public function test_marking_a_status_default_clears_the_previous_default(): void
    {
        $noxus = $this->userWithRole(Role::NOXUS_ROLE);

        $firstId = $this->actingAs($noxus, 'api')
            ->postJson('/api/animal-statuses', ['name' => 'Fase 1', 'is_default' => true])
            ->assertCreated()
            ->json('data.id');

        $secondId = $this->actingAs($noxus, 'api')
            ->postJson('/api/animal-statuses', ['name' => 'Fase 2', 'is_default' => true])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('animal_statuses', ['id' => $firstId, 'is_default' => false]);
        $this->assertDatabaseHas('animal_statuses', ['id' => $secondId, 'is_default' => true]);
        $this->assertSame(
            1,
            DB::table('animal_statuses')->whereNull('organization_id')->where('is_default', true)->count()
        );
    }

    // ---- Non-Noxus users are read-only (403 on writes) -------------------

    public function test_non_noxus_cannot_create_a_lookup(): void
    {
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $this->actingAs($user, 'api')
            ->postJson('/api/tags', ['name' => 'Proibido'])
            ->assertForbidden();

        $this->assertDatabaseMissing('tags', ['name' => 'Proibido']);
    }

    public function test_non_noxus_cannot_edit_or_delete_a_lookup(): void
    {
        $this->seed(AnimalSizesTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);

        $defaultId = DB::table('animal_sizes')->whereNull('organization_id')->value('id');

        $this->actingAs($user, 'api')
            ->putJson("/api/animal-sizes/{$defaultId}", ['name' => 'Alterado', 'sort_order' => 1])
            ->assertForbidden();

        $this->actingAs($user, 'api')
            ->deleteJson("/api/animal-sizes/{$defaultId}")
            ->assertForbidden();

        $this->assertDatabaseHas('animal_sizes', ['id' => $defaultId, 'name' => 'Pequeno']);
    }

    public function test_non_noxus_cannot_create_a_breed(): void
    {
        $this->seed(SpeciesTableSeeder::class);
        $user = $this->userWithRole(Role::ADMIN_ROLE);
        $dogId = DB::table('species')->where('name', 'Cachorro')->value('id');

        $this->actingAs($user, 'api')
            ->postJson('/api/breeds', ['species_id' => $dogId, 'name' => 'Proibida'])
            ->assertForbidden();

        $this->assertDatabaseMissing('breeds', ['name' => 'Proibida']);
    }

    // ---- helpers ---------------------------------------------------------

    private function userWithRole(int $roleId): User
    {
        $org = Organization::create([
            'name' => 'Org '.Str::random(6),
            'tax_id' => (string) Str::uuid(),
        ]);

        return User::create([
            'organization_id' => $org->id,
            'name' => 'User '.Str::random(6),
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'role_id' => $roleId,
        ]);
    }
}
