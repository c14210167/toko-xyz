<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->string('payment_number', 50)->unique();
            $table->foreignId('order_id')->constrained('orders', 'order_id')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['Cash', 'Card', 'Transfer', 'E-Wallet']);
            $table->enum('payment_status', ['Pending', 'Paid', 'Partial', 'Refunded'])->default('Paid');
            $table->string('transaction_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_url')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
            $table->index('payment_number');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
