<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\FinanceMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 6 — donors.
 *
 * Donors are org-scoped operational data (suppliers pattern): reads filtered
 * by organization and organization_id set server-side on create. The Admin
 * role is granted full access so the permission gate lets these requests
 * through while exercising the route wiring.
 */
class DonorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(FinanceMenusSeeder::class);
    }

    public function test_store_creates_donor_scoped_to_the_users_org(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/donors', [
                'name' => 'Maria Doadora',
                'email' => 'maria@example.com',
                'phone' => '(11) 99999-8888',
                'notes' => 'Doa ração todo mês.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Maria Doadora');

        $this->assertDatabaseHas('donors', [
            'name' => 'Maria Doadora',
            'organization_id' => $org->id,
            'phone' => '11999998888',
        ]);
    }

    public function test_organization_id_from_request_body_is_ignored(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/donors', [
                'name' => 'João',
                'organization_id' => $orgB->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('donors', [
            'name' => 'João',
            'organization_id' => $org->id,
        ]);
    }

    public function test_index_lists_only_own_orgs_donors(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->makeDonor($org, 'Minha Doadora');
        $this->makeDonor($orgB, 'Doadora Alheia');

        $this->actingAs($admin, 'api')
            ->getJson('/api/donors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Minha Doadora');
    }

    public function test_update_edits_donor(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $donor = $this->makeDonor($org, 'Maria');

        $this->actingAs($admin, 'api')
            ->putJson("/api/donors/{$donor->id}", [
                'name' => 'Maria Atualizada',
                'email' => 'nova@example.com',
            ])
            ->assertOk();

        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'name' => 'Maria Atualizada',
            'email' => 'nova@example.com',
        ]);
    }

    public function test_destroy_soft_deletes_donor(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $donor = $this->makeDonor($org, 'Maria');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/donors/{$donor->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('donors', ['id' => $donor->id]);
    }

    public function test_donor_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeDonor($orgB, 'Alheia');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/donors/{$theirs->id}")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->putJson("/api/donors/{$theirs->id}", ['name' => 'Hijack'])
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->deleteJson("/api/donors/{$theirs->id}")
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

    private function makeDonor(Organization $org, string $name): Donor
    {
        return Donor::create([
            'organization_id' => $org->id,
            'name' => $name,
        ]);
    }
}
