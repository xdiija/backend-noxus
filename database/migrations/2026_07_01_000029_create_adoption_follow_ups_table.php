<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoption_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adoption_id')->constrained()->cascadeOnDelete();
            $table->date('follow_up_date');
            $table->string('contact_method')->nullable();
            $table->foreignId('status_id')->constrained('adoption_follow_up_statuses');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoption_follow_ups');
    }
};
