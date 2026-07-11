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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 2 sub-step — animal photos (MinIO/S3, presigned-private).
 *
 * The S3 disks are faked, so these tests don't need a running MinIO. Uploads
 * write to the `s3` disk; the Resource builds presigned links from the
 * `s3_public` disk (temporary-URL generation is stubbed here). Org scoping is
 * enforced through the parent animal, exactly like every other shelter slice.
 */
class AnimalImageTest extends TestCase
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

    public function test_upload_stores_file_and_creates_row(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        $this->actingAs($admin, 'api')
            ->post("/api/animals/{$animal->id}/images", [
                'image' => UploadedFile::fake()->create('rex.jpg', 200, 'image/jpeg'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.url', fn ($url) => str_contains($url, 'http'));

        $image = $animal->images()->firstOrFail();

        $this->assertStringStartsWith(
            "animals/{$animal->organization_id}/{$animal->id}/",
            $image->path
        );
        Storage::disk('s3')->assertExists($image->path);
    }

    public function test_show_animal_includes_image_urls(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        $this->actingAs($admin, 'api')
            ->post("/api/animals/{$animal->id}/images", [
                'image' => UploadedFile::fake()->create('rex.jpg', 200, 'image/jpeg'),
            ])->assertCreated();

        $this->actingAs($admin, 'api')
            ->getJson("/api/animals/{$animal->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.url', fn ($url) => str_contains($url, 'http'));
    }

    public function test_upload_to_another_orgs_animal_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeAnimal($orgB, 'Alheio');

        $this->actingAs($adminA, 'api')
            ->post("/api/animals/{$theirs->id}/images", [
                'image' => UploadedFile::fake()->create('x.jpg', 200, 'image/jpeg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('animal_images', 0);
    }

    public function test_delete_removes_file_and_row(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        $imageId = $this->actingAs($admin, 'api')
            ->post("/api/animals/{$animal->id}/images", [
                'image' => UploadedFile::fake()->create('rex.jpg', 200, 'image/jpeg'),
            ])->json('data.id');

        $path = $animal->images()->firstOrFail()->path;

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/animals/{$animal->id}/images/{$imageId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('animal_images', ['id' => $imageId]);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        [, $admin] = $this->orgWithAdmin();
        $animal = $this->makeAnimal($this->orgOf($admin), 'Rex');

        // The exception handler maps ValidationException to HTTP 400.
        $this->actingAs($admin, 'api')
            ->post("/api/animals/{$animal->id}/images", [
                'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(400)
            ->assertJsonPath('errors.image.0', 'O arquivo deve ser uma imagem.');

        $this->assertDatabaseCount('animal_images', 0);
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
            'status_id' => (int) DB::table('animal_statuses')->where('name', 'Em Tratamento')->value('id'),
            'birth_date_is_estimated' => true,
            'neutered' => false,
        ]);
    }
}
