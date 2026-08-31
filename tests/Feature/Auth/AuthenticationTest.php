<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        session(['captcha_answer' => 7]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => '7',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_ventanilla_es_redirigido_al_listado_de_alumnos_tras_login(): void
    {
        $user = User::factory()->create(['role' => 'ventanilla']);
        session(['captcha_answer' => 7]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => '7',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('alumnos.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();
        session(['captcha_answer' => 7]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'captcha' => '7',
        ]);

        $this->assertGuest();
    }

    public function test_el_captcha_incorrecto_rechaza_el_login(): void
    {
        $user = User::factory()->create();
        session(['captcha_answer' => 7]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => '99',
        ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
