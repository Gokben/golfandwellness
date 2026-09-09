<?php

namespace Tests\Feature;

use App\Models\SetupUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.setup_mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000003_create_setup_users.php'))->up();
        (require database_path('migrations/2026_09_02_000004_add_role_to_setup_users.php'))->up();
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function profile(): array
    {
        return ['name' => 'Test', 'surname' => 'User', 'username' => 'test.user', 'telephone' => '00123', 'email' => 'test@golf', 'active' => true, 'password' => 'Test-Only-Secret9'];
    }

    public function test_reference_profiles_have_no_invented_passwords_and_no_secrets_are_returned(): void
    {
        $data = $this->getJson('/api/setup-users')->assertOk()->assertJsonCount(4, 'users')->json('users');
        $this->assertSame(['rana', 'merve', 'ozgul', 'admin2'], array_column($data, 'username'));
        $this->assertSame('Sürmeli', $data[0]['surname']);
        foreach ($data as $row) $this->assertArrayNotHasKey('password', $row);
        $this->assertSame(4, SetupUser::whereNull('password')->count());
        $this->assertSame(4, SetupUser::whereNull('role')->count());
        foreach ($data as $row) $this->assertNull($row['role']);
    }

    public function test_password_is_hashed_preserved_when_blank_and_changed_only_when_provided(): void
    {
        $fields = $this->profile();
        $created = $this->postJson('/api/setup-users', $fields)->assertCreated()->assertJsonMissingPath('user.password')->json('user');
        $url = '/api/setup-users/'.$created['id'];
        $hash = SetupUser::findOrFail($created['id'])->password;
        $this->assertNotSame($fields['password'], $hash);
        $this->assertTrue(Hash::check($fields['password'], $hash));
        $this->assertSame('00123', $created['telephone']);
        $fields['password'] = '';
        $fields['active'] = false;
        $fields['surname'] = 'Changed';
        $this->putJson($url, [...$fields, 'version' => 1])->assertOk()->assertJsonPath('user.active', false)->assertJsonPath('user.version', 2)->assertJsonMissingPath('user.password');
        $this->assertSame($hash, SetupUser::findOrFail($created['id'])->password);
        $fields['password'] = 'Another-Test-Secret9';
        $this->putJson($url, [...$fields, 'version' => 2])->assertOk();
        $this->assertTrue(Hash::check($fields['password'], SetupUser::findOrFail($created['id'])->password));
        $this->getJson('/api/setup-users')->assertDontSee($hash)->assertDontSee($fields['password']);
    }

    public function test_validation_and_case_insensitive_duplicate_usernames(): void
    {
        $fields = $this->profile();
        foreach ([['password' => 'short'], ['password' => str_repeat('ü', 40)], ['password' => ''], ['username' => 'RANA'], ['username' => 'invalid user'], ['email' => 'no-at-sign'], ['name' => '']] as $change) {
            $this->postJson('/api/setup-users', array_replace($fields, $change))->assertStatus(422);
        }
        $this->assertSame(4, SetupUser::count());
    }

    public function test_stale_edits_and_deletes_are_rejected_and_deletion_is_recoverable(): void
    {
        $fields = $this->profile();
        $created = $this->postJson('/api/setup-users', $fields)->assertCreated()->json('user');
        $url = '/api/setup-users/'.$created['id'];
        unset($fields['password']);
        $this->putJson($url, [...$fields, 'version' => 1])->assertOk();
        $this->putJson($url, [...$fields, 'name' => 'Stale', 'version' => 1])->assertStatus(409);
        $this->deleteJson($url, ['version' => 1])->assertStatus(409);
        $this->deleteJson($url, ['version' => 2])->assertOk()->assertJsonPath('deleted', true);
        $this->assertNull(SetupUser::find($created['id']));
        $deleted = SetupUser::withTrashed()->findOrFail($created['id']);
        $this->assertFalse($deleted->active);
        $this->assertSame('Test', $deleted->name);
        $this->getJson('/api/setup-users')->assertJsonCount(4, 'users');
        $this->putJson($url, [...$fields, 'version' => 3])->assertNotFound();
    }

    public function test_access_is_local_only_and_json_writes_are_required(): void
    {
        $this->withHeaders(['X-Requested-With' => ''])->postJson('/api/setup-users', $this->profile())->assertStatus(415);
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Origin' => 'https://untrusted.example'])->getJson('/api/setup-users')->assertForbidden();
        $this->withHeaders(['Origin' => 'http://127.0.0.1:8093']);
        $this->app->detectEnvironment(fn () => 'production');
        $this->getJson('/api/setup-users')->assertForbidden();
    }

    public function test_roles_save_on_create_and_update_without_changing_other_profile_fields(): void
    {
        $fields = $this->profile();
        $created = $this->postJson('/api/setup-users', [...$fields, 'role' => 'Rezervasyon'])->assertCreated()->assertJsonPath('user.role', 'Rezervasyon')->json('user');
        $url = '/api/setup-users/'.$created['id'];
        $hash = SetupUser::findOrFail($created['id'])->password;
        unset($fields['password']);
        $version = 1;
        foreach (['Admin', 'Rezervasyon', 'Muhasebe', 'Operasyon'] as $role) {
            $this->putJson($url, [...$fields, 'role' => $role, 'version' => $version++])->assertOk()->assertJsonPath('user.role', $role);
            $this->assertSame($role, SetupUser::findOrFail($created['id'])->role);
        }
        $this->putJson($url, [...$fields, 'version' => $version++])->assertOk()->assertJsonPath('user.role', 'Operasyon');
        $this->putJson($url, [...$fields, 'role' => 'SuperAdmin', 'version' => $version])->assertStatus(422);
        $this->putJson($url, [...$fields, 'role' => ['Admin'], 'version' => $version])->assertStatus(422);
        $this->getJson('/api/setup-users')->assertJsonPath('users.4.role', 'Operasyon');
        $this->putJson($url, [...$fields, 'role' => null, 'version' => $version])->assertOk()->assertJsonPath('user.role', null);
        $saved = SetupUser::findOrFail($created['id']);
        $this->assertSame($hash, $saved->password);
        $this->assertTrue($saved->active);
        $this->assertSame('00123', $saved->telephone);
        $this->assertSame(4, SetupUser::where('id', '!=', $created['id'])->whereNull('role')->count());
    }
}
