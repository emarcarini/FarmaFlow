<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Acessar Painel Comercial');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'vendedor@comercial.com.br',
            'password' => Hash::make('senha123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'vendedor@comercial.com.br',
            'password' => 'senha123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'vendedor@comercial.com.br',
            'password' => Hash::make('senha123'),
        ]);

        $this->post('/login', [
            'email' => 'vendedor@comercial.com.br',
            'password' => 'senha-errada',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_all_portal_views(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertStatus(200);
        $this->actingAs($user)->get('/inbox')->assertStatus(200);
        $this->actingAs($user)->get('/crm')->assertStatus(200);
        $this->actingAs($user)->get('/catalogo')->assertStatus(200);
        $this->actingAs($user)->get('/vendas')->assertStatus(200);
        $this->actingAs($user)->get('/tarefas')->assertStatus(200);
        $this->actingAs($user)->get('/docs')->assertStatus(200);
    }
}
