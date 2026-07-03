<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('species_id')->constrained();
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->char('sex', 1)->nullable(); // M / F
            // Store an (estimated) birth date instead of a static age that goes stale.
            $table->date('estimated_birth_date')->nullable();
            $table->boolean('birth_date_is_estimated')->default(true);
            $table->foreignId('size_id')->constrained('animal_sizes');
            $table->string('color')->nullable();
            $table->string('microchip')->unique()->nullable();
            $table->boolean('neutered')->default(false);
            $table->date('neuter_date')->nullable();
            $table->foreignId('status_id')->constrained('animal_statuses');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
