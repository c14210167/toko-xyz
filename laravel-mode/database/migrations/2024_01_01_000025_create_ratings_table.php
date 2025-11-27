<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id('rating_id');
            $table->foreignId('order_id')->constrained('orders', 'order_id')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('technician_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->tinyInteger('service_rating')->nullable()->comment('1-5');
            $table->tinyInteger('technician_rating')->nullable()->comment('1-5');
            $table->tinyInteger('speed_rating')->nullable()->comment('1-5');
            $table->tinyInteger('price_rating')->nullable()->comment('1-5');
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->text('response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('customer_id');
            $table->index('technician_id');
            $table->index('overall_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
