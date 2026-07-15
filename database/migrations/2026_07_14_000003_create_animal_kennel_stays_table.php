<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only placement history: every move creates a new row, closed
        // stays are never edited or reopened. No soft deletes (log table).
        Schema::create('animal_kennel_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kennel_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->date('exit_date')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_kennel_stays');
    }
};
