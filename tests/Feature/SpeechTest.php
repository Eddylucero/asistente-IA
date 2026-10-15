<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LocalSpeech;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class SpeechTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_is_saved_only_for_the_authenticated_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $email = $user->email;

        $this->actingAs($user)->patch(route('profile.voice.update'), [
            'tts_voice' => 'piper:es_MX-claude-high', 'email' => 'changed@example.com', 'id' => $other->id,
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();

        $this->assertSame('piper:es_MX-claude-high', $user->fresh()->tts_voice);
        $this->assertSame($email, $user->fresh()->email);
        $this->assertNull($other->fresh()->tts_voice);
    }

    public function test_browser_voice_can_be_saved_and_automatic_clears_the_preference(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.voice.update'), ['tts_voice' => 'browser:Microsoft%20Elena:es-ES'])
            ->assertSessionHasNoErrors();
        $this->assertSame('browser:Microsoft%20Elena:es-ES', $user->fresh()->tts_voice);

        $this->patch(route('profile.voice.update'), ['tts_voice' => 'auto'])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->tts_voice);
    }

    public function test_invalid_preferences_use_the_voice_error_bag(): void
    {
        $user = User::factory()->create(['tts_voice' => 'piper:es_AR-daniela-high']);
        foreach (['piper:../../secret', 'browser:', str_repeat('a', 513), '', ['invalid']] as $voice) {
            $this->actingAs($user)->from(route('profile.edit'))
                ->patch(route('profile.voice.update'), ['tts_voice' => $voice])
                ->assertRedirect(route('profile.edit'))->assertSessionHasErrors(['tts_voice'], null, 'updateVoice');
        }
        $this->assertSame('piper:es_AR-daniela-high', $user->fresh()->tts_voice);
    }

    public function test_profile_renders_saved_preference_and_available_local_voices(): void
    {
        $this->mock(LocalSpeech::class, function (MockInterface $mock) {
            $mock->shouldReceive('voices')->once()->andReturn([['id' => 'piper:es_AR-daniela-high', 'name' => 'Daniela']]);
            $mock->shouldReceive('defaultVoice')->once()->andReturn('piper:es_AR-daniela-high');
        });
        $this->actingAs(User::factory()->create(['tts_voice' => 'piper:es_MX-claude-high']))->get(route('profile.edit'))
            ->assertOk()->assertSee('Voz de Lumi')->assertSee('Escuchar muestra')
            ->assertSee('data-current-voice="piper:es_MX-claude-high"', false)->assertSee('Daniela');
    }

    public function test_synthesis_returns_private_wav_audio(): void
    {
        $wav = "RIFF\x00\x00\x00\x00WAVE\x00audio";
        $this->mock(LocalSpeech::class, function (MockInterface $mock) use ($wav) {
            $mock->shouldReceive('isAvailable')->with('piper:es_AR-daniela-high')->once()->andReturnTrue();
            $mock->shouldReceive('synthesize')->with('Hola, ¿cómo estás?', 'piper:es_AR-daniela-high')->once()->andReturn($wav);
        });
        $response = $this->actingAs(User::factory()->create())->postJson(route('speech.store'), [
            'text' => 'Hola, ¿cómo estás?', 'voice' => 'piper:es_AR-daniela-high',
        ])->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->assertSame($wav, $response->getContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function test_synthesis_rejects_unknown_voices_and_oversized_or_missing_text(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson(route('speech.store'), ['voice' => 'piper:../../secret', 'text' => 'Hola'])->assertUnprocessable()->assertJsonValidationErrors('voice');
        $this->postJson(route('speech.store'), ['voice' => 'piper:es_AR-daniela-high', 'text' => str_repeat('a', 5001)])->assertUnprocessable()->assertJsonValidationErrors('text');
        $this->postJson(route('speech.store'), ['voice' => 'piper:es_AR-daniela-high'])->assertUnprocessable()->assertJsonValidationErrors('text');
    }

    public function test_missing_models_are_reported_without_running_the_engine(): void
    {
        $this->mock(LocalSpeech::class, function (MockInterface $mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturnFalse();
            $mock->shouldNotReceive('synthesize');
        });
        $this->actingAs(User::factory()->create())->postJson(route('speech.store'), [
            'voice' => 'piper:es_AR-daniela-high', 'text' => 'Hola',
        ])->assertUnprocessable();
    }

    public function test_engine_errors_do_not_expose_internal_details(): void
    {
        $this->mock(LocalSpeech::class, function (MockInterface $mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturnTrue();
            $mock->shouldReceive('synthesize')->once()->andThrow(new RuntimeException('private model path'));
        });
        $this->actingAs(User::factory()->create())->postJson(route('speech.store'), [
            'voice' => 'piper:es_AR-daniela-high', 'text' => 'Hola',
        ])->assertStatus(503)->assertDontSee('private model path');
    }

    public function test_guests_cannot_save_preferences_or_generate_audio(): void
    {
        $this->patch(route('profile.voice.update'), ['tts_voice' => 'auto'])->assertRedirect(route('login'));
        $this->postJson(route('speech.store'), ['text' => 'Hola', 'voice' => 'piper:es_AR-daniela-high'])->assertUnauthorized();
    }
}
