<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations', 'location_id')->onDelete('set null');
            $table->foreignId('technician_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->string('service_type', 100)->nullable();
            $table->string('device_type', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->text('problem_description')->nullable();
            $table->text('issue_type')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('warranty_status', 50)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'waiting_parts', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamps();

            $table->index('order_number');
            $table->index('user_id');
            $table->index('location_id');
            $table->index('technician_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
