<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->boolean('view');
            $table->boolean('create');
            $table->boolean('update');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu');
    }
};
