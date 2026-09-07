<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('code')->unique();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->decimal('max_discount_pct', 5, 2)->default(10.00);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string('document')->nullable()->index(); // CNPJ/CPF
            $table->string('email')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('segment')->nullable()->index();
            $table->enum('classification', ['A', 'B', 'C'])->default('B')->index();
            $table->enum('status', ['active', 'inactive', 'prospect', 'at_risk'])->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->string('name');
            $table->string('role_position')->nullable();
            $table->string('phone')->index(); // WhatsApp phone formatted
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 20)->default('#4f46e5');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_tag_id')->constrained('customer_tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();
        });

        Schema::create('consent_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('channel')->default('whatsapp'); // whatsapp, email, sms
            $table->boolean('is_opted_out')->default(false)->index();
            $table->string('opt_out_reason')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_preferences');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('representatives');
    }
};
