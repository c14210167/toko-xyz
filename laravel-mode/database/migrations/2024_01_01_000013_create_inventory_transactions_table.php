<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('item_id')->constrained('inventory_items', 'item_id')->onDelete('cascade');
            $table->enum('transaction_type', ['IN', 'OUT', 'ADJUSTMENT']);
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->index('item_id');
            $table->index('transaction_type');
            $table->index('order_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
