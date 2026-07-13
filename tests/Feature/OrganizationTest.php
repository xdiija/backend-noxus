<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\OrganizationMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Organizations CRUD — tenant management, Noxus-only.
 *
 * The /organizations menu is exclusive_noxus with no role_menu grants, so
 * every non-Noxus role is blocked by check.permissions (even a mistakenly
 * granted role: hasPermission() refuses exclusive_noxus menus). The Service
 * is deliberately not org-scoped: Noxus manages all tenants.
 */
class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        // Upserts the "Administrativo" container + the Organizações menu.
        $this->seed(OrganizationMenusSeeder::class);
    }

    public function test_noxus_user_can_create_an_organization(): void
    {
        $noxus = $this->makeUser(Role::NOXUS_ROLE);

        $id = $this->actingAs($noxus, 'api')
            ->postJson('/api/organizations', [
                'name' => 'Abrigo Novo',
                'legal_name' => 'Abrigo Novo Proteção Animal',
                'tax_id' => '11.444.777/0001-61',
                'email' => 'contato@abrigonovo.org',
                'phone' => '(11) 98888-7777',
                'city' => 'Campinas',
                'state' => 'SP',
            ])
            ->assertCreated()
            ->json('data.id');

        // Masks are stripped before persisting.
        $this->assertDatabaseHas('organizations', [
            'id' => $id,
            'name' => 'Abrigo Novo',
            'tax_id' => '11444777000161',
            'phone' => '11988887777',
        ]);
    }

    public function test_noxus_user_can_list_and_filter_organizations(): void
    {
        $noxus = $this->makeUser(Role::NOXUS_ROLE);

        Organization::create(['name' => 'Abrigo Amigo', 'tax_id' => '11444777000161']);
        Organization::create(['name' => 'Lar dos Bichos', 'tax_id' => '11222333000181']);

        $this->actingAs($noxus, 'api')
            ->getJson('/api/organizations?filter=Amigo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Abrigo Amigo']);
    }

    public function test_noxus_user_can_update_an_organization(): void
    {
        $noxus = $this->makeUser(Role::NOXUS_ROLE);
        $org = Organization::create(['name' => 'Abrigo Amigo']);

        $this->actingAs($noxus, 'api')
            ->putJson("/api/organizations/{$org->id}", [
                'name' => 'Abrigo Amigo Renomeado',
                'city' => 'Santos',
                'state' => 'SP',
            ])
            ->assertOk();

        $this->assertDatabaseHas('organizations', [
            'id' => $org->id,
            'name' => 'Abrigo Amigo Renomeado',
            'city' => 'Santos',
        ]);
    }

    public function test_duplicated_tax_id_is_rejected(): void
    {
        $noxus = $this->makeUser(Role::NOXUS_ROLE);
        Organization::create(['name' => 'Abrigo Amigo', 'tax_id' => '11444777000161']);

        $this->actingAs($noxus, 'api')
            ->postJson('/api/organizations', [
                'name' => 'Outro Abrigo',
                'tax_id' => '11.444.777/0001-61',
            ])
            ->assertStatus(400)
            ->assertJsonValidationErrors(['tax_id']);
    }

    public function test_organization_without_users_can_be_deleted(): void
    {
        $noxus = $this->makeUser(Role::NOXUS_ROLE);
        $empty = Organization::create(['name' => 'Abrigo Vazio']);

        $this->actingAs($noxus, 'api')
            ->deleteJson("/api/organizations/{$empty->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('organizations', ['id' => $empty->id]);
    }

    public function test_organization_with_users_cannot_be_deleted(): void
    {
        $noxus = $this->makeUser(Role::NOXUS_ROLE);

        $this->actingAs($noxus, 'api')
            ->deleteJson("/api/organizations/{$noxus->organization_id}")
            ->assertStatus(400);

        $this->assertNotSoftDeleted('organizations', ['id' => $noxus->organization_id]);
    }

    public function test_admin_user_is_blocked(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ROLE);

        $this->actingAs($admin, 'api')
            ->getJson('/api/organizations')
            ->assertForbidden();

        $this->actingAs($admin, 'api')
            ->postJson('/api/organizations', ['name' => 'Tentativa'])
            ->assertForbidden();
    }

    public function test_exclusive_noxus_blocks_even_a_granted_role(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ROLE);

        // Simulate a mistaken grant: hasPermission() must still refuse it
        // because the menu is exclusive_noxus.
        DB::table('role_menu')->insert([
            'role_id' => Role::ADMIN_ROLE,
            'menu_id' => DB::table('menus')->where('route', '/organizations')->value('id'),
            'can_view' => 1,
            'can_create' => 1,
            'can_update' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'api')
            ->getJson('/api/organizations')
            ->assertForbidden();
    }

    public function test_menu_is_hidden_from_non_noxus_users(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ROLE);

        $routes = $this->actingAs($admin, 'api')
            ->getJson('/api/menus/getbyroles')
            ->assertOk()
            ->json();

        $flat = collect($routes)->flatMap(fn ($menu) => array_merge(
            [$menu['route'] ?? null],
            array_column($menu['children'] ?? [], 'route'),
        ));

        $this->assertFalse($flat->contains('/organizations'));
    }

    // ---- helpers ---------------------------------------------------------

    private function makeUser(int $roleId): User
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
