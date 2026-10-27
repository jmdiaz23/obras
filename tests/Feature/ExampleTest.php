<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_redirige_al_login_para_invitados(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_el_login_carga_correctamente_sin_opcion_de_registro(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(Login::class)
            ->assertDontSee('Registra tu empresa');
    }

    public function test_el_registro_publico_esta_deshabilitado(): void
    {
        $this->get('/registro')->assertNotFound();
    }
}
