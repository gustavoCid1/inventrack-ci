<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_pantalla_de_login_carga_correctamente(): void
    {
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
    }

    #[Test]
    public function un_invitado_es_redirigido_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    #[Test]
    public function un_usuario_inicia_sesion_con_credenciales_validas(): void
    {
        $usuario = User::factory()->create();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
    }

    #[Test]
    public function rechaza_credenciales_incorrectas(): void
    {
        $usuario = User::factory()->create();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
