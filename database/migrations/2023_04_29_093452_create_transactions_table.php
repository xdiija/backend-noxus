<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('id');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('transaction_categories')->onDelete('cascade');
        });
    }

    public function down()
{
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('transactions');
    Schema::enableForeignKeyConstraints();
}
};
