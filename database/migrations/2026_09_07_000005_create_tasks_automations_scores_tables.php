<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('task_type', ['call', 'meeting', 'whatsapp', 'followup', 'review'])->default('whatsapp')->index();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->index();
            $table->dateTime('due_date')->nullable()->index();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->dateTime('scheduled_for')->index();
            $table->enum('trigger_type', ['unanswered_quote', 'inactive_customer', 'repurchase_cycle', 'manual'])->default('manual')->index();
            $table->enum('status', ['scheduled', 'executed', 'cancelled'])->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_product_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('last_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('last_purchased_at')->nullable()->index();
            $table->integer('total_quantity_purchased')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0.00);
            $table->integer('average_cycle_days')->nullable(); // Média de dias entre recompras
            $table->date('next_estimated_purchase_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('customer_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->integer('recency_score')->default(0); // 0-100
            $table->integer('frequency_score')->default(0); // 0-100
            $table->integer('monetary_score')->default(0); // 0-100
            $table->integer('overall_score')->default(0); // 0-100
            $table->enum('trend', ['growing', 'stable', 'declining', 'at_risk'])->default('stable')->index();
            $table->json('explanation')->nullable(); // Explica o motivo do score
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('type', 50)->default('info'); // alert, handover, quote, order, task
            $table->boolean('is_read')->default(false)->index();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('actor_type', ['user', 'customer', 'ai', 'system'])->default('user')->index();
            $table->string('actor_name')->nullable();
            $table->string('action')->index(); // e.g.: price_calculation, order_created, quote_created, human_handover, clinical_alert
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('customer_scores');
        Schema::dropIfExists('customer_product_history');
        Schema::dropIfExists('followups');
        Schema::dropIfExists('tasks');
    }
};
