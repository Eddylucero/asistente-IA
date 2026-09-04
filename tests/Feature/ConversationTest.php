<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_their_conversation_without_creating_one_immediately(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('conversations.index'));

        $response->assertOk();
        $this->assertDatabaseMissing('conversations', [
            'user_id' => $user->id,
        ]);
    }

    public function test_conversation_is_created_only_when_the_user_sends_their_first_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('conversations.messages.store'), [
                'content' => 'Me siento ansioso y necesito hablar.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => 'Calma para la ansiedad',
        ]);
    }

    public function test_conversation_title_is_a_thematic_label_instead_of_the_users_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('conversations.messages.store'), [
                'content' => 'Me ayudas con mi ansiedad al no poder hacer tareas',
            ])
            ->assertOk();

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => 'Apoyo para la ansiedad con tareas',
        ]);
        $this->assertDatabaseMissing('conversations', [
            'user_id' => $user->id,
            'title' => 'Me ayudas con mi ansiedad al no poder hacer tareas',
        ]);
    }

    public function test_creating_a_new_conversation_does_not_reuse_the_latest_one(): void
    {
        $user = User::factory()->create();
        $previous = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Conversación anterior',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('conversations.messages.store'), [
                'content' => 'No puedo dormir y necesito descansar mejor.',
                'new_conversation' => true,
            ])
            ->assertOk()
            ->assertJsonPath('conversation.title', 'Apoyo para mejorar el descanso');

        $this->assertDatabaseCount('conversations', 2);
        $this->assertDatabaseHas('conversations', [
            'id' => $previous->id,
            'title' => 'Conversación anterior',
        ]);
    }

    public function test_conversation_view_renders_only_the_authenticated_users_messages(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $user->id]);
        $otherConversation = Conversation::create(['user_id' => $otherUser->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Mensaje persistido visible',
        ]);
        Message::create([
            'conversation_id' => $otherConversation->id,
            'role' => 'user',
            'content' => 'Mensaje de otro usuario',
        ]);

        $response = $this->actingAs($user)->get(route('conversations.index'));

        $response->assertOk()->assertSee('Mensaje persistido visible')->assertDontSee('Mensaje de otro usuario');
    }

    public function test_authenticated_user_can_browse_their_conversation_history(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Ansiedad y descanso',
        ]);
        Conversation::create([
            'user_id' => $otherUser->id,
            'title' => 'Conversación privada',
        ]);

        $response = $this->actingAs($user)->get(route('conversations.history'));

        $response->assertOk()
            ->assertSee('Ansiedad y descanso')
            ->assertDontSee('Conversación privada')
            ->assertSee(route('conversations.show', $conversation), false);
    }

    public function test_history_description_defines_the_users_topic_without_showing_ai_response(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $user->id, 'title' => 'Calma para la ansiedad']);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Me ayudas con mi ansiedad al no poder hacer tareas',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Haz una pausa. Practica una respiración lenta y divide la tarea en un paso pequeño.',
        ]);

        $response = $this->actingAs($user)->get(route('conversations.history'));

        $response->assertOk()
            ->assertSee('Ansiedad relacionada con la dificultad para avanzar en las tareas.')
            ->assertDontSee('Haz una pausa. Practica una respiración lenta y divide la tarea en un paso pequeño.')
            ->assertDontSee('Me ayudas con mi ansiedad al no poder hacer tareas');
    }

    public function test_user_can_open_a_fresh_empty_conversation_without_creating_a_blank_record(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Conversación anterior',
        ]);

        $response = $this->actingAs($user)->get(route('conversations.create'));

        $response->assertOk()
            ->assertSee('Sesión Actual')
            ->assertSee('Comienza escribiendo cómo te sientes.')
            ->assertSee('Sugerencias')
            ->assertSee('Prefiero hablarlo')
            ->assertSee('data-chat-suggestion-toggle', false)
            ->assertSee('data-chat-suggestion-list', false);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => 'Conversación anterior',
        ]);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_user_can_delete_their_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Conversación para eliminar',
        ]);

        $response = $this->actingAs($user)->delete(route('conversations.destroy', $conversation));

        $response->assertRedirect(route('conversations.history'))
            ->assertSessionHas('swal', [
                'icon' => 'success',
                'title' => 'Eliminado correctamente',
            ]);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_user_cannot_delete_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::create(['user_id' => $otherUser->id]);

        $this->actingAs($user)
            ->delete(route('conversations.destroy', $conversation))
            ->assertNotFound();

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
    }
}
