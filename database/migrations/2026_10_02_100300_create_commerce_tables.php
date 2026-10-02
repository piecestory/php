<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('type', 20)->comment('showroom | warehouse');
            $table->string('city', 100);
            $table->string('district', 100);
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('map_url', 500)->nullable();
            $table->boolean('is_pickup_point')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->char('token', 40)->unique()->comment('Identifies guest carts');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // Prices are never stored here: they are read live from products until the order is placed.
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->boolean('requires_pickup_branch')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->char('locale', 2)->default('ar');
            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('unpaid');
            $table->char('currency', 3)->default('SAR');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->comment('VAT contained in grand_total');
            $table->decimal('grand_total', 12, 2);
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // Address snapshot: the order keeps the address as it was when placed.
            $table->string('ship_recipient_name')->nullable();
            $table->string('ship_phone', 20)->nullable();
            $table->string('ship_city', 100)->nullable();
            $table->string('ship_district', 100)->nullable();
            $table->string('ship_street')->nullable();
            $table->string('ship_building_number', 10)->nullable();
            $table->string('ship_postal_code', 10)->nullable();
            $table->string('ship_additional_number', 10)->nullable();
            $table->string('ship_short_address', 8)->nullable();
            $table->string('customer_note', 1000)->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index(['payment_status', 'placed_at']);
            $table->index('phone');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 40);
            $table->string('name_ar');
            $table->string('name_en');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('carrier', 50);
            $table->string('tracking_number', 100)->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['product_id', 'expires_at']);
            $table->unique(['order_id', 'product_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_change');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 30);
            $table->nullableMorphs('reference');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 30);
            $table->string('provider_reference', 100)->nullable();
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('SAR');
            $table->string('status', 20)->default('initiated');
            $table->string('failure_reason', 500)->nullable();
            $table->json('metadata')->nullable()->comment('Provider response; never card data');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 150);
            $table->string('event_type', 100)->nullable();
            $table->char('payload_hash', 64);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending');
            $table->string('provider_reference', 100)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('branches');
    }
};
