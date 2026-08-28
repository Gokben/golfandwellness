<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_home_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('golfandwellness');
    }

    public function test_login_and_desktop_preview_are_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Kullanıcı Girişi');
        $this->get('/preview')->assertRedirect('/');
    }
}
