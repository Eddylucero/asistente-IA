<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_a_code_is_emailed_and_the_user_moves_to_the_code_step(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('password.code'));
        $response->assertSessionHas('password_reset.email', $user->email);

        Mail::assertSent(PasswordResetCodeMail::class, fn ($mail) => $mail->hasTo($user->email));

        $this->assertDatabaseCount('password_reset_codes', 1);

        $this->get('/forgot-password/codigo')
            ->assertStatus(200)
            ->assertSee($user->email);
    }

    public function test_the_email_carries_the_code_and_renders(): void
    {
        $html = (new PasswordResetCodeMail('483920', 'Ana'))->render();

        $this->assertStringContainsString('483920', $html);
        $this->assertStringContainsString('Ana', $html);
    }

    public function test_an_unregistered_email_is_rejected_with_a_clear_message(): void
    {
        Mail::fake();

        $response = $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nadie@example.com']);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors([
            'email' => 'Este correo no se encuentra registrado.',
        ]);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_codes', 0);
        $this->assertNull(session('password_reset.email'));
    }

    public function test_the_email_is_matched_ignoring_uppercase_and_spaces(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'anahi@example.com']);

        $response = $this->post('/forgot-password', ['email' => '  ANAHI@Example.COM  ']);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('password.code'));
        $response->assertSessionHas('password_reset.email', 'anahi@example.com');

        Mail::assertSent(PasswordResetCodeMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_the_code_step_is_unreachable_without_asking_for_a_code(): void
    {
        $this->get('/forgot-password/codigo')->assertRedirect(route('password.request'));
    }

    public function test_a_wrong_code_is_rejected_and_burns_an_attempt(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCodeFor($user);

        $response = $this->post('/forgot-password/codigo', [
            'code' => $this->wrongVariantOf($code),
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('password_reset.token');

        $this->assertSame(1, PasswordResetCode::where('email', $user->email)->first()->attempts);
    }

    public function test_a_code_dies_after_too_many_attempts(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCodeFor($user);

        PasswordResetCode::where('email', $user->email)
            ->update(['attempts' => PasswordResetCode::MAX_ATTEMPTS]);

        $this->post('/forgot-password/codigo', ['code' => $code]);

        $this->assertNull(session('password_reset.token'));
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCodeFor($user);

        PasswordResetCode::where('email', $user->email)
            ->update(['expires_at' => now()->subMinute()]);

        $this->post('/forgot-password/codigo', ['code' => $code]);

        $this->assertNull(session('password_reset.token'));
    }

    public function test_the_right_code_unlocks_the_new_password_screen(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCodeFor($user);

        $response = $this->post('/forgot-password/codigo', ['code' => $code]);

        $response->assertRedirect(route('password.reset.form'));
        $this->assertNotNull(session('password_reset.token'));

        $this->get('/forgot-password/nueva-clave')->assertStatus(200);
    }

    public function test_the_new_password_screen_is_unreachable_without_verifying(): void
    {
        $user = User::factory()->create();
        $this->requestCodeFor($user);

        $this->get('/forgot-password/nueva-clave')->assertRedirect(route('password.request'));
    }

    public function test_the_password_is_changed_and_the_code_is_consumed(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCodeFor($user);

        $this->post('/forgot-password/codigo', ['code' => $code]);

        $response = $this->post('/forgot-password/nueva-clave', [
            'password' => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('clave-nueva-123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_codes', 0);
        $this->assertNull(session('password_reset.token'));
    }

    public function test_the_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCodeFor($user);

        $this->post('/forgot-password/codigo', ['code' => $code]);

        $response = $this->post('/forgot-password/nueva-clave', [
            'password' => 'clave-nueva-123',
            'password_confirmation' => 'otra-cosa-456',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(Hash::check('clave-nueva-123', $user->fresh()->password));
    }

    public function test_resending_replaces_the_previous_code(): void
    {
        $user = User::factory()->create();
        $first = $this->requestCodeFor($user);

        Mail::fake();
        $this->post('/forgot-password/reenviar')->assertRedirect();

        $second = null;
        Mail::assertSent(PasswordResetCodeMail::class, function ($mail) use (&$second) {
            $second = $mail->code;

            return true;
        });

        $this->assertDatabaseCount('password_reset_codes', 1);
        $this->post('/forgot-password/codigo', ['code' => $first]);
        $this->assertNull(session('password_reset.token'));

        $this->post('/forgot-password/codigo', ['code' => $second]);
        $this->assertNotNull(session('password_reset.token'));
    }

    private function requestCodeFor(User $user): string
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => $user->email]);

        $code = null;

        Mail::assertSent(PasswordResetCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function wrongVariantOf(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }
}
