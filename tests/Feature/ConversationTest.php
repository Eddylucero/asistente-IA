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

    public function test_authenticated_user_can_open_their_conversation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('conversations.index'));

        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
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

    public function test_user_can_start_a_new_conversation_without_losing_history(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Conversación anterior',
        ]);

        $response = $this->actingAs($user)->get(route('conversations.create'));

        $newConversation = Conversation::where('user_id', $user->id)
            ->whereKeyNot($conversation->id)
            ->first();

        $response->assertRedirect(route('conversations.show', $newConversation));
        $this->assertNotNull($newConversation);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'title' => 'Conversación anterior',
        ]);
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
