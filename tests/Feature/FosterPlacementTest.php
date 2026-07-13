<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalFosterHome;
use App\Models\FosterHome;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\FosteringMenusSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 5 — foster placements (stays).
 *
 * A placement is an animal's stay in a foster home, nested under the org-scoped
 * home. Opening a stay enforces the fostering invariants (fostering.md): at
 * most one open stay per animal and the home's capacity; open/close keep the
 * animal's status in sync (Em Lar Temporário / Disponível) through
 * AnimalService, which logs animal_status_history.
 */
class FosterPlacementTest extends TestCase
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
        $this->seed(FosteringMenusSeeder::class);

        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
    }

    public function test_store_opens_stay_and_marks_animal_fostered(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $animal = $this->makeAnimal($org, 'Rex');

        $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$home->id}/placements", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_open', true);

        $this->assertDatabaseHas('animal_foster_homes', [
            'animal_id' => $animal->id,
            'foster_home_id' => $home->id,
            'exit_date' => null,
        ]);

        // Animal moved to "Em Lar Temporário" with a logged status change.
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Em Lar Temporário'),
        ]);
        $this->assertDatabaseHas('animal_status_history', [
            'animal_id' => $animal->id,
            'new_status_id' => $this->animalStatusId('Em Lar Temporário'),
        ]);
    }

    public function test_second_open_stay_for_same_animal_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $homeA = $this->makeFosterHome($org);
        $homeB = $this->makeFosterHome($org);
        $animal = $this->makeAnimal($org, 'Rex');

        $this->openStay($homeA, $animal, '2026-06-01');

        // Even in a different home, an animal can have only one open stay.
        $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$homeB->id}/placements", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-02',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'Este animal já possui um lar temporário ativo.');
    }

    public function test_full_home_rejects_new_placement(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org, capacity: 1);
        $this->openStay($home, $this->makeAnimal($org, 'Rex'), '2026-06-01');

        $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$home->id}/placements", [
                'animal_id' => $this->makeAnimal($org, 'Mia')->id,
                'entry_date' => '2026-06-02',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.foster_home_id.0', 'Este lar temporário já atingiu sua capacidade máxima.');
    }

    public function test_closed_stays_free_up_capacity(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org, capacity: 1);
        $stay = $this->openStay($home, $this->makeAnimal($org, 'Rex'), '2026-06-01');

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}/placements/{$stay->id}/close", ['exit_date' => '2026-06-10'])
            ->assertOk();

        $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$home->id}/placements", [
                'animal_id' => $this->makeAnimal($org, 'Mia')->id,
                'entry_date' => '2026-06-11',
            ])
            ->assertCreated();
    }

    public function test_close_sets_exit_date_and_marks_animal_available(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStayViaApi($admin, $home, $animal, '2026-06-01');

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}/placements/{$stay}/close", ['exit_date' => '2026-06-10'])
            ->assertOk()
            ->assertJsonPath('data.is_open', false);

        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Disponível'),
        ]);
    }

    public function test_close_does_not_clobber_status_set_by_another_workflow(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStayViaApi($admin, $home, $animal, '2026-06-01');

        // Meanwhile the animal was adopted straight from the foster home.
        $animal->update(['status_id' => $this->animalStatusId('Adotado')]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}/placements/{$stay}/close", ['exit_date' => '2026-06-10'])
            ->assertOk();

        // Closing the stay must not flip an adopted animal back to available.
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Adotado'),
        ]);
    }

    public function test_closing_an_already_closed_stay_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $stay = $this->openStay($home, $this->makeAnimal($org, 'Rex'), '2026-06-01', exitDate: '2026-06-10');

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}/placements/{$stay->id}/close", ['exit_date' => '2026-06-12'])
            ->assertStatus(400)
            ->assertJsonPath('errors.exit_date.0', 'Esta estadia já foi encerrada.');
    }

    public function test_exit_date_before_entry_date_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $stay = $this->openStay($home, $this->makeAnimal($org, 'Rex'), '2026-06-05');

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}/placements/{$stay->id}/close", ['exit_date' => '2026-06-01'])
            ->assertStatus(400)
            ->assertJsonPath('errors.exit_date.0', 'A data de saída não pode ser anterior à data de entrada.');
    }

    public function test_update_corrects_entry_date(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($home, $animal, '2026-06-05');

        $this->actingAs($admin, 'api')
            ->putJson("/api/foster-homes/{$home->id}/placements/{$stay->id}", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertOk();

        $this->assertDatabaseHas('animal_foster_homes', [
            'id' => $stay->id,
            'entry_date' => '2026-06-01',
        ]);
    }

    public function test_index_lists_the_homes_stays_with_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $this->openStay($home, $this->makeAnimal($org, 'Rex'), '2026-06-01');
        $this->openStay($home, $this->makeAnimal($org, 'Mia'), '2026-06-03');

        $this->actingAs($admin, 'api')
            ->getJson("/api/foster-homes/{$home->id}/placements")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.animal.name', 'Mia');
    }

    public function test_cannot_place_an_animal_from_another_org(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $home = $this->makeFosterHome($org);
        $theirAnimal = $this->makeAnimal($orgB, 'Alheio');

        $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$home->id}/placements", [
                'animal_id' => $theirAnimal->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'O animal informado é inválido.');
    }

    public function test_placements_of_another_orgs_home_are_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirHome = $this->makeFosterHome($orgB);
        $theirStay = $this->openStay($theirHome, $this->makeAnimal($orgB, 'Alheio'), '2026-06-01');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/foster-homes/{$theirHome->id}/placements")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->putJson("/api/foster-homes/{$theirHome->id}/placements/{$theirStay->id}/close", ['exit_date' => '2026-06-10'])
            ->assertNotFound();
    }

    public function test_destroy_removes_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $home = $this->makeFosterHome($org);
        $stay = $this->openStay($home, $this->makeAnimal($org, 'Rex'), '2026-06-01');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/foster-homes/{$home->id}/placements/{$stay->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('animal_foster_homes', ['id' => $stay->id]);
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

    private function makeFosterHome(Organization $org, ?int $capacity = null): FosterHome
    {
        return FosterHome::create([
            'organization_id' => $org->id,
            'responsible_name' => 'Casa '.Str::random(6),
            'capacity' => $capacity,
        ]);
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

    private function animalStatusId(string $name): int
    {
        return (int) DB::table('animal_statuses')->where('name', $name)->value('id');
    }

    /** Seed a stay directly (no status sync — DB state only). */
    private function openStay(FosterHome $home, Animal $animal, string $entryDate, ?string $exitDate = null): AnimalFosterHome
    {
        return AnimalFosterHome::create([
            'animal_id' => $animal->id,
            'foster_home_id' => $home->id,
            'entry_date' => $entryDate,
            'exit_date' => $exitDate,
        ]);
    }

    /** Open a stay through the API (runs the full workflow) and return its id. */
    private function openStayViaApi(User $admin, FosterHome $home, Animal $animal, string $entryDate): int
    {
        return (int) $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$home->id}/placements", [
                'animal_id' => $animal->id,
                'entry_date' => $entryDate,
            ])
            ->assertCreated()
            ->json('data.id');
    }
}
