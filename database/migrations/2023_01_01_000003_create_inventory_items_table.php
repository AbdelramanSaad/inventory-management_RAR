<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            $table->integer('quantity');
            $table->integer('min_stock_level');
            $table->decimal('unit_price', 10, 2);
            $table->enum('category', ['electronics', 'furniture', 'clothing', 'other']);
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for filtering and searching
            $table->index('warehouse_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index('category');
            $table->index('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
