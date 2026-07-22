<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AnimalSizesTableSeeder;
use Database\Seeders\AnimalStatusesTableSeeder;
use Database\Seeders\SpeciesTableSeeder;
use Database\Seeders\SystemMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 7 — polymorphic documents (ADR-0005).
 *
 * Files live on MinIO/S3 presigned-private like animal images (the disks are
 * faked here). Documents are top-level and org-scoped: the owner is optional
 * (both morph columns null = org-level document) and, when present, is
 * resolved inside the org scope so a tenant can never attach to — or read —
 * another org's records.
 */
class DocumentTest extends TestCase
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
        $this->seed(SpeciesTableSeeder::class);
        $this->seed(AnimalSizesTableSeeder::class);
        $this->seed(AnimalStatusesTableSeeder::class);

        Storage::fake('s3');
        Storage::fake('s3_public');
        // The faked (local) disk can't sign URLs, so stub the generator.
        Storage::disk('s3_public')->buildTemporaryUrlsUsing(
            fn ($path, $expiration, $options) => "http://localhost:9000/test/{$path}"
        );
    }

    public function test_upload_org_level_document_stores_file_and_creates_row(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->post('/api/documents', [
                'file' => UploadedFile::fake()->create('Estatuto da ONG.pdf', 200, 'application/pdf'),
                'type' => 'Estatuto',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'estatuto-da-ong.pdf')
            ->assertJsonPath('data.type', 'Estatuto')
            ->assertJsonPath('data.url', fn ($url) => str_contains($url, 'http'));

        $document = Document::firstOrFail();

        $this->assertSame($org->id, $document->organization_id);
        $this->assertNull($document->documentable_type);
        $this->assertNull($document->documentable_id);
        $this->assertStringStartsWith("documents/{$org->id}/organization/", $document->path);
        Storage::disk('s3')->assertExists($document->path);
    }

    public function test_upload_linked_to_an_animal_sets_the_morph_owner(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');

        $this->actingAs($admin, 'api')
            ->post('/api/documents', [
                'file' => UploadedFile::fake()->create('exame.pdf', 100, 'application/pdf'),
                'type' => 'Exame',
                'documentable_type' => 'animal',
                'documentable_id' => $animal->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.documentable.type', 'animal')
            ->assertJsonPath('data.documentable.id', $animal->id)
            ->assertJsonPath('data.documentable.name', 'Rex');

        $this->assertDatabaseHas('documents', [
            'organization_id' => $org->id,
            'documentable_type' => Animal::class,
            'documentable_id' => $animal->id,
        ]);
    }

    public function test_upload_linked_to_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeAnimal($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')
            ->post('/api/documents', [
                'file' => UploadedFile::fake()->create('exame.pdf', 100, 'application/pdf'),
                'documentable_type' => 'animal',
                'documentable_id' => $theirs->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_documentable_id_without_type_is_rejected(): void
    {
        [, $admin] = $this->orgWithAdmin();

        // The exception handler maps ValidationException to HTTP 400.
        $this->actingAs($admin, 'api')
            ->post('/api/documents', [
                'file' => UploadedFile::fake()->create('exame.pdf', 100, 'application/pdf'),
                'documentable_id' => 1,
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.documentable_type.0', 'Informe o tipo de vínculo do documento.');
    }

    public function test_index_is_org_scoped_and_filters_by_owner(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB, $adminB] = $this->orgWithAdmin();

        $animal = $this->makeAnimal($org, 'Rex');

        $this->actingAs($admin, 'api')->post('/api/documents', [
            'file' => UploadedFile::fake()->create('geral.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->actingAs($admin, 'api')->post('/api/documents', [
            'file' => UploadedFile::fake()->create('exame-rex.pdf', 100, 'application/pdf'),
            'documentable_type' => 'animal',
            'documentable_id' => $animal->id,
        ])->assertCreated();

        $this->actingAs($adminB, 'api')->post('/api/documents', [
            'file' => UploadedFile::fake()->create('deles.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->actingAs($admin, 'api')
            ->getJson('/api/documents')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin, 'api')
            ->getJson("/api/documents?documentable_type=animal&documentable_id={$animal->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'exame-rex.pdf');
    }

    public function test_update_changes_only_the_type_metadata(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $id = $this->actingAs($admin, 'api')->post('/api/documents', [
            'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
            'type' => 'Rascunho',
        ])->json('data.id');

        $this->actingAs($admin, 'api')
            ->putJson("/api/documents/{$id}", ['type' => 'Contrato'])
            ->assertOk()
            ->assertJsonPath('data.type', 'Contrato');

        $this->assertDatabaseHas('documents', ['id' => $id, 'type' => 'Contrato']);
    }

    public function test_delete_removes_file_and_row(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $id = $this->actingAs($admin, 'api')->post('/api/documents', [
            'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
        ])->json('data.id');

        $path = Document::findOrFail($id)->path;

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/documents/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('documents', ['id' => $id]);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_another_orgs_document_is_not_updatable_or_deletable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [, $adminB] = $this->orgWithAdmin();

        $id = $this->actingAs($adminB, 'api')->post('/api/documents', [
            'file' => UploadedFile::fake()->create('deles.pdf', 100, 'application/pdf'),
        ])->json('data.id');

        $this->actingAs($adminA, 'api')
            ->putJson("/api/documents/{$id}", ['type' => 'Invadido'])
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->deleteJson("/api/documents/{$id}")
            ->assertNotFound();

        $this->assertDatabaseHas('documents', ['id' => $id]);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        [, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->post('/api/documents', [
                'file' => UploadedFile::fake()->create('script.sh', 10, 'application/x-sh'),
            ])
            ->assertStatus(400)
            ->assertJsonPath(
                'errors.file.0',
                'O arquivo deve ser PDF, imagem (JPEG/PNG/WEBP) ou documento (DOC/DOCX/ODT/XLS/XLSX).'
            );

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_owner_options_only_require_the_documents_permission(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($org, 'Rex');

        // The system menus grant documents only, so the owner resources' own
        // routes are denied…
        $this->actingAs($admin, 'api')
            ->getJson('/api/animals')
            ->assertForbidden();
        $this->actingAs($admin, 'api')
            ->getJson('/api/volunteers')
            ->assertForbidden();

        // …while the documents-gated owner picker still serves them.
        $this->actingAs($admin, 'api')
            ->getJson('/api/documents/owner-options?type=animal&filter=Re')
            ->assertOk()
            ->assertJsonFragment(['id' => $animal->id, 'name' => 'Rex']);

        // Unknown owner types are rejected.
        $this->actingAs($admin, 'api')
            ->getJson('/api/documents/owner-options?type=invoice')
            ->assertStatus(400);
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

    private function makeAnimal(Organization $org, string $name): Animal
    {
        return Animal::create([
            'organization_id' => $org->id,
            'name' => $name,
            'species_id' => (int) DB::table('species')->where('name', 'Cachorro')->value('id'),
            'size_id' => (int) DB::table('animal_sizes')->where('name', 'Médio')->value('id'),
            'status_id' => (int) DB::table('animal_statuses')->where('name', 'Em Tratamento')->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);
    }
}
