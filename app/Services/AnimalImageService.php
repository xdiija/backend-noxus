<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnimalImageService
{
    /**
     * The disk objects are written to / deleted from (internal MinIO endpoint).
     */
    private const DISK = 's3';

    public function list(string $animalId): Collection
    {
        return $this->animal($animalId)->images()->latest('id')->get();
    }

    public function upload(string $animalId, UploadedFile $file): AnimalImage
    {
        $animal = $this->animal($animalId);

        // Namespace objects by org + animal so keys never collide across tenants.
        $path = $file->store("animals/{$animal->organization_id}/{$animal->id}", self::DISK);

        return $animal->images()->create(['path' => $path]);
    }

    public function delete(string $animalId, string $imageId): void
    {
        $animal = $this->animal($animalId);

        DB::transaction(function () use ($animal, $imageId) {
            $image = $animal->images()->findOrFail($imageId);

            Storage::disk(self::DISK)->delete($image->path);
            $image->delete();
        });
    }

    /**
     * Resolve the animal scoped to the authenticated user's organization, so a
     * tenant can only ever attach/list/remove images on its own animals.
     */
    private function animal(string $animalId): Animal
    {
        return Animal::query()
            ->where('organization_id', auth()->user()?->organization_id)
            ->findOrFail($animalId);
    }
}
