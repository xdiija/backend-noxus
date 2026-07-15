<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\DonationType;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DonationTypesTableSeeder;
use Database\Seeders\FinanceMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 6 — donations.
 *
 * Donations are org-scoped (suppliers pattern) and typed by the
 * donation_types lookup. A donation identifies its donor either through an
 * org-scoped donor_id link or through the inline donor_* fallback fields
 * (finance.md); amount is nullable so in-kind gifts can be logged without a
 * cash value (ADR-0007).
 */
class DonationTest extends TestCase
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
        $this->seed(DonationTypesTableSeeder::class);
    }

    public function test_store_creates_donation_with_linked_donor(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $donor = $this->makeDonor($org, 'Maria');

        $this->actingAs($admin, 'api')
            ->postJson('/api/donations', [
                'donor_id' => $donor->id,
                'donation_type_id' => $this->typeId('Dinheiro'),
                'amount' => 150.50,
                'donation_date' => '2026-07-10',
            ])
            ->assertCreated()
            ->assertJsonPath('data.donor.name', 'Maria')
            ->assertJsonPath('data.donation_type.name', 'Dinheiro')
            ->assertJsonPath('data.amount', 150.5);

        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'organization_id' => $org->id,
            'amount' => 150.50,
        ]);
    }

    public function test_store_creates_one_off_donation_with_inline_donor_fields(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/donations', [
                'donor_name' => 'Anônimo Generoso',
                'donor_phone' => '(11) 98888-7777',
                'donation_type_id' => $this->typeId('Ração / Alimentos'),
                'donation_date' => '2026-07-01',
                'notes' => '20kg de ração.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.donor_name', 'Anônimo Generoso')
            ->assertJsonPath('data.amount', null);

        $this->assertDatabaseHas('donations', [
            'organization_id' => $org->id,
            'donor_id' => null,
            'donor_name' => 'Anônimo Generoso',
            'donor_phone' => '11988887777',
            'amount' => null,
        ]);
    }

    public function test_linked_donor_wins_over_inline_fields(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $donor = $this->makeDonor($org, 'Maria');

        $this->actingAs($admin, 'api')
            ->postJson('/api/donations', [
                'donor_id' => $donor->id,
                'donor_name' => 'Não Deveria Ficar',
                'donation_type_id' => $this->typeId('Dinheiro'),
                'amount' => 10,
                'donation_date' => '2026-07-10',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'donor_name' => null,
        ]);
    }

    public function test_donor_from_another_org_is_rejected(): void
    {
        [, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();
        $theirs = $this->makeDonor($orgB, 'Alheia');

        $this->actingAs($admin, 'api')
            ->postJson('/api/donations', [
                'donor_id' => $theirs->id,
                'donation_type_id' => $this->typeId('Dinheiro'),
                'amount' => 10,
                'donation_date' => '2026-07-10',
            ])
            ->assertStatus(400)
            ->assertJsonValidationErrors('donor_id');
    }

    public function test_future_donation_date_is_rejected(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/donations', [
                'donation_type_id' => $this->typeId('Dinheiro'),
                'amount' => 10,
                'donation_date' => now()->addDay()->toDateString(),
            ])
            ->assertStatus(400)
            ->assertJsonValidationErrors('donation_date');
    }

    public function test_index_lists_only_own_orgs_donations(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->makeDonation($org, ['donor_name' => 'Minha Doação']);
        $this->makeDonation($orgB, ['donor_name' => 'Doação Alheia']);

        $this->actingAs($admin, 'api')
            ->getJson('/api/donations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.donor_name', 'Minha Doação');
    }

    public function test_update_edits_donation(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $donation = $this->makeDonation($org);

        $this->actingAs($admin, 'api')
            ->putJson("/api/donations/{$donation->id}", [
                'donation_type_id' => $this->typeId('Medicamentos'),
                'amount' => 300,
                'donation_date' => '2026-07-05',
                'notes' => 'Atualizada.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'donation_type_id' => $this->typeId('Medicamentos'),
            'amount' => 300,
            'notes' => 'Atualizada.',
        ]);
    }

    public function test_destroy_soft_deletes_donation(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $donation = $this->makeDonation($org);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/donations/{$donation->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('donations', ['id' => $donation->id]);
    }

    public function test_donation_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeDonation($orgB);

        $this->actingAs($adminA, 'api')
            ->getJson("/api/donations/{$theirs->id}")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->deleteJson("/api/donations/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_donation_types_lookup_returns_defaults_plus_own_customs_only(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        DonationType::create(['organization_id' => $org->id, 'name' => 'Meu Tipo']);
        DonationType::create(['organization_id' => $orgB->id, 'name' => 'Tipo Alheio']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/donation-types')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Dinheiro'));
        $this->assertTrue($names->contains('Meu Tipo'));
        $this->assertFalse($names->contains('Tipo Alheio'));
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

    private function makeDonation(Organization $org, array $overrides = []): Donation
    {
        return Donation::create($overrides + [
            'organization_id' => $org->id,
            'donation_type_id' => $this->typeId('Dinheiro'),
            'amount' => 50,
            'donation_date' => '2026-07-01',
        ]);
    }

    private function typeId(string $name): int
    {
        return DonationType::whereNull('organization_id')->where('name', $name)->value('id');
    }
}
