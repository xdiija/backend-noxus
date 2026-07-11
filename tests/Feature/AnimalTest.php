<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalMenusSeeder;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 2 — animals (core).
 *
 * Animals are organization-scoped operational data: reads are filtered by the
 * authenticated user's org in AnimalService, org is set server-side on create,
 * and a status change writes an append-only animal_status_history row. The
 * Admin role is granted full access (AnimalMenusSeeder) so the permission gate
 * lets these requests through while still exercising the route wiring.
 */
class AnimalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        // Menu + Admin role_menu grants for the check.permissions:animals gate.
        $this->seed(AnimalMenusSeeder::class);

        // Reference data the Animal form depends on.
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);
    }

    public function test_store_creates_org_scoped_animal_with_initial_status_history(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $statusId = $this->statusId('Em Tratamento');

        $id = $this->actingAs($admin, 'api')
            ->postJson('/api/animals', $this->animalPayload(['status_id' => $statusId]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Rex')
            ->json('data.id');

        $this->assertDatabaseHas('animals', [
            'id' => $id,
            'organization_id' => $org->id,
            'name' => 'Rex',
        ]);

        // Initial status is logged with a null "from" status.
        $this->assertDatabaseHas('animal_status_history', [
            'animal_id' => $id,
            'old_status_id' => null,
            'new_status_id' => $statusId,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_store_with_rescue_creates_one_to_one_intake(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $id = $this->actingAs($admin, 'api')
            ->postJson('/api/animals', $this->animalPayload([
                'rescue' => [
                    'rescue_date' => '2024-01-10 09:30:00',
                    'location' => 'Rua das Flores',
                    'condition' => 'Desnutrido',
                ],
            ]))
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('rescues', [
            'animal_id' => $id,
            'location' => 'Rua das Flores',
        ]);
    }

    public function test_index_is_scoped_to_organization(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $mine = $this->makeAnimal($this->orgOf($adminA), 'Meu');
        $theirs = $this->makeAnimal($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')
            ->getJson('/api/animals')
            ->assertOk()
            ->assertJsonFragment(['id' => $mine->id])
            ->assertJsonMissing(['id' => $theirs->id]);
    }

    public function test_animal_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeAnimal($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/animals/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_update_status_writes_history_row(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        $newStatus = $this->statusId('Disponível');

        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}", $this->animalPayload([
                'status_id' => $newStatus,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('animal_status_history', [
            'animal_id' => $animal->id,
            'old_status_id' => $animal->status_id,
            'new_status_id' => $newStatus,
        ]);
    }

    public function test_change_status_endpoint_moves_and_logs(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        $newStatus = $this->statusId('Adotado');

        $this->actingAs($admin, 'api')
            ->putJson("/api/animals/{$animal->id}/status", [
                'status_id' => $newStatus,
                'notes' => 'Adoção concluída',
            ])
            ->assertOk();

        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'status_id' => $newStatus]);
        $this->assertDatabaseHas('animal_status_history', [
            'animal_id' => $animal->id,
            'new_status_id' => $newStatus,
            'notes' => 'Adoção concluída',
        ]);
    }

    public function test_microchip_is_globally_unique(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        // Existing animal in another org already owns the chip.
        $this->makeAnimal($orgB, 'Chipado', ['microchip' => '900123456789']);

        // The project's exception handler maps ValidationException to HTTP 400.
        $this->actingAs($adminA, 'api')
            ->postJson('/api/animals', $this->animalPayload(['microchip' => '900123456789']))
            ->assertStatus(400)
            ->assertJsonPath('errors.microchip.0', 'Este microchip já está cadastrado em outro animal.');
    }

    public function test_destroy_soft_deletes_the_animal(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animals/{$animal->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('animals', ['id' => $animal->id]);
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

    private function animalPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rex',
            'species_id' => $this->speciesId('Cachorro'),
            'sex' => 'M',
            'birth_date_is_estimated' => true,
            'estimated_birth_date' => '2022-05-01',
            'size_id' => $this->sizeId('Médio'),
            'neutered' => false,
            'status_id' => $this->statusId('Em Tratamento'),
        ], $overrides);
    }

    private function makeAnimal(Organization $org, string $name, array $overrides = []): Animal
    {
        return Animal::create(array_merge([
            'organization_id' => $org->id,
            'name' => $name,
            'species_id' => $this->speciesId('Cachorro'),
            'size_id' => $this->sizeId('Médio'),
            'status_id' => $this->statusId('Em Tratamento'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ], $overrides));
    }

    private function speciesId(string $name): int
    {
        return (int) DB::table('species')->where('name', $name)->value('id');
    }

    private function sizeId(string $name): int
    {
        return (int) DB::table('animal_sizes')->where('name', $name)->value('id');
    }

    private function statusId(string $name): int
    {
        return (int) DB::table('animal_statuses')->where('name', $name)->value('id');
    }
}
