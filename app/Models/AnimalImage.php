<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A stored photo of an animal. `path` is the object key on the `s3` disk
 * (MinIO). No soft deletes — removing a row means the image is gone (the
 * Service deletes the underlying object too). See data-model/conventions.md.
 */
class AnimalImage extends Model
{
    protected $table = 'animal_images';

    protected $fillable = [
        'animal_id',
        'path',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
