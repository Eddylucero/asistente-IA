<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('voice.messages.store'), [
            'content' => 'Hoy me siento tranquilo.',
        ]);

        $response->assertCreated()->assertJsonPath('message.content', 'Hoy me siento tranquilo.');
        $this->assertDatabaseHas('messages', [
            'role' => 'user',
            'content' => 'Hoy me siento tranquilo.',
        ]);
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
        ]);
    }
}
