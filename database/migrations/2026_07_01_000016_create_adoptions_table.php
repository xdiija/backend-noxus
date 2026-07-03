<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // NOT unique: an animal may be returned and re-adopted later.
            $table->foreignId('animal_id')->constrained();
            $table->foreignId('adopter_id')->constrained();
            $table->date('adoption_date');
            $table->foreignId('status_id')->constrained('adoption_statuses');
            $table->boolean('agreement_signed')->default(false);
            $table->date('return_date')->nullable();
            $table->text('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoptions');
    }
};
