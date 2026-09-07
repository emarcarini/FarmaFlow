<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->string('title');
            $table->enum('stage', ['lead', 'qualified', 'quoted', 'negotiating', 'won', 'lost'])->default('lead')->index();
            $table->decimal('estimated_value', 12, 2)->default(0.00);
            $table->integer('probability')->default(50); // 0 a 100%
            $table->date('expected_close_date')->nullable();
            $table->string('loss_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted_to_order'])->default('draft')->index();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_total', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('revalidated_at')->nullable();
            $table->json('snapshot_data')->nullable(); // Guarda detalhes dos preços e condições vigentes no momento
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_pct', 5, 2)->default(0.00);
            $table->decimal('total_price', 12, 2);
            $table->enum('condition_source', ['base_price', 'tier_price', 'campaign'])->default('base_price');
            $table->foreignId('commercial_campaign_id')->nullable()->constrained('commercial_campaigns')->nullOnDelete();
            $table->foreignId('product_price_id')->nullable()->constrained('product_prices')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->enum('status', ['pending_approval', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('confirmed')->index();
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_total', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->json('validation_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_pct', 5, 2)->default(0.00);
            $table->decimal('total_price', 12, 2);
            $table->enum('condition_source', ['base_price', 'tier_price', 'campaign'])->default('base_price');
            $table->foreignId('commercial_campaign_id')->nullable()->constrained('commercial_campaigns')->nullOnDelete();
            $table->foreignId('product_price_id')->nullable()->constrained('product_prices')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('reason')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('opportunities');
    }
};
