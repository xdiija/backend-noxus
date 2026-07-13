<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Volunteer;
use Database\Seeders\FosteringMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 5 — volunteers.
 *
 * Volunteers are org-scoped operational data (suppliers pattern): reads
 * filtered by organization, organization_id set server-side on create, and a
 * status toggle. The Admin role is granted full access so the permission gate
 * lets these requests through while exercising the route wiring.
 */
class VolunteerTest extends TestCase
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

    public function test_store_creates_volunteer_scoped_to_the_users_org(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/volunteers', [
                'name' => 'João Voluntário',
                'phone' => '(11) 99999-8888',
                'email' => 'joao@example.com',
                'role' => 'Motorista de resgate',
                'status' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'João Voluntário');

        $this->assertDatabaseHas('volunteers', [
            'name' => 'João Voluntário',
            'organization_id' => $org->id,
            'role' => 'Motorista de resgate',
        ]);
    }

    public function test_organization_id_from_request_body_is_ignored(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/volunteers', [
                'name' => 'Maria',
                'status' => true,
                'organization_id' => $orgB->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('volunteers', [
            'name' => 'Maria',
            'organization_id' => $org->id,
        ]);
    }

    public function test_index_lists_only_own_orgs_volunteers(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->makeVolunteer($org, 'Minha Voluntária');
        $this->makeVolunteer($orgB, 'Voluntária Alheia');

        $this->actingAs($admin, 'api')
            ->getJson('/api/volunteers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Minha Voluntária');
    }

    public function test_update_edits_volunteer(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $volunteer = $this->makeVolunteer($org, 'João');

        $this->actingAs($admin, 'api')
            ->putJson("/api/volunteers/{$volunteer->id}", [
                'name' => 'João Atualizado',
                'role' => 'Lar temporário',
                'status' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('volunteers', [
            'id' => $volunteer->id,
            'name' => 'João Atualizado',
            'role' => 'Lar temporário',
        ]);
    }

    public function test_change_status_toggles_the_active_flag(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $volunteer = $this->makeVolunteer($org, 'João');

        $this->actingAs($admin, 'api')
            ->putJson("/api/volunteers/{$volunteer->id}/status")
            ->assertOk();

        $this->assertDatabaseHas('volunteers', ['id' => $volunteer->id, 'status' => 0]);
    }

    public function test_destroy_soft_deletes_volunteer(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $volunteer = $this->makeVolunteer($org, 'João');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/volunteers/{$volunteer->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('volunteers', ['id' => $volunteer->id]);
    }

    public function test_volunteer_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeVolunteer($orgB, 'Alheia');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/volunteers/{$theirs->id}")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->putJson("/api/volunteers/{$theirs->id}", ['name' => 'Hijack', 'status' => true])
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->deleteJson("/api/volunteers/{$theirs->id}")
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

    private function makeVolunteer(Organization $org, string $name): Volunteer
    {
        return Volunteer::create([
            'organization_id' => $org->id,
            'name' => $name,
            'status' => 1,
        ]);
    }
}
