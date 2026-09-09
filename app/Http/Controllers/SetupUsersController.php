<?php

namespace App\Http\Controllers;

use App\Models\SetupUser;
use App\Support\GolfAccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SetupUsersController extends Controller
{
    private function guard(Request $request): void
    {
        GolfAccess::authorizeApi($request);
    }

    private function payload(SetupUser $user): array
    {
        return $user->only(['id', 'name', 'surname', 'username', 'telephone', 'email', 'active', 'role', 'version']);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $users = SetupUser::orderBy('id')->get(['id', 'name', 'surname', 'username', 'telephone', 'email', 'active', 'role', 'version']);
        return response()->json(['users' => $users->map(fn ($user) => $this->payload($user))])->header('Cache-Control', 'no-store');
    }

    private function fields(Request $request, ?int $id = null): array
    {
        if (is_string($request->input('username'))) $request->merge(['username' => strtolower(trim($request->input('username')))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'surname' => 'nullable|string|max:100',
            'username' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[a-z0-9][a-z0-9._-]*$/', Rule::unique('setup_mysql.setup_users', 'username')->ignore($id)],
            'telephone' => 'nullable|string|max:40',
            // Preserve reference contact addresses such as sales3@golf; no mail is sent.
            'email' => ['nullable', 'string', 'max:254', 'regex:/^[^\s@]+@[^\s@]+$/u'],
            'active' => 'required|boolean',
            'role' => ['sometimes', 'nullable', 'string', Rule::in(SetupUser::ROLES)],
            'password' => [$id === null ? 'required' : 'nullable', 'string', 'min:8', function ($attribute, $value, $fail) {
                if (strlen($value) > 72) $fail('Şifre en fazla 72 bayt olabilir.');
            }],
        ], [
            'username.unique' => 'Bu kullanıcı adı zaten kullanılıyor.',
            'username.regex' => 'Kullanıcı adında yalnızca harf (a-z), rakam, nokta, alt çizgi ve tire kullanın.',
            'password.required' => 'Yeni kullanıcı için şifre girin.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'email.regex' => 'Geçerli bir e-posta adresi girin.',
            'role.in' => 'Kullanıcı rolü Admin, Rezervasyon, Muhasebe veya Operasyon olmalıdır.',
        ]);
        foreach (['name', 'surname', 'telephone', 'email'] as $field) $data[$field] = trim($data[$field] ?? '');
        return $data;
    }

    private function save(SetupUser $user): void
    {
        try { $user->save(); }
        catch (QueryException $error) {
            if (in_array((string) $error->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['username' => 'Bu kullanıcı adı zaten kullanılıyor.']);
            }
            throw $error;
        }
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $data = $this->fields($request);
        $user = new SetupUser;
        $user->fill(collect($data)->except('password')->all());
        $user->password = Hash::make($data['password']);
        $user->version = 1;
        $this->save($user);
        return response()->json(['user' => $this->payload($user)], 201)->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, int $id)
    {
        $this->guard($request);
        $existing = SetupUser::findOrFail($id);
        $data = $this->fields($request, $id);
        if (!GolfAccess::localPreview($request) && $existing->username === 'admin2') {
            $nextRole = array_key_exists('role', $data) ? $data['role'] : $existing->role;
            abort_unless($data['username'] === 'admin2' && $data['active'] && $nextRole === 'Admin', 422, 'Tek canlı yönetici hesabının erişimi kapatılamaz.');
        }
        $version = $request->validate(['version' => 'required|integer|min:1'])['version'];
        return DB::connection('setup_mysql')->transaction(function () use ($data, $id, $version) {
            $user = SetupUser::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($user->version !== (int) $version) return response()->json(['message' => 'Kullanıcı başka bir pencerede değişti. Listeyi yenileyip kaydı yeniden açın.'], 409);
            $user->fill(collect($data)->except('password')->all());
            if (!empty($data['password'])) $user->password = Hash::make($data['password']);
            $user->version++;
            $this->save($user);
            return response()->json(['user' => $this->payload($user)])->header('Cache-Control', 'no-store');
        });
    }

    public function destroy(Request $request, int $id)
    {
        $this->guard($request);
        if (!GolfAccess::localPreview($request)) abort_if(SetupUser::findOrFail($id)->username === 'admin2', 422, 'Tek canlı yönetici hesabı silinemez.');
        $version = $request->validate(['version' => 'required|integer|min:1'])['version'];
        return DB::connection('setup_mysql')->transaction(function () use ($id, $version) {
            $user = SetupUser::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($user->version !== (int) $version) return response()->json(['message' => 'Kullanıcı başka bir pencerede değişti. Listeyi yenileyin.'], 409);
            $user->active = false;
            $user->version++;
            $this->save($user);
            $user->delete();
            return response()->json(['deleted' => true]);
        });
    }
}
