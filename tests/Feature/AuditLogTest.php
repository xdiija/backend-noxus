<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Volunteer;
use Database\Seeders\FosteringMenusSeeder;
use Database\Seeders\SystemMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 7 — audit trail.
 *
 * audit_logs rows are written by the Auditable model concern on
 * created/updated/deleted, only for actions performed by an authenticated
 * user (seeders stay silent). The API surface is read-only (GET /audit-logs),
 * org-scoped like every shelter resource.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(SystemMenusSeeder::class);
        $this->seed(FosteringMenusSeeder::class);
    }

    public function test_create_update_and_delete_write_audit_rows(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $id = $this->actingAs($admin, 'api')
            ->postJson('/api/volunteers', ['name' => 'João', 'status' => true])
            ->json('data.id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/volunteers/{$id}", ['name' => 'João Silva', 'status' => true])
            ->assertOk();

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/volunteers/{$id}")
            ->assertNoContent();

        foreach (['created', 'updated', 'deleted'] as $action) {
            $this->assertDatabaseHas('audit_logs', [
                'organization_id' => $org->id,
                'user_id' => $admin->id,
                'action' => $action,
                'entity' => 'Volunteer',
                'entity_id' => $id,
            ]);
        }
    }

    public function test_unauthenticated_writes_are_not_audited(): void
    {
        [$org] = $this->orgWithAdmin();

        // Seeder-style direct model write, no authenticated user.
        Volunteer::create([
            'organization_id' => $org->id,
            'name' => 'Semeado',
            'status' => 1,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_index_lists_only_own_orgs_trail(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [, $adminB] = $this->orgWithAdmin();

        $this->actingAs($adminA, 'api')
            ->postJson('/api/volunteers', ['name' => 'Nosso', 'status' => true])
            ->assertCreated();

        $this->actingAs($adminB, 'api')
            ->postJson('/api/volunteers', ['name' => 'Deles', 'status' => true])
            ->assertCreated();

        $this->actingAs($adminA, 'api')
            ->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.name', $adminA->name)
            ->assertJsonPath('data.0.action.id', 'created')
            ->assertJsonPath('data.0.entity', 'Volunteer');
    }

    public function test_index_filters_by_entity_action_and_record(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $id = $this->actingAs($admin, 'api')
            ->postJson('/api/volunteers', ['name' => 'João', 'status' => true])
            ->json('data.id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/volunteers/{$id}", ['name' => 'João Silva', 'status' => true])
            ->assertOk();

        $this->actingAs($admin, 'api')
            ->getJson('/api/audit-logs?action=updated')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action.name', 'Atualização');

        $this->actingAs($admin, 'api')
            ->getJson("/api/audit-logs?entity=Volunteer&entity_id={$id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin, 'api')
            ->getJson('/api/audit-logs?entity=Animal')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_trail_has_no_write_surface(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/audit-logs', ['action' => 'created'])
            ->assertStatus(405);
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
}
