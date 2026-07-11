<?php

namespace Tests\Feature;

use App\Models\Adopter;
use App\Models\Adoption;
use App\Models\Animal;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdoptionFollowUpStatusesTableSeeder;
use Database\Seeders\AdoptionMenusSeeder;
use Database\Seeders\AdoptionStatusesTableSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 3 — adoption follow-ups.
 *
 * Post-adoption check-ins nested under an adoption. Org scoping is enforced in
 * AdoptionFollowUpService via the parent adoption, so a tenant can only reach
 * follow-ups on its own adoptions. status_id references
 * adoption_follow_up_statuses (not adoption_statuses).
 */
class AdoptionFollowUpTest extends TestCase
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
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
        $this->seed(AdoptionStatusesTableSeeder::class);
        $this->seed(AdoptionFollowUpStatusesTableSeeder::class);
    }

    public function test_store_creates_follow_up_under_adoption(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $adoption = $this->makeAdoption($org);

        $id = $this->actingAs($admin, 'api')
            ->postJson("/api/adoptions/{$adoption->id}/follow-ups", [
                'follow_up_date' => '2026-07-01',
                'contact_method' => 'phone',
                'status_id' => $this->followUpStatusId('Agendado'),
                'notes' => 'Ligar para confirmar bem-estar',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('adoption_follow_ups', [
            'id' => $id,
            'adoption_id' => $adoption->id,
            'contact_method' => 'phone',
        ]);
    }

    public function test_index_lists_follow_ups_of_the_adoption(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $adoption = $this->makeAdoption($org);

        $adoption->followUps()->create([
            'follow_up_date' => '2026-07-01',
            'status_id' => $this->followUpStatusId('Contatado'),
        ]);

        $this->actingAs($admin, 'api')
            ->getJson("/api/adoptions/{$adoption->id}/follow-ups")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_follow_up_on_another_orgs_adoption_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAdoption = $this->makeAdoption($orgB);

        $this->actingAs($adminA, 'api')
            ->postJson("/api/adoptions/{$theirAdoption->id}/follow-ups", [
                'follow_up_date' => '2026-07-01',
                'status_id' => $this->followUpStatusId('Agendado'),
            ])
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

    private function makeAdoption(Organization $org): Adoption
    {
        $animal = Animal::create([
            'organization_id' => $org->id,
            'name' => 'Rex',
            'species_id' => (int) DB::table('species')->where('name', 'Cachorro')->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => (int) DB::table('animal_statuses')->where('name', 'Adotado')->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);

        $adopter = Adopter::create([
            'organization_id' => $org->id,
            'name' => 'Maria',
        ]);

        return Adoption::create([
            'organization_id' => $org->id,
            'animal_id' => $animal->id,
            'adopter_id' => $adopter->id,
            'adoption_date' => '2026-06-01',
            'status_id' => (int) DB::table('adoption_statuses')->where('name', 'Pendente')->value('id'),
            'agreement_signed' => false,
        ]);
    }

    private function followUpStatusId(string $name): int
    {
        return (int) DB::table('adoption_follow_up_statuses')->where('name', $name)->value('id');
    }
}
