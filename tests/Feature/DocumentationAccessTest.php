<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_docs_route(): void
    {
        $response = $this->get('/docs');

        // Deve redirecionar para a página de login
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_docs_route(): void
    {
        $user = User::factory()->create([
            'role' => 'representative',
        ]);

        $response = $this->actingAs($user)->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('Manual Completo do Representante');
        $response->assertSee('Visão Geral e Primeiros Passos');
        $response->assertSee('Atendimento Inteligente no WhatsApp & Regras da IA');
        $response->assertSee('CRM Comercial, Tags e Score RFM');
        $response->assertSee('Cotações e Fechamento com Trava de Segurança');
        $response->assertSee('Transferência Humana (Handover) e Pausa da IA');
        $response->assertSee('Guia de Comandos em Linguagem Natural');
        $response->assertSee('Segurança, LGPD e Firewall Clínico');
    }

    public function test_can_navigate_between_documentation_topics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/docs?topic=seguranca-lgpd');

        $response->assertStatus(200);
        $response->assertSee('Firewall Clínico e Farmacêutico');
        $response->assertSee('Opt-Out Automático');
    }
}
