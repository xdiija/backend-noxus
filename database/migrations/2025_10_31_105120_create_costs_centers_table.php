<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('cost_centers');
        Schema::enableForeignKeyConstraints();
    }
};
