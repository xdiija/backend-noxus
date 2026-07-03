<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // Single intake event per animal; all rescue data lives here (not on animals).
            $table->foreignId('animal_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('rescue_date');
            $table->string('location')->nullable();
            $table->string('rescued_by')->nullable();
            $table->string('condition')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rescues');
    }
};
