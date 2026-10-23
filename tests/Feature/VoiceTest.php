<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_voice_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('voice.index'));

        $response->assertOk();
    }

    public function test_voice_transcription_is_saved_in_the_authenticated_users_conversation(): void
    {
        Http::fake(['api.groq.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Me alegra escucharte.']]],
        ])]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('voice.messages.store'), [
            'content' => 'Hoy me siento tranquilo.',
        ]);

        $response->assertCreated()->assertJsonPath('message.content', 'Hoy me siento tranquilo.');
        $response->assertJsonCount(2, 'messages')->assertJsonPath('messages.1.content', 'Me alegra escucharte.');
        $this->assertDatabaseHas('messages', [
            'role' => 'user',
            'content' => 'Hoy me siento tranquilo.',
        ]);
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('messages', ['role' => 'assistant', 'content' => 'Me alegra escucharte.']);
        $this->get(route('voice.index'))->assertDontSee('Hoy me siento tranquilo.')->assertDontSee('Me alegra escucharte.');
        $this->get(route('conversations.index'))->assertSee('Hoy me siento tranquilo.')->assertSee('Me alegra escucharte.');
    }

    public function test_voice_continues_the_selected_conversation_and_keeps_its_context(): void
    {
        Http::fake(['api.groq.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Seguimos conversando.']]],
        ])]);
        $user = User::factory()->create();
        $selected = $user->conversations()->create();
        $selected->messages()->create(['role' => 'user', 'content' => 'Contexto anterior.']);
        $user->conversations()->create();
        $this->actingAs($user)->get(route('voice.index', ['conversation' => $selected->id]))
            ->assertOk()->assertSee('data-conversation="'.$selected->id.'"', false)->assertDontSee('Contexto anterior.');
        $this->postJson(route('voice.messages.store'), [
            'content' => 'Quiero seguir con lo anterior.', 'conversation_id' => $selected->id,
        ])->assertCreated()->assertJsonPath('conversation.id', $selected->id);
        Http::assertSent(fn ($request) => collect($request['messages'])->contains(
            fn ($message) => $message['content'] === 'Contexto anterior.'
        ));
        $this->assertSame(3, $selected->messages()->count());
    }

    public function test_voice_rejects_other_users_conversations(): void
    {
        Http::preventStrayRequests();
        $other = User::factory()->create()->conversations()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('voice.index', ['conversation' => $other->id]))->assertNotFound();
        $this->postJson(route('voice.messages.store'), [
            'content' => 'No debe guardarse.', 'conversation_id' => $other->id,
        ])->assertStatus(502);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_voice_failure_rolls_back_and_empty_transcriptions_are_rejected(): void
    {
        $this->mock(Assistant::class, function ($mock) {
            $mock->shouldReceive('reply')->once()->andThrow(new \RuntimeException('Offline'));
        });
        $this->actingAs(User::factory()->create())->postJson(route('voice.messages.store'), ['content' => ''])
            ->assertUnprocessable();
        $this->postJson(route('voice.messages.store'), ['content' => 'Mi frase pendiente.'])->assertStatus(502);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_voice_can_start_a_new_chat_and_requires_authentication(): void
    {
        $this->get(route('voice.index'))->assertRedirect(route('login'));
        $this->postJson(route('voice.messages.store'), ['content' => 'Hola'])->assertUnauthorized();
        $user = User::factory()->create();
        $old = $user->conversations()->create();
        $old->messages()->create(['role' => 'user', 'content' => 'Mensaje anterior privado.']);
        $this->actingAs($user)->get(route('voice.index', ['new' => 1]))
            ->assertOk()->assertDontSee('Mensaje anterior privado.');
        $this->postJson(route('voice.messages.store'), ['content' => '¿Quién eres?', 'new_conversation' => true])
            ->assertCreated()->assertJsonCount(2, 'messages');
        $this->assertSame(2, $user->conversations()->count());
    }
}
