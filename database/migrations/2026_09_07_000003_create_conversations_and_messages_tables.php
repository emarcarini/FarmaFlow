<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained('representatives')->nullOnDelete();
            $table->string('channel')->default('whatsapp');
            $table->string('external_id')->nullable()->index(); // ID do chat na Evolution API (ex: 5511999999999@s.whatsapp.net)
            $table->enum('status', ['ai_handling', 'human_takeover', 'closed'])->default('ai_handling')->index();
            $table->string('handover_reason')->nullable();
            $table->timestamp('handover_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound'])->index();
            $table->string('external_id')->nullable()->unique(); // ID da mensagem no WhatsApp para deduplicação / idempotência
            $table->enum('sender_type', ['customer', 'representative', 'ai', 'system'])->index();
            $table->longText('content');
            $table->string('message_type', 30)->default('text'); // text, audio, image, document, location
            $table->enum('status', ['received', 'pending', 'sent', 'delivered', 'read', 'failed'])->default('received')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->enum('memory_type', ['fact', 'preference', 'communication_style', 'temporary_context'])->index();
            $table->text('content');
            $table->string('source_message_id')->nullable();
            $table->decimal('confidence_score', 4, 2)->default(1.00);
            $table->boolean('is_confirmed')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_memories');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
