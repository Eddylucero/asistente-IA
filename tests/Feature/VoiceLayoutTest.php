<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_panel_uses_the_viewport_aware_height(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString('h-screen-safe', $html);
        $this->assertStringNotContainsString('h-screen w-full', $html);
    }

    public function test_the_avatar_is_capped_by_viewport_height(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString('h-[min(14rem,32vh)]', $html);
        $this->assertStringContainsString('md:h-[min(18rem,40vh)]', $html);
        $this->assertStringContainsString('w-[min(80vw,38vh,600px)]', $html);
    }

    public function test_the_screen_neither_scrolls_nor_clips_its_chrome(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString('flex h-full min-h-0 flex-col overflow-hidden', $html);
        $this->assertStringContainsString('shrink-0', $html);
        $this->assertStringContainsString('flex min-h-0 flex-1', $html);
    }

    public function test_the_heading_shrinks_on_small_screens(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString('text-headline-lg sm:text-display-lg', $html);
    }

    public function test_the_identity_block_swaps_position_by_breakpoint(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertSame(2, substr_count($html, 'Sesión activa'));
        $this->assertStringContainsString('hidden sm:flex', $html);
        $this->assertStringContainsString('shrink-0 sm:hidden', $html);
        $this->assertStringContainsString('gap-4', $html);
    }

    public function test_the_background_decor_is_clipped_by_the_screen_root(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString(
            'relative flex h-full min-h-0 flex-col overflow-hidden',
            $html,
        );
    }

    public function test_the_panel_main_does_not_scroll(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString('h-screen-safe w-full overflow-hidden', $html);
    }

    public function test_the_status_is_addressable_in_every_instance(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringNotContainsString('id="connection-status"', $html);
        $this->assertSame(2, substr_count($html, 'data-connection-status>'));
    }

    public function test_the_screen_exposes_its_hooks_instead_of_inline_scripts(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/voz')->getContent();

        $this->assertStringContainsString('data-voice', $html);
        $this->assertStringContainsString('data-voice-button', $html);
        $this->assertStringContainsString('data-voice-icon', $html);
        $this->assertStringContainsString('data-voice-transcript', $html);
        $this->assertStringContainsString('data-endpoint="'.route('voice.messages.store').'"', $html);
        $this->assertStringNotContainsString('webkitSpeechRecognition', $html);
    }
}
