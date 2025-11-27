<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->string('sale_number', 50)->unique();
            $table->foreignId('location_id')->nullable()->constrained('locations', 'location_id')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['Cash', 'Card', 'Transfer', 'E-Wallet']);
            $table->enum('payment_status', ['Paid', 'Pending', 'Partial', 'Refunded'])->default('Paid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->index('location_id');
            $table->index('customer_id');
            $table->index('sale_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
