<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('item_code', 50)->unique()->nullable();
            $table->string('name', 200);
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories', 'category_id')->onDelete('set null');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('reorder_level')->default(10);
            $table->foreignId('location_id')->nullable()->constrained('locations', 'location_id')->onDelete('set null');
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('location_id');
            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
