<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Carts ────────────────────────────────────────────────
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = guest
            $table->string('session_id')->nullable(); // for guest carts
            $table->timestamps();

            $table->index(['user_id', 'session_id']);
        });

        // ─── Cart Items ───────────────────────────────────────────
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // snapshot price at time of add
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });

        // ─── Orders ───────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g. ORD-20240101-0001
            $table->foreignId('customer_id')->constrained('users')->onDelete('restrict');

            // Status
            $table->enum('status', [
                'pending',
                'paid',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
            ])->default('pending');

            // Pricing
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_price', 12, 2);

            // Shipping address (snapshot at order time)
            $table->string('shipping_name');
            $table->string('shipping_phone', 20)->nullable();
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_country');
            $table->string('shipping_zip', 20)->nullable();

            // Payment
            $table->enum('payment_method', ['simulation', 'stripe', 'cod'])->default('simulation');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Order Items ──────────────────────────────────────────
        // Key: each item is linked to its vendor for sub-order views
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('vendor_id')->constrained()->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot values at purchase time
            $table->string('product_name');
            $table->string('variant_label')->nullable(); // e.g. "Red / XL"
            $table->string('product_sku');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            // Vendor sub-order status
            $table->enum('vendor_status', [
                'pending',
                'confirmed',
                'shipped',
                'delivered',
                'cancelled',
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
