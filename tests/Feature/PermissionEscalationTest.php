<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Roles, menus and role_menu grants are GLOBAL tables shared by every
 * organization, so letting a tenant admin edit them would change permissions
 * across all orgs (and let admins grant themselves anything). These tests pin
 * the two guards that prevent that escalation:
 *
 * 1. Role/menu management is Noxus-only: the /roles and /menus menus are
 *    exclusive_noxus, and User::hasPermission ignores role_menu grants on
 *    exclusive_noxus menus — even a rogue grant doesn't open the route.
 * 2. Only Noxus users may assign the Noxus role (it bypasses CheckPermissions
 *    entirely, org-wide and cross-tenant).
 */
class PermissionEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        // Explicit ids for the same reason as the roles above (MySQL keeps the
        // auto-increment across RefreshDatabase transactions, so re-running
        // MenusTableSeeder would break its hardcoded parent_id).
        DB::table('menus')->insert([
            ['id' => 1, 'name' => 'Administrativo', 'route' => null, 'icon' => 'admin_panel_settings', 'parent_id' => null, 'order' => 1, 'status' => 1, 'exclusive_noxus' => 0],
            ['id' => 2, 'name' => 'Usuários', 'route' => '/users', 'icon' => 'manage_accounts', 'parent_id' => 1, 'order' => 2, 'status' => 1, 'exclusive_noxus' => 0],
            ['id' => 3, 'name' => 'Perfis', 'route' => '/roles', 'icon' => 'groups', 'parent_id' => 1, 'order' => 1, 'status' => 1, 'exclusive_noxus' => 1],
        ]);

        $this->seed(AdminPermissionsSeeder::class);

        // hasPermission() caches per user+route+action; flush so grants
        // inserted inside a test are seen immediately.
        User::forgetUserPermissionsCache();
    }

    public function test_admin_cannot_list_roles_even_with_a_rogue_grant(): void
    {
        [, $admin] = $this->orgWithUser(Role::ADMIN_ROLE);

        // Simulate a leftover / maliciously inserted grant on the Noxus-only
        // menu: exclusive_noxus must win over role_menu.
        $rolesMenuId = DB::table('menus')->where('route', '/roles')->value('id');
        DB::table('role_menu')->insert([
            'role_id' => Role::ADMIN_ROLE,
            'menu_id' => $rolesMenuId,
            'can_view' => 1,
            'can_create' => 1,
            'can_update' => 1,
        ]);

        $this->actingAs($admin, 'api')
            ->getJson('/api/roles')
            ->assertForbidden();
    }

    public function test_admin_cannot_update_a_role(): void
    {
        [, $admin] = $this->orgWithUser(Role::ADMIN_ROLE);

        $this->actingAs($admin, 'api')
            ->putJson('/api/roles/'.Role::ADMIN_ROLE, ['name' => 'Admin Turbinado'])
            ->assertForbidden();
    }

    public function test_admin_cannot_assign_the_noxus_role_to_a_user(): void
    {
        [, $admin] = $this->orgWithUser(Role::ADMIN_ROLE);

        // The project's exception handler maps ValidationException to HTTP 400.
        $this->actingAs($admin, 'api')
            ->postJson('/api/users', $this->userPayload(Role::NOXUS_ROLE))
            ->assertStatus(400)
            ->assertJsonPath('errors.role.0', 'O perfil informado é inválido.');
    }

    public function test_noxus_can_assign_the_noxus_role(): void
    {
        [, $noxus] = $this->orgWithUser(Role::NOXUS_ROLE);

        $this->actingAs($noxus, 'api')
            ->postJson('/api/users', $this->userPayload(Role::NOXUS_ROLE))
            ->assertCreated();
    }

    // ---- helpers ---------------------------------------------------------

    /**
     * @return array{0: Organization, 1: User}
     */
    private function orgWithUser(int $roleId): array
    {
        $org = Organization::create([
            'name' => 'Org '.Str::random(6),
            'tax_id' => (string) Str::uuid(),
        ]);

        $user = User::create([
            'organization_id' => $org->id,
            'name' => 'User '.Str::random(6),
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'role_id' => $roleId,
        ]);

        return [$org, $user];
    }

    private function userPayload(int $roleId): array
    {
        return [
            'name' => 'Novo Usuário',
            'email' => Str::random(8).'@example.com',
            'password' => 'secret123',
            'status' => 1,
            'role' => $roleId,
        ];
    }
}
