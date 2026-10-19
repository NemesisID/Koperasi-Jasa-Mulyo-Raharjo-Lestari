<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Company Profile Contents (compro_contents)
        Schema::create('compro_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page')->default('welcome'); // e.g. welcome, about
            $table->string('section'); // e.g. hero, about, institusi, timeline, instagram, services
            $table->string('key'); // e.g. judul, deskripsi, background_image, posts, etc.
            $table->string('type')->default('text'); // text, richtext, image, url, repeater
            $table->longText('value')->nullable(); // string, html, or json string for repeater
            $table->integer('order')->default(1);
            $table->timestamps();

            $table->index(['page', 'section', 'order']);
        });

        // 2. Site Settings (Key-Value configuration for contact, social media, payment accounts)
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, image, boolean
            $table->string('group')->default('general'); // general, contact, payment, shipping
            $table->timestamps();
        });

        // 3. Product Categories & Products
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('weight_gram')->default(0);
            $table->integer('stock_qty')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
        });

        // 4. Product Prices (Retail & Distributor) & Audits
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('tier', ['retail', 'distributor']);
            $table->unsignedBigInteger('price'); // nominal integer rupiah
            $table->timestamps();

            $table->unique(['product_id', 'tier']);
        });

        Schema::create('price_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('tier', ['retail', 'distributor']);
            $table->unsignedBigInteger('old_price')->nullable();
            $table->unsignedBigInteger('new_price');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // 5. Shipping Rates (Flat Internal Zone: Province, City, District)
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('city');
            $table->string('district')->nullable(); // nullable jika berlaku flat se-kota
            $table->unsignedBigInteger('rate_per_kg'); // biaya per kg (pembulatan ceil ke atas)
            $table->unsignedInteger('estimated_days_min')->default(1);
            $table->unsignedInteger('estimated_days_max')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['province', 'city', 'district']);
        });

        // 6. Carts & Cart Items (Supports Guest via session_token or Authenticated User)
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token')->nullable()->index(); // for guest
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });

        // 7. Orders, Order Items, Status History
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g. ORD-20261019-XXXX
            $table->string('idempotency_key')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            
            // Sliced shipping address
            $table->string('shipping_province');
            $table->string('shipping_city');
            $table->string('shipping_district');
            $table->string('shipping_postal_code')->nullable();
            $table->text('shipping_address_detail');
            
            // Calculated weight and pricing snapshot
            $table->unsignedInteger('total_weight_gram')->default(0);
            $table->unsignedInteger('billable_weight_kg')->default(0);
            $table->unsignedBigInteger('shipping_fee')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);

            // Statuses
            $table->enum('order_status', ['pending', 'processing', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending_payment', 'paid', 'expired', 'failed'])->default('pending_payment');
            $table->string('payment_method')->default('manual_transfer'); // manual_transfer, qris
            $table->string('payment_proof')->nullable(); // path upload bukti transfer
            $table->timestamp('paid_at')->nullable();
            $table->string('tracking_number')->nullable(); // nomor resi pengiriman
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            
            // Immutable snapshot of product details at time of order
            $table->string('product_sku');
            $table->string('product_name');
            $table->enum('price_tier', ['retail', 'distributor'])->default('retail');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('unit_weight_gram');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->default('system'); // system, customer, admin
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('price_audits');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('compro_contents');
    }
};
