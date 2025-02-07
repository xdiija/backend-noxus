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
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('payment_date');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('account_id');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('transaction_categories')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
