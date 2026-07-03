<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermissions;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Asserts that org-scoped resources never leak across organizations.
 *
 * The permission gate (CheckPermissions) is bypassed on purpose: these tests
 * target tenant isolation, which is enforced in the Service layer independently
 * of role/menu permissions. Auth itself is kept (actingAs) so the Services can
 * resolve the current organization from auth()->user().
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckPermissions::class);

        // Roles must exist for the users.role_id FK and PermissionService, which
        // hardcodes Noxus=1 / Admin=2. Insert with explicit ids: RefreshDatabase
        // rolls back per test but MySQL doesn't reset auto-increment, so relying
        // on insertion order would drift the ids across tests.
        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
            ['id' => Role::ADMIN_ROLE + 1, 'name' => 'Regular', 'status' => 1],
        ]);
    }

    public function test_supplier_index_is_scoped_to_organization(): void
    {
        [$orgA, $adminA] = $this->orgWithAdmin('Org A');
        [$orgB] = $this->orgWithAdmin('Org B');

        $supplierA = $this->makeSupplier($orgA, 'Fornecedor A');
        $supplierB = $this->makeSupplier($orgB, 'Fornecedor B');

        $this->actingAs($adminA, 'api')
            ->getJson('/api/suppliers')
            ->assertOk()
            ->assertJsonFragment(['id' => $supplierA->id])
            ->assertJsonMissing(['id' => $supplierB->id]);
    }

    public function test_supplier_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin('Org A');
        [$orgB] = $this->orgWithAdmin('Org B');

        $supplierB = $this->makeSupplier($orgB, 'Fornecedor B');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/suppliers/{$supplierB->id}")
            ->assertNotFound();
    }

    public function test_user_index_is_scoped_to_organization(): void
    {
        [, $adminA] = $this->orgWithAdmin('Org A');
        [$orgB, $adminB] = $this->orgWithAdmin('Org B');
        $regularB = $this->makeUser($orgB, Role::ADMIN_ROLE + 1, 'regularb@example.com');

        $this->actingAs($adminA, 'api')
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $adminA->id])
            ->assertJsonMissing(['id' => $adminB->id])
            ->assertJsonMissing(['id' => $regularB->id]);
    }

    public function test_user_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin('Org A');
        [, $adminB] = $this->orgWithAdmin('Org B');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/users/{$adminB->id}")
            ->assertNotFound();
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function orgWithAdmin(string $name): array
    {
        $org = Organization::create([
            'name' => $name,
            'tax_id' => (string) Str::uuid(),
        ]);

        $admin = $this->makeUser($org, Role::ADMIN_ROLE, Str::random(8) . '@example.com');

        return [$org, $admin];
    }

    private function makeUser(Organization $org, int $roleId, string $email): User
    {
        return User::create([
            'organization_id' => $org->id,
            'name' => 'User ' . $email,
            'email' => $email,
            'password' => bcrypt('secret123'),
            'status' => 1,
            'role_id' => $roleId,
        ]);
    }

    private function makeSupplier(Organization $org, string $name): Supplier
    {
        return Supplier::create([
            'organization_id' => $org->id,
            'nome_fantasia' => $name,
            'razao_social' => $name . ' LTDA',
            'cnpj' => preg_replace('/\D/', '', (string) Str::uuid()) ?: '00000000000000',
            'status' => 1,
        ]);
    }
}
