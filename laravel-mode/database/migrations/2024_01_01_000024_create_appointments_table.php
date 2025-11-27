<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->string('appointment_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations', 'location_id')->onDelete('cascade');
            $table->date('appointment_date');
            $table->time('time_slot');
            $table->string('device_type', 100)->nullable();
            $table->text('issue_description')->nullable();
            $table->enum('status', ['Pending', 'Confirmed', 'Cancelled', 'Completed', 'No-Show'])->default('Pending');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id')->onDelete('set null');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('location_id');
            $table->index('appointment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
