<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_toggle_is_present_in_the_main_layout_header(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/inicio');

        $response->assertStatus(200);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('light_mode', false);
        $response->assertSee('dark_mode', false);
    }

    public function test_the_toggle_is_present_in_the_panel_sidebar(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/conversacion');

        $response->assertStatus(200);
        $response->assertSee('data-theme-toggle', false);
    }

    public function test_every_layout_applies_the_theme_before_painting(): void
    {
        $user = User::factory()->create();

        $guest = $this->get('/login');
        $guest->assertStatus(200);
        $guest->assertSee("localStorage.getItem('theme')", false);

        $main = $this->actingAs($user)->get('/inicio');
        $main->assertSee("localStorage.getItem('theme')", false);

        $panel = $this->actingAs($user)->get('/conversacion');
        $panel->assertSee("localStorage.getItem('theme')", false);

        $app = $this->actingAs($user)->get('/profile');
        $app->assertSee("localStorage.getItem('theme')", false);
    }
}
