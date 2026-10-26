<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiAutenticacionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function entrega_un_token_con_credenciales_validas(): void
    {
        $usuario = User::factory()->create();

        $this->postJson('/api/tokens', ['email' => $usuario->email, 'password' => 'password'])
            ->assertCreated()
            ->assertJsonStructure(['token', 'rol']);
    }

    #[Test]
    public function rechaza_credenciales_invalidas(): void
    {
        $usuario = User::factory()->create();

        $this->postJson('/api/tokens', ['email' => $usuario->email, 'password' => 'mal'])->assertUnauthorized();
    }

    #[Test]
    public function los_endpoints_protegidos_exigen_token(): void
    {
        $this->getJson('/api/productos')->assertUnauthorized();
        $this->postJson('/api/ventas', [])->assertUnauthorized();
    }
}
