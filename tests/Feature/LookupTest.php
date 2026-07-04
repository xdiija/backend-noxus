<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\BreedsTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Database\Seeders\TagsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 1 — animal reference data (lookups).
 *
 * Covers the testable outcomes from the roadmap: the list/select endpoints
 * return the seeded system defaults (+ each org's own customs), global
 * reference data is shared, and configurable lookups are org-scoped — a tenant
 * can create its own rows but can never see, edit, or delete another org's
 * rows or the shared system defaults.
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
    }

    // ---- Global reference data (species / breeds) ------------------------

    public function test_species_returns_seeded_defaults(): void
    {
        $this->seed(SpeciesTableSeeder::class);
        [, $admin] = $this->orgWithAdmin('Org A');

        $this->actingAs($admin, 'api')
            ->getJson('/api/species')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cachorro'])
            ->assertJsonFragment(['name' => 'Gato']);
    }

    public function test_breeds_can_be_filtered_by_species(): void
    {
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(BreedsTableSeeder::class);
        [, $admin] = $this->orgWithAdmin('Org A');

        $dogId = DB::table('species')->where('name', 'Cachorro')->value('id');

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/breeds?species_id={$dogId}")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Labrador']);

        // Every returned breed belongs to the requested species.
        foreach ($response->json('data') as $breed) {
            $this->assertSame($dogId, $breed['species_id']);
        }
    }

    // ---- Configurable lookups: defaults visible to every org -------------

    public function test_animal_sizes_returns_system_defaults(): void
    {
        $this->seed(AnimalSizesTableSeeder::class);
        [, $admin] = $this->orgWithAdmin('Org A');

        $this->actingAs($admin, 'api')
            ->getJson('/api/animal-sizes')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Pequeno', 'is_custom' => false])
            ->assertJsonFragment(['name' => 'Gigante', 'is_custom' => false]);
    }

    public function test_animal_statuses_returns_system_defaults(): void
    {
        $this->seed(AnimalStatusesTableSeeder::class);
        [, $admin] = $this->orgWithAdmin('Org A');

        $this->actingAs($admin, 'api')
            ->getJson('/api/animal-statuses')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Disponível', 'is_custom' => false]);
    }

    public function test_tags_returns_system_defaults(): void
    {
        $this->seed(TagsTableSeeder::class);
        [, $admin] = $this->orgWithAdmin('Org A');

        $this->actingAs($admin, 'api')
            ->getJson('/api/tags')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Idoso', 'is_custom' => false]);
    }

    // ---- Configurable lookups: create is org-scoped ----------------------

    public function test_creating_a_tag_sets_organization_from_authenticated_user(): void
    {
        [$org, $admin] = $this->orgWithAdmin('Org A');

        $this->actingAs($admin, 'api')
            ->postJson('/api/tags', ['name' => 'Medroso'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Medroso', 'is_custom' => true]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Medroso',
            'organization_id' => $org->id,
        ]);
    }

    public function test_org_custom_lookups_do_not_leak_across_organizations(): void
    {
        $this->seed(TagsTableSeeder::class);
        [, $adminA] = $this->orgWithAdmin('Org A');
        [, $adminB] = $this->orgWithAdmin('Org B');

        // Org A creates a custom tag.
        $tagId = $this->actingAs($adminA, 'api')
            ->postJson('/api/tags', ['name' => 'Exclusivo A'])
            ->assertCreated()
            ->json('data.id');

        // Org B sees the shared defaults but not Org A's custom tag.
        $this->actingAs($adminB, 'api')
            ->getJson('/api/tags')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Idoso'])
            ->assertJsonMissing(['name' => 'Exclusivo A']);

        // Org B can't reach Org A's row directly either.
        $this->actingAs($adminB, 'api')
            ->getJson("/api/tags/{$tagId}")
            ->assertNotFound();
    }

    public function test_org_cannot_update_or_delete_another_orgs_custom_lookup(): void
    {
        [, $adminA] = $this->orgWithAdmin('Org A');
        [, $adminB] = $this->orgWithAdmin('Org B');

        $tagId = $this->actingAs($adminA, 'api')
            ->postJson('/api/tags', ['name' => 'Só do A'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($adminB, 'api')
            ->putJson("/api/tags/{$tagId}", ['name' => 'Invadido'])
            ->assertNotFound();

        $this->actingAs($adminB, 'api')
            ->deleteJson("/api/tags/{$tagId}")
            ->assertNotFound();

        $this->assertDatabaseHas('tags', ['id' => $tagId, 'name' => 'Só do A']);
    }

    // ---- Configurable lookups: system defaults are read-only -------------

    public function test_system_default_lookup_cannot_be_edited_or_deleted(): void
    {
        $this->seed(AnimalSizesTableSeeder::class);
        [, $admin] = $this->orgWithAdmin('Org A');

        $defaultId = DB::table('animal_sizes')->whereNull('organization_id')->value('id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/animal-sizes/{$defaultId}", ['name' => 'Alterado'])
            ->assertNotFound();

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animal-sizes/{$defaultId}")
            ->assertNotFound();

        $this->assertDatabaseHas('animal_sizes', ['id' => $defaultId, 'organization_id' => null]);
    }

    // ---- animal_statuses: at most one org default ------------------------

    public function test_marking_a_status_default_clears_the_previous_org_default(): void
    {
        [$org, $admin] = $this->orgWithAdmin('Org A');

        $firstId = $this->actingAs($admin, 'api')
            ->postJson('/api/animal-statuses', ['name' => 'Fase 1', 'is_default' => true])
            ->assertCreated()
            ->json('data.id');

        $secondId = $this->actingAs($admin, 'api')
            ->postJson('/api/animal-statuses', ['name' => 'Fase 2', 'is_default' => true])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('animal_statuses', ['id' => $firstId, 'is_default' => false]);
        $this->assertDatabaseHas('animal_statuses', ['id' => $secondId, 'is_default' => true]);
        $this->assertSame(
            1,
            DB::table('animal_statuses')
                ->where('organization_id', $org->id)
                ->where('is_default', true)
                ->count()
        );
    }

    // ---- helpers ---------------------------------------------------------

    /**
     * @return array{0: Organization, 1: User}
     */
    private function orgWithAdmin(string $name): array
    {
        $org = Organization::create([
            'name' => $name,
            'tax_id' => (string) Str::uuid(),
        ]);

        $admin = User::create([
            'organization_id' => $org->id,
            'name' => 'Admin '.$name,
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'role_id' => Role::ADMIN_ROLE,
        ]);

        return [$org, $admin];
    }
}
