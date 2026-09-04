<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelDrawerTest extends TestCase
{
    use RefreshDatabase;

    public static function panelScreens(): array
    {
        return [
            'conversación' => ['/conversacion'],
            'voz' => ['/voz'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panelScreens')]
    public function test_the_panel_screens_expose_the_drawer_on_mobile(string $url): void
    {
        $response = $this->actingAs(User::factory()->create())->get($url);

        $response->assertStatus(200);
        $response->assertSee('data-drawer-toggle', false);
        $response->assertSee('data-drawer-scrim', false);
        $response->assertSee('data-drawer-close', false);
        $response->assertSee('id="panel-drawer"', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panelScreens')]
    public function test_the_drawer_slides_on_mobile_but_stays_fixed_on_desktop(string $url): void
    {
        $response = $this->actingAs(User::factory()->create())->get($url);

        $response->assertSee('-translate-x-full', false);
        $response->assertSee('lg:translate-x-0', false);
        $response->assertSee('transition-transform duration-calm ease-calm', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panelScreens')]
    public function test_the_drawer_and_its_veil_are_translucent(string $url): void
    {
        $response = $this->actingAs(User::factory()->create())->get($url);

        $response->assertSee('bg-surface-container/55', false);
        $response->assertSee('backdrop-blur-3xl', false);
        $response->assertSee('bg-on-surface/25', false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panelScreens')]
    public function test_logout_switches_place_between_mobile_and_desktop(string $url): void
    {
        $html = $this->actingAs(User::factory()->create())->get($url)->getContent();

        $aside = $this->aside($html);
        $outside = str_replace($aside, '', $html);

        $this->assertStringContainsString('hidden lg:block', $aside);
        $this->assertStringContainsString('Salir', $aside);

        $this->assertStringContainsString('aria-label="Cerrar sesión"', $outside);
        $this->assertStringContainsString('lg:hidden', $outside);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panelScreens')]
    public function test_the_theme_toggle_lives_only_in_the_header(string $url): void
    {
        $html = $this->actingAs(User::factory()->create())->get($url)->getContent();

        $aside = $this->aside($html);
        $outside = str_replace($aside, '', $html);

        $this->assertStringNotContainsString('data-theme-toggle', $aside);
        $this->assertStringContainsString('data-theme-toggle', $outside);
    }

    public function test_the_chat_header_has_no_dead_overflow_button(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/conversacion')->getContent();

        $this->assertStringNotContainsString('more_vert', $html);
    }

    public function test_only_the_current_conversation_section_is_active(): void
    {
        $user = User::factory()->create();
        $history = $this->actingAs($user)->get(route('conversations.history'))->getContent();
        $conversation = $this->actingAs($user)->get(route('conversations.index'))->getContent();

        $this->assertMatchesRegularExpression('/<a class="[^"]*bg-primary\/10[^"]*"[^>]*href="[^"]*\/historial"/', $history);
        $this->assertDoesNotMatchRegularExpression('/<a class="[^"]*bg-primary\/10[^"]*"[^>]*href="[^"]*\/conversacion"/', $history);
        $this->assertMatchesRegularExpression('/<a class="[^"]*bg-primary\/10[^"]*"[^>]*href="[^"]*\/conversacion"/', $conversation);
        $this->assertDoesNotMatchRegularExpression('/<a class="[^"]*bg-primary\/10[^"]*"[^>]*href="[^"]*\/historial"/', $conversation);
    }

    public function test_conversation_view_does_not_expose_the_home_confirmation_trigger(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/conversacion')->getContent();

        $this->assertStringNotContainsString('data-new-conversation', $html);
        $this->assertStringContainsString('data-start-new-conversation', $html);
    }

    private function aside(string $html): string
    {
        preg_match('/<aside.*?<\/aside>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
