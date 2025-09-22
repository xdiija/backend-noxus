<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recurrent_payments', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->decimal('amount', 10, 2);
            $table->enum('interval', ['weekly', 'monthly', 'yearly'])->nullable();
            $table->date('start_date');
            $table->date('next_date');
            $table->date('end_date')->nullable();
            $table->integer('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('recurrent_payments');
        Schema::enableForeignKeyConstraints();
    }
};
