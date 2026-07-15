<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalFosterHome;
use App\Models\AnimalKennelStay;
use App\Models\FosterHome;
use App\Models\Kennel;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\FosteringMenusSeeder;
use Database\Seeders\KennelsMenusSeeder;
use Database\Seeders\KennelStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 8 — kennel stays (where the animal is at).
 *
 * A stay is an animal's period in a kennel, nested under the org-scoped kennel.
 * Opening one enforces the kennel invariants (kennels.md): one open stay per
 * animal, capacity, kennel in service, animal at the shelter (no open foster
 * stay, no off-site status). History is append-only: closed stays are
 * immutable, transfers close + create in one transaction, and moving the
 * animal to an off-site status closes its open stay through AnimalService.
 */
class KennelStayTest extends TestCase
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
        $this->seed(KennelsMenusSeeder::class);

        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
        $this->seed(KennelStatusesTableSeeder::class);
    }

    public function test_store_opens_stay_without_touching_animal_status(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennel->id}/stays", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-01',
                'reason' => 'Resgate',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.reason', 'Resgate');

        // Being in a kennel says nothing about the lifecycle status.
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Disponível'),
        ]);
    }

    public function test_second_open_stay_for_same_animal_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennelA = $this->makeKennel($org, 'A1');
        $kennelB = $this->makeKennel($org, 'B1');
        $animal = $this->makeAnimal($org, 'Rex');

        $this->openStay($kennelA, $animal);

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennelB->id}/stays", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-02',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'Este animal já possui uma alocação ativa em canil.');
    }

    public function test_full_kennel_rejects_new_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1', capacity: 1);
        $this->openStay($kennel, $this->makeAnimal($org, 'Rex'));

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennel->id}/stays", [
                'animal_id' => $this->makeAnimal($org, 'Mia')->id,
                'entry_date' => '2026-06-02',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.kennel_id.0', 'Este canil já atingiu sua capacidade máxima.');
    }

    public function test_kennel_under_maintenance_rejects_new_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1', statusName: 'Em Manutenção');

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennel->id}/stays", [
                'animal_id' => $this->makeAnimal($org, 'Rex')->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.kennel_id.0', 'Este canil está em manutenção e não pode receber animais.');
    }

    public function test_isolation_kennel_accepts_stays(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'ISO-1', statusName: 'Isolamento');

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennel->id}/stays", [
                'animal_id' => $this->makeAnimal($org, 'Rex')->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertCreated();
    }

    public function test_animal_with_open_foster_stay_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');

        AnimalFosterHome::create([
            'animal_id' => $animal->id,
            'foster_home_id' => FosterHome::create([
                'organization_id' => $org->id,
                'responsible_name' => 'Casa '.Str::random(6),
            ])->id,
            'entry_date' => '2026-05-01',
        ]);

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennel->id}/stays", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'Este animal está em um lar temporário. Encerre essa estadia antes de alocá-lo em um canil.');
    }

    public function test_off_site_animal_status_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');
        $animal->update(['status_id' => $this->animalStatusId('Adotado')]);

        $this->actingAs($admin, 'api')
            ->postJson("/api/kennels/{$kennel->id}/stays", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-01',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.animal_id.0', 'O status atual do animal não permite alocação em canil.');
    }

    public function test_close_sets_exit_date_and_keeps_animal_status(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($kennel, $animal);

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}/stays/{$stay->id}/close", ['exit_date' => '2026-06-10'])
            ->assertOk()
            ->assertJsonPath('data.is_open', false);

        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'status_id' => $this->animalStatusId('Disponível'),
        ]);
    }

    public function test_closing_an_already_closed_stay_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $stay = $this->openStay($kennel, $this->makeAnimal($org, 'Rex'), exitDate: '2026-06-10');

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}/stays/{$stay->id}/close", ['exit_date' => '2026-06-12'])
            ->assertStatus(400)
            ->assertJsonPath('errors.id.0', 'Esta alocação já foi encerrada.');
    }

    public function test_exit_date_before_entry_date_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $stay = $this->openStay($kennel, $this->makeAnimal($org, 'Rex'), entryDate: '2026-06-05');

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}/stays/{$stay->id}/close", ['exit_date' => '2026-06-01'])
            ->assertStatus(400)
            ->assertJsonPath('errors.exit_date.0', 'A data de saída não pode ser anterior à data de entrada.');
    }

    public function test_update_corrects_an_open_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($kennel, $animal, entryDate: '2026-06-05');

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}/stays/{$stay->id}", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-01',
                'reason' => 'Resgate',
            ])
            ->assertOk();

        $this->assertDatabaseHas('animal_kennel_stays', [
            'id' => $stay->id,
            'entry_date' => '2026-06-01',
            'reason' => 'Resgate',
        ]);
    }

    public function test_closed_stays_are_immutable(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($kennel, $animal, exitDate: '2026-06-10');

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}/stays/{$stay->id}", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-02',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.id.0', 'Esta alocação já foi encerrada.');
    }

    public function test_transfer_closes_current_stay_and_opens_a_new_one(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennelA = $this->makeKennel($org, 'A1');
        $kennelB = $this->makeKennel($org, 'B1');
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($kennelA, $animal);

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennelA->id}/stays/{$stay->id}/transfer", [
                'kennel_id' => $kennelB->id,
                'date' => '2026-06-10',
            ])
            // 201: the transfer's response is the newly created stay.
            ->assertCreated()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.reason', 'Transferência')
            ->assertJsonPath('data.kennel.id', $kennelB->id);

        // The old stay was closed, never edited or reopened.
        $this->assertDatabaseHas('animal_kennel_stays', [
            'id' => $stay->id,
            'kennel_id' => $kennelA->id,
            'exit_date' => '2026-06-10',
        ]);
        $this->assertDatabaseHas('animal_kennel_stays', [
            'animal_id' => $animal->id,
            'kennel_id' => $kennelB->id,
            'entry_date' => '2026-06-10',
            'exit_date' => null,
        ]);
    }

    public function test_transfer_to_the_same_kennel_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1', capacity: 2);
        $stay = $this->openStay($kennel, $this->makeAnimal($org, 'Rex'));

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennel->id}/stays/{$stay->id}/transfer", [
                'kennel_id' => $kennel->id,
                'date' => '2026-06-10',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.kennel_id.0', 'O animal já está neste canil.');
    }

    public function test_transfer_to_a_full_kennel_is_rejected(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennelA = $this->makeKennel($org, 'A1');
        $kennelB = $this->makeKennel($org, 'B1', capacity: 1);
        $this->openStay($kennelB, $this->makeAnimal($org, 'Mia'));
        $stay = $this->openStay($kennelA, $this->makeAnimal($org, 'Rex'));

        $this->actingAs($admin, 'api')
            ->putJson("/api/kennels/{$kennelA->id}/stays/{$stay->id}/transfer", [
                'kennel_id' => $kennelB->id,
                'date' => '2026-06-10',
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.kennel_id.0', 'Este canil já atingiu sua capacidade máxima.');
    }

    public function test_off_site_status_change_closes_the_open_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($kennel, $animal);

        // Adoption/foster/death workflows all route through animal status
        // changes; any off-site status frees the kennel automatically.
        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}/status", [
                'status_id' => $this->animalStatusId('Adotado'),
            ])
            ->assertOk();

        $this->assertDatabaseMissing('animal_kennel_stays', [
            'id' => $stay->id,
            'exit_date' => null,
        ]);
    }

    public function test_foster_placement_closes_the_open_kennel_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $animal = $this->makeAnimal($org, 'Rex');
        $stay = $this->openStay($kennel, $animal);

        $home = FosterHome::create([
            'organization_id' => $org->id,
            'responsible_name' => 'Casa '.Str::random(6),
        ]);

        $this->actingAs($admin, 'api')
            ->postJson("/api/foster-homes/{$home->id}/placements", [
                'animal_id' => $animal->id,
                'entry_date' => '2026-06-10',
            ])
            ->assertCreated();

        $this->assertDatabaseMissing('animal_kennel_stays', [
            'id' => $stay->id,
            'exit_date' => null,
        ]);
    }

    public function test_stays_of_another_orgs_kennel_are_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirKennel = $this->makeKennel($orgB, 'Alheio');
        $theirStay = $this->openStay($theirKennel, $this->makeAnimal($orgB, 'Alheio'));

        $this->actingAs($adminA, 'api')
            ->getJson("/api/kennels/{$theirKennel->id}/stays")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->putJson("/api/kennels/{$theirKennel->id}/stays/{$theirStay->id}/close", ['exit_date' => '2026-06-10'])
            ->assertNotFound();
    }

    public function test_index_lists_the_kennels_stays_with_animal(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1', capacity: 2);
        $this->openStay($kennel, $this->makeAnimal($org, 'Rex'), entryDate: '2026-06-01');
        $this->openStay($kennel, $this->makeAnimal($org, 'Mia'), entryDate: '2026-06-03');

        $this->actingAs($admin, 'api')
            ->getJson("/api/kennels/{$kennel->id}/stays")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.animal.name', 'Mia');
    }

    public function test_destroy_removes_stay(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $kennel = $this->makeKennel($org, 'A1');
        $stay = $this->openStay($kennel, $this->makeAnimal($org, 'Rex'));

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/kennels/{$kennel->id}/stays/{$stay->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('animal_kennel_stays', ['id' => $stay->id]);
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

    private function makeKennel(Organization $org, string $name, int $capacity = 1, string $statusName = 'Disponível'): Kennel
    {
        return Kennel::create([
            'organization_id' => $org->id,
            'name' => $name,
            'capacity' => $capacity,
            'status_id' => (int) DB::table('kennel_statuses')->where('name', $statusName)->value('id'),
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

    /** Seed a stay directly (no workflow side effects — DB state only). */
    private function openStay(Kennel $kennel, Animal $animal, string $entryDate = '2026-06-01', ?string $exitDate = null): AnimalKennelStay
    {
        return AnimalKennelStay::create([
            'animal_id' => $animal->id,
            'kennel_id' => $kennel->id,
            'entry_date' => $entryDate,
            'exit_date' => $exitDate,
        ]);
    }
}
