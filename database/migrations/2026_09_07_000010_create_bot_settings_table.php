<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type')->default('string'); // boolean, integer, float, string
            $table->timestamps();
        });

        // Seed default operational settings
        $now = now();
        $defaults = [
            [
                'key' => 'typing_delay_enabled',
                'value' => '1',
                'group' => 'typing',
                'label' => 'Simular Digitação Humana no WhatsApp',
                'description' => 'Mantém o status "digitando..." ativo no WhatsApp por um período aleatório antes de disparar a resposta.',
                'type' => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'typing_delay_min',
                'value' => '5',
                'group' => 'typing',
                'label' => 'Tempo Mínimo de Digitação (segundos)',
                'description' => 'Tempo mínimo aleatório que o robô fica com o status "digitando..." antes de responder.',
                'type' => 'integer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'typing_delay_max',
                'value' => '30',
                'group' => 'typing',
                'label' => 'Tempo Máximo de Digitação (segundos)',
                'description' => 'Tempo máximo aleatório que o robô fica com o status "digitando..." antes de responder.',
                'type' => 'integer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'split_long_messages',
                'value' => '1',
                'group' => 'typing',
                'label' => 'Dividir Mensagens Longas em Blocos Curtos',
                'description' => 'Divide respostas extensas em 2 ou 3 mensagens menores enviadas com pequeno intervalo, como pessoas digitam no WhatsApp.',
                'type' => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'auto_pause_on_human_reply',
                'value' => '1',
                'group' => 'safety',
                'label' => 'Pausa Automática ao Intervir Humano',
                'description' => 'Se o representante enviar uma mensagem manual no WhatsApp, o robô pausa o atendimento automático.',
                'type' => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'pause_duration_minutes',
                'value' => '120',
                'group' => 'safety',
                'label' => 'Duração da Pausa de Intervenção (minutos)',
                'description' => 'Tempo de silêncio do robô antes de retomar o atendimento automático se o cliente enviar nova dúvida.',
                'type' => 'integer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'large_order_alert_threshold',
                'value' => '3000.00',
                'group' => 'alerts',
                'label' => 'Limite para Alerta de Pedido Grande (R$)',
                'description' => 'Cotações acima deste valor notificam o administrador.',
                'type' => 'float',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'max_autonomous_discount_pct',
                'value' => '5.0',
                'group' => 'commercial',
                'label' => 'Desconto Máximo Autônomo (%)',
                'description' => 'Percentual máximo de desconto que o robô pode conceder por conta própria em negociações.',
                'type' => 'float',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'prohibited_words',
                'value' => '',
                'group' => 'safety',
                'label' => 'Palavras e Termos Proibidos',
                'description' => 'Lista separada por vírgula de palavras que o robô nunca deve citar.',
                'type' => 'string',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('bot_settings')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
