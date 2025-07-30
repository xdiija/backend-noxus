<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null'); // link to payment if relevant
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null'); // optional
            $table->enum('type', ['income', 'expense', 'transfer_in', 'transfer_out', 'adjustment']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2)->nullable(); // Optional but useful
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_movements');
    }
};
