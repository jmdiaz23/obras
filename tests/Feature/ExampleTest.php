<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_redirige_al_login_para_invitados(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_el_login_carga_correctamente(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(\App\Livewire\Auth\Login::class);
    }

    public function test_el_registro_carga_correctamente(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSeeLivewire(\App\Livewire\Auth\Register::class);
    }
}
