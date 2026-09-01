<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
    }

    public function test_google_redirect_sends_the_user_to_google(): void
    {
        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirectContains('accounts.google.com');
    }

    public function test_google_redirect_fails_gracefully_without_credentials(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('swal');
        $this->assertGuest();
    }

    public function test_callback_creates_and_logs_in_a_new_user(): void
    {
        $this->mockSocialiteUser();

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertSame('900123', $user->google_id);
        $this->assertSame('Ana García', $user->name);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_google_to_an_existing_account_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'ana@example.com',
            'google_id' => null,
        ]);

        $this->mockSocialiteUser();

        $this->get(route('auth.google.callback'));

        $this->assertSame(1, User::count());
        $this->assertSame('900123', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_callback_returns_to_login_when_the_user_denies_access(): void
    {
        $response = $this->get(route('auth.google.callback', ['error' => 'access_denied']));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function mockSocialiteUser(): void
    {
        $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getId')->andReturn('900123');
        $googleUser->shouldReceive('getName')->andReturn('Ana García');
        $googleUser->shouldReceive('getEmail')->andReturn('ana@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/foto.jpg');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
