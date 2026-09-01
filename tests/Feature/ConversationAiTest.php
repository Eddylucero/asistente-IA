<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConversationAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_is_sent_to_groq_and_both_messages_are_saved(): void
    {
        Http::fake([
            'api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Gracias por compartirlo conmigo.'],
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $user->id]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Hola, ¿cómo te sientes hoy?',
        ]);

        $response = $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => 'Me siento un poco cansado.',
        ]);

        $response->assertOk()->assertJsonCount(2, 'messages');
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Me siento un poco cansado.',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Gracias por compartirlo conmigo.',
        ]);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => 'Me siento un poco cansado.',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && collect($request['messages'])->contains(fn ($message) => $message['role'] === 'system'
                && str_contains($message['content'], $user->name)
                && str_contains($message['content'], 'Distingue siempre la intención')
                && str_contains($message['content'], 'quién eres'))
            && collect($request['messages'])->contains(fn ($message) => $message['role'] === 'user'
                && $message['content'] === 'Me siento un poco cansado.')
            && $request['temperature'] === 0.3
            && $request['max_tokens'] === 350);
    }

    public function test_groq_failure_does_not_leave_a_user_message_saved(): void
    {
        Http::fake([
            'api.groq.com/openai/v1/chat/completions' => Http::response(['error' => 'unavailable'], 503),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => 'Este mensaje no debe quedar incompleto.',
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseMissing('messages', [
            'role' => 'user',
            'content' => 'Este mensaje no debe quedar incompleto.',
        ]);
    }

    public function test_message_is_saved_in_the_conversation_being_viewed(): void
    {
        Http::fake([
            'api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Respuesta de apoyo.']]],
            ]),
        ]);

        $user = User::factory()->create();
        $currentConversation = Conversation::create(['user_id' => $user->id, 'title' => 'Sesión actual']);
        $otherConversation = Conversation::create(['user_id' => $user->id, 'title' => 'Otra sesión']);

        $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => 'Quiero continuar esta conversación.',
            'conversation_id' => $currentConversation->id,
        ])->assertOk();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $currentConversation->id,
            'content' => 'Quiero continuar esta conversación.',
        ]);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $otherConversation->id,
            'content' => 'Quiero continuar esta conversación.',
        ]);
    }

    public function test_basic_identity_response_does_not_call_groq(): void
    {
        Http::fake();

        $user = User::factory()->create(['name' => 'Anahi']);

        $response = $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => '¿Quién soy?',
        ]);

        $response->assertOk()->assertJsonPath(
            'messages.1.content',
            'Eres Anahi, y estoy aquí para ayudarte. Cuéntame, ¿qué te incomoda o te hace sentir mal?'
        );
        Http::assertNothingSent();
        $this->assertDatabaseHas('messages', [
            'role' => 'assistant',
            'content' => 'Eres Anahi, y estoy aquí para ayudarte. Cuéntame, ¿qué te incomoda o te hace sentir mal?',
        ]);
    }

    public function test_basic_assistant_identity_response_does_not_call_groq(): void
    {
        Http::fake();

        $user = User::factory()->create(['name' => 'Anahi']);

        $response = $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => '¿Quién eres?',
        ]);

        $response->assertOk()->assertJsonPath(
            'messages.1.content',
            'Soy Mente, tu asistente de apoyo psicológico. Estoy aquí para escucharte y acompañarte; ¿qué te gustaría contarme?'
        );
        Http::assertNothingSent();
    }

    public function test_date_questions_are_answered_locally_without_calling_groq(): void
    {
        Date::setTestNow('2026-08-28 12:00:00');
        Http::fake();

        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => '¿Qué día es hoy?',
        ]);

        $response->assertOk()->assertJsonPath(
            'messages.1.content',
            'Hoy es viernes 28 de agosto de 2026.'
        );
        Http::assertNothingSent();
        Date::setTestNow();
    }

    public function test_date_and_name_questions_are_answered_together_locally(): void
    {
        Date::setTestNow('2026-08-28 12:00:00');
        Http::fake();

        $user = User::factory()->create(['name' => 'Anahi']);
        $response = $this->actingAs($user)->postJson(route('conversations.messages.store'), [
            'content' => '¿Qué día es hoy y cómo me llamo?',
        ]);

        $response->assertOk()->assertJsonPath(
            'messages.1.content',
            'Hoy es viernes 28 de agosto de 2026 y te llamas Anahi. ¿En qué más puedo ayudarte?'
        );
        Http::assertNothingSent();
        Date::setTestNow();
    }
}
