<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\Representative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvolutionApiWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $rep = Representative::create([
            'name' => 'Carlos Silva',
            'code' => 'REP-01',
            'phone' => '5511988887777',
            'is_active' => true,
        ]);

        $company = Company::create([
            'name' => 'Farmácia Central',
            'representative_id' => $rep->id,
            'segment' => 'Farmácia',
        ]);

        Contact::create([
            'company_id' => $company->id,
            'representative_id' => $rep->id,
            'name' => 'João Vendedor',
            'phone' => '5511999990001',
        ]);

        Product::create([
            'code' => 'PROD-DIP-500',
            'name' => 'Dipirona 500mg',
            'base_price' => 12.00,
            'stock_quantity' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_processes_inbound_whatsapp_message_and_responds(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => 'MSG_TEST_001',
                    'remoteJid' => '5511999990001@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'pushName' => 'João Vendedor',
                'message' => [
                    'conversation' => 'Quanto está a Dipirona?',
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/evolution', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'processed');

        $this->assertDatabaseHas('messages', [
            'external_id' => 'MSG_TEST_001',
            'direction' => 'inbound',
        ]);

        $this->assertDatabaseHas('messages', [
            'direction' => 'outbound',
            'sender_type' => 'ai',
        ]);
    }

    public function test_deduplicates_duplicate_webhook_messages(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => 'MSG_DUP_002',
                    'remoteJid' => '5511999990001@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'pushName' => 'João Vendedor',
                'message' => [
                    'conversation' => 'Olá!',
                ],
            ],
        ];

        // Primeiro envio
        $res1 = $this->postJson('/api/webhooks/evolution', $payload);
        $res1->assertStatus(200);
        $res1->assertJsonPath('status', 'processed');

        // Segundo envio com o mesmo ID
        $res2 = $this->postJson('/api/webhooks/evolution', $payload);
        $res2->assertStatus(200);
        $res2->assertJsonPath('status', 'duplicate_ignored');

        // Garante que só há 1 mensagem registrada
        $this->assertEquals(1, Message::where('external_id', 'MSG_DUP_002')->count());
    }

    public function test_processes_lgpd_opt_out_keyword(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => 'MSG_OPTOUT_003',
                    'remoteJid' => '5511999990001@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'PARAR',
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/evolution', $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'opt_out_processed');

        $contact = Contact::where('phone', '5511999990001')->first();
        $this->assertTrue($contact->isOptedOut('whatsapp'));
    }

    public function test_clinical_firewall_triggers_human_handover_on_medical_question(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => 'MSG_CLINICAL_004',
                    'remoteJid' => '5511999990001@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Estou com muita dor de cabeça e febre, posso tomar quantas gotas desse remédio?',
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/evolution', $payload);
        $response->assertStatus(200);

        $conversation = Conversation::whereHas('contact', fn($q) => $q->where('phone', '5511999990001'))->first();
        $this->assertEquals('human_takeover', $conversation->status);
        $this->assertEquals('clinical_inquiry_firewall', $conversation->handover_reason);
    }
}
