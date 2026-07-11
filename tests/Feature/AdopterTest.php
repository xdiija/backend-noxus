<?php

namespace Tests\Feature;

use App\Models\Adopter;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdoptionMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 3 — adopters.
 *
 * Adopters are organization-scoped operational data: reads are filtered by the
 * authenticated user's org in AdopterService, org is set server-side on create,
 * and CPF is unique per organization (not globally). The Admin role is granted
 * full access (AdoptionMenusSeeder) so the permission gate lets these requests
 * through while still exercising the route wiring.
 */
class AdopterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(AdoptionMenusSeeder::class);
    }

    public function test_store_creates_org_scoped_adopter(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $id = $this->actingAs($admin, 'api')
            ->postJson('/api/adopters', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Maria Silva')
            ->json('data.id');

        $this->assertDatabaseHas('adopters', [
            'id' => $id,
            'organization_id' => $org->id,
            'name' => 'Maria Silva',
        ]);
    }

    public function test_cpf_is_unique_within_the_organization(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/adopters', $this->payload())
            ->assertCreated();

        // Same CPF in the same org is rejected (mapped to HTTP 400).
        $this->actingAs($admin, 'api')
            ->postJson('/api/adopters', $this->payload(['name' => 'Outro']))
            ->assertStatus(400)
            ->assertJsonPath('errors.cpf.0', 'Este CPF já está cadastrado para outro adotante.');
    }

    public function test_same_cpf_is_allowed_in_a_different_organization(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [, $adminB] = $this->orgWithAdmin();

        $this->actingAs($adminA, 'api')
            ->postJson('/api/adopters', $this->payload())
            ->assertCreated();

        // The same person may exist as an adopter in another org (ADR/spec).
        $this->actingAs($adminB, 'api')
            ->postJson('/api/adopters', $this->payload())
            ->assertCreated();
    }

    public function test_index_is_scoped_to_organization(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $mine = $this->makeAdopter($this->orgOf($adminA), 'Meu');
        $theirs = $this->makeAdopter($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')
            ->getJson('/api/adopters')
            ->assertOk()
            ->assertJsonFragment(['id' => $mine->id])
            ->assertJsonMissing(['id' => $theirs->id]);
    }

    public function test_adopter_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeAdopter($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/adopters/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_destroy_soft_deletes_the_adopter(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $adopter = $this->makeAdopter($this->orgOf($admin), 'Maria');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/adopters/{$adopter->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('adopters', ['id' => $adopter->id]);
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

    private function orgOf(User $user): Organization
    {
        return Organization::findOrFail($user->organization_id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Silva',
            'cpf' => '111.444.777-35',
            'phone' => '(11) 98888-7777',
            'email' => 'maria@example.com',
            'city' => 'São Paulo',
            'state' => 'SP',
        ], $overrides);
    }

    private function makeAdopter(Organization $org, string $name, array $overrides = []): Adopter
    {
        return Adopter::create(array_merge([
            'organization_id' => $org->id,
            'name' => $name,
        ], $overrides));
    }
}
