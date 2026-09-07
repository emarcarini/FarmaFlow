<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código interno / EAN
            $table->string('sku')->nullable()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('presentation')->nullable(); // ex: 'Caixa com 30 comprimidos'
            $table->string('unit', 10)->default('un'); // cx, un, fr, pct
            $table->decimal('base_price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->string('category')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable(); // null significa infinito (ex: 50+)
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_pct', 5, 2)->default(0.00);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['fixed_price', 'percentage_discount', 'tier_override'])->default('percentage_discount');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(10);
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_campaign_id')->constrained('commercial_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('special_price', 10, 2)->nullable();
            $table->decimal('discount_pct', 5, 2)->nullable();
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_campaign_id')->constrained('commercial_campaigns')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->string('segment')->nullable();
            $table->string('classification', 5)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_audiences');
        Schema::dropIfExists('campaign_products');
        Schema::dropIfExists('commercial_campaigns');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
    }
};
