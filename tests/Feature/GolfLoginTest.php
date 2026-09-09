<?php

namespace Tests\Feature;

use App\Models\SetupUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GolfLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.setup_mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000003_create_setup_users.php'))->up();
        (require database_path('migrations/2026_09_02_000004_add_role_to_setup_users.php'))->up();
        SetupUser::where('username', 'admin2')->update(['role' => 'Admin', 'password' => Hash::make('Test-only-password!')]);
        $this->app->detectEnvironment(fn () => 'production');
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function signIn(): void
    {
        $this->withSession(['_token' => 'test-csrf'])->post('/login', [
            '_token' => 'test-csrf', 'username' => 'admin2', 'password' => 'Test-only-password!',
        ])->assertRedirect('/desktop');
    }

    public function test_live_desktop_aliases_and_every_api_require_authentication(): void
    {
        $this->get('/desktop')->assertRedirect('/login');
        $this->get('/desktop.html')->assertRedirect('/login');
        foreach (['setup-users', 'setup-records/golf-games', 'setup-records/golf-courses', 'setup-records/agency-extras', 'exchange-rates'] as $path) {
            $this->getJson('/api/'.$path)->assertForbidden();
        }
        $this->get('/login')->assertOk()->assertSee('name="username"', false)->assertSee('name="_token"', false)->assertDontSee('event.preventDefault()');
    }

    public function test_only_explicit_admin_can_login_and_password_must_match(): void
    {
        SetupUser::where('username', 'rana')->update(['role' => 'Admin', 'password' => Hash::make('Test-only-password!')]);
        foreach ([['admin2', 'wrong'], ['rana', 'Test-only-password!']] as [$username, $password]) {
            $this->withSession(['_token' => 'test-csrf'])->post('/login', ['_token' => 'test-csrf', 'username' => $username, 'password' => $password])->assertSessionHasErrors('username');
            $this->assertGuest('golf');
        }
        $this->signIn();
        $this->assertAuthenticatedAs(SetupUser::where('username', 'admin2')->first(), 'golf');
        $this->getJson('/api/setup-users')->assertOk()->assertJsonMissingPath('users.3.password');
        $this->get('/desktop')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_csrf_is_required_for_live_login_and_writes(): void
    {
        $this->post('/login', ['username' => 'admin2', 'password' => 'Test-only-password!'])->assertStatus(419);
        $this->signIn();
        $this->putJson('/api/setup-users/4', [])->assertStatus(419);
        $this->postJson('/api/exchange-rates/refresh', [])->assertStatus(419);
        $this->withHeaders(['X-CSRF-TOKEN' => session()->token()])->putJson('/api/setup-users/4', [])->assertStatus(422);
    }

    public function test_other_origin_and_live_loopback_do_not_bypass_authorization(): void
    {
        $this->withHeaders(['Origin' => 'http://127.0.0.1:8093'])->getJson('/api/setup-users')->assertForbidden();
        $this->signIn();
        $this->withHeaders(['Origin' => 'https://untrusted.example'])->getJson('/api/setup-users')->assertForbidden();
    }

    public function test_disabled_or_demoted_account_and_password_change_revoke_existing_sessions(): void
    {
        foreach ([['active' => false], ['role' => 'Operasyon'], ['password' => Hash::make('Different-test-only-password!')]] as $change) {
            SetupUser::where('username', 'admin2')->update(['active' => true, 'role' => 'Admin', 'password' => Hash::make('Test-only-password!')]);
            Auth::forgetGuards();
            $this->signIn();
            SetupUser::where('username', 'admin2')->update($change);
            Auth::forgetGuards();
            $this->getJson('/api/setup-users')->assertForbidden();
        }
    }

    public function test_logout_invalidates_session_and_admin_cannot_remove_own_access(): void
    {
        $this->signIn();
        $this->withHeaders(['X-CSRF-TOKEN' => session()->token()]);
        $this->deleteJson('/api/setup-users/4', ['version' => 1])->assertStatus(422);
        $user = SetupUser::findOrFail(4)->only(['name', 'surname', 'username', 'email', 'telephone', 'active', 'role', 'version']);
        $this->putJson('/api/setup-users/4', [...$user, 'active' => false])->assertStatus(422);
        $this->putJson('/api/setup-users/4', [...$user, 'role' => 'Operasyon'])->assertStatus(422);
        $this->putJson('/api/setup-users/4', [...$user, 'role' => null])->assertStatus(422);
        $this->post('/logout', ['_token' => session()->token()])->assertRedirect('/login');
        $this->assertGuest('golf');
        $this->getJson('/api/setup-users')->assertForbidden();
    }

    public function test_failed_login_attempts_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withSession(['_token' => 'test-csrf'])->post('/login', ['_token' => 'test-csrf', 'username' => 'admin2', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->withSession(['_token' => 'test-csrf'])->post('/login', ['_token' => 'test-csrf', 'username' => 'admin2', 'password' => 'Test-only-password!'])->assertSessionHasErrors('username');
        $this->assertGuest('golf');
    }
}
