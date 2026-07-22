<?php

namespace Tests\Feature;

use App\Models\Adopter;
use App\Models\Adoption;
use App\Models\Animal;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdoptionMenusSeeder;
use Database\Seeders\AdoptionStatusesTableSeeder;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 3 — adoptions.
 *
 * Adoptions carry the adopt/return/cancel lifecycle. They are org-scoped, enforce
 * the "one active adoption per animal" rule (ADR-0002) in application code, and
 * keep the animal's status in sync (writing an animal_status_history row through
 * AnimalService). The Admin role is granted full access so the permission gate
 * lets these requests through while exercising the route wiring.
 */
class AdoptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(AnimalMenusSeeder::class);
        $this->seed(AdoptionMenusSeeder::class);

        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
        $this->seed(AdoptionStatusesTableSeeder::class);
    }

    public function test_store_creates_adoption_and_marks_animal_adopted(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');
        $adopter = $this->makeAdopter($org, 'Maria');

        $id = $this->actingAs($admin, 'api')
            ->postJson('/api/adoptions', [
                'animal_id' => $animal->id,
                'adopter_id' => $adopter->id,
                'adoption_date' => '2026-06-01',
                'agreement_signed' => true,
            ])
            ->assertCreated()
            ->json('data.id');

        // Adoption row created with the default status (Pendente).
        $this->assertDatabaseHas('adoptions', [
            'id' => $id,
            'organization_id' => $org->id,
            'animal_id' => $animal->id,
            'status_id' => $this->adoptionStatusId('Pendente'),
        ]);

        // Animal moved to "Adotado" with a logged status change.
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Adotado'),
        ]);
        $this->assertDatabaseHas('animal_status_history', [
            'animal_id' => $animal->id,
            'new_status_id' => $this->animalStatusId('Adotado'),
        ]);
    }

    public function test_second_active_adoption_for_same_animal_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');
        $adopter = $this->makeAdopter($org, 'Maria');

        $this->makeAdoption($org, $animal, $adopter);

        // A second active adoption is blocked (ADR-0002, application-enforced).
        $this->actingAs($admin, 'api')
            ->postJson('/api/adoptions', [
                'animal_id' => $animal->id,
                'adopter_id' => $adopter->id,
                'adoption_date' => '2026-06-02',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'Este animal já possui uma adoção ativa.');
    }

    public function test_return_marks_adoption_returned_and_animal_returned(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');
        $adopter = $this->makeAdopter($org, 'Maria');
        $adoption = $this->makeAdoption($org, $animal, $adopter);

        $this->actingAs($admin, 'api')
            ->putJson("/api/adoptions/{$adoption->id}/return", [
                'return_date' => '2026-06-10',
                'return_reason' => 'Mudança de cidade',
            ])
            ->assertOk();

        $this->assertDatabaseHas('adoptions', [
            'id' => $adoption->id,
            'status_id' => $this->adoptionStatusId('Devolvida'),
            'return_reason' => 'Mudança de cidade',
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Devolvido'),
        ]);
    }

    public function test_animal_can_be_re_adopted_after_a_return(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');
        $adopter = $this->makeAdopter($org, 'Maria');
        $adoption = $this->makeAdoption($org, $animal, $adopter);

        $this->actingAs($admin, 'api')
            ->putJson("/api/adoptions/{$adoption->id}/return", ['return_date' => '2026-06-10'])
            ->assertOk();

        // With the previous adoption returned, a new one is allowed.
        $this->actingAs($admin, 'api')
            ->postJson('/api/adoptions', [
                'animal_id' => $animal->id,
                'adopter_id' => $adopter->id,
                'adoption_date' => '2026-07-01',
            ])
            ->assertCreated();

        $this->assertSame(2, Adoption::where('animal_id', $animal->id)->count());
    }

    public function test_cancel_marks_adoption_cancelled_and_animal_available(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');
        $adopter = $this->makeAdopter($org, 'Maria');
        $adoption = $this->makeAdoption($org, $animal, $adopter);

        $this->actingAs($admin, 'api')
            ->putJson("/api/adoptions/{$adoption->id}/cancel", ['notes' => 'Desistência'])
            ->assertOk();

        $this->assertDatabaseHas('adoptions', [
            'id' => $adoption->id,
            'status_id' => $this->adoptionStatusId('Cancelada'),
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Disponível'),
        ]);
    }

    public function test_cannot_adopt_an_animal_from_another_org(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirAnimal = $this->makeAnimal($orgB, 'Alheio');
        $myAdopter = $this->makeAdopter($this->orgOf($adminA), 'Maria');

        $this->actingAs($adminA, 'api')
            ->postJson('/api/adoptions', [
                'animal_id' => $theirAnimal->id,
                'adopter_id' => $myAdopter->id,
                'adoption_date' => '2026-06-01',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'O animal informado é inválido.');
    }

    public function test_adoption_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $animal = $this->makeAnimal($orgB, 'Alheio');
        $adopter = $this->makeAdopter($orgB, 'Maria');
        $theirs = $this->makeAdoption($orgB, $animal, $adopter);

        $this->actingAs($adminA, 'api')
            ->getJson("/api/adoptions/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_animal_and_adopter_options_are_slim_and_org_scoped(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $mine = $this->makeAnimal($org, 'Rex');
        $this->makeAnimal($orgB, 'Alheio');
        $adopter = $this->makeAdopter($org, 'Maria');

        $this->actingAs($admin, 'api')
            ->getJson('/api/adoptions/animal-options?filter=Rex')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', ['id' => $mine->id, 'name' => 'Rex']);

        $this->actingAs($admin, 'api')
            ->getJson('/api/adoptions/adopter-options')
            ->assertOk()
            ->assertJsonFragment(['id' => $adopter->id, 'name' => 'Maria']);
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

    private function makeAnimal(Organization $org, string $name): Animal
    {
        return Animal::create([
            'organization_id' => $org->id,
            'name' => $name,
            'species_id' => (int) DB::table('species')->where('name', 'Cachorro')->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => $this->animalStatusId('Disponível'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);
    }

    private function makeAdopter(Organization $org, string $name): Adopter
    {
        return Adopter::create([
            'organization_id' => $org->id,
            'name' => $name,
        ]);
    }

    private function makeAdoption(Organization $org, Animal $animal, Adopter $adopter): Adoption
    {
        return Adoption::create([
            'organization_id' => $org->id,
            'animal_id' => $animal->id,
            'adopter_id' => $adopter->id,
            'adoption_date' => '2026-06-01',
            'status_id' => $this->adoptionStatusId('Pendente'),
            'agreement_signed' => false,
        ]);
    }

    private function animalStatusId(string $name): int
    {
        return (int) DB::table('animal_statuses')->where('name', $name)->value('id');
    }

    private function adoptionStatusId(string $name): int
    {
        return (int) DB::table('adoption_statuses')->where('name', $name)->value('id');
    }
}
