<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kennels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('capacity')->default(1);
            // Advisory largest size that fits; enforcement is a future improvement.
            $table->foreignId('size_id')->nullable()->constrained('animal_sizes')->nullOnDelete();
            // Operational state (kennel_statuses lookup). Occupancy is derived
            // from open stays, never stored.
            $table->foreignId('status_id')->constrained('kennel_statuses');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kennels');
    }
};
