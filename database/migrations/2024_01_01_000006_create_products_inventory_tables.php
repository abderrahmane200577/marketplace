<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Product Images ───────────────────────────────────────
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // ─── Product Variants (size, color, etc.) ─────────────────
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('attribute');   // e.g. "color", "size"
            $table->string('value');       // e.g. "Red", "XL"
            $table->decimal('price_modifier', 8, 2)->default(0); // +/- from base price
            $table->string('sku')->nullable()->unique();
            $table->timestamps();
        });

        // ─── Inventory (stock per product) ─────────────────────────
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('track_quantity')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'variant_id']);
        });

        // ─── Inventory Logs (IN / OUT movements) ───────────────────
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('change');       // positive = IN, negative = OUT
            $table->integer('quantity_after');
            $table->enum('reason', [
                'purchase',      // customer order
                'cancel',        // order cancelled → restore
                'restock',       // vendor manual restock
                'adjustment',    // admin manual correction
                'return',        // customer return
            ]);
            $table->string('reference')->nullable(); // e.g. order_id
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
    }
};
