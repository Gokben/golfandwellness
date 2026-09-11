<?php

namespace App\Http\Controllers;

use App\Models\SetupUser;
use App\Support\GolfAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class GolfLoginController extends Controller
{
    public function form(Request $request)
    {
        if (GolfAccess::authenticated($request)) return redirect()->route('home');
        return response()->view('auth.login')->header('Cache-Control', 'no-store');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:80', 'password' => 'required|string|max:72']);
        $username = strtolower(trim($data['username']));
        $key = 'golf-login:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => 'Çok fazla giriş denemesi. Bir dakika sonra tekrar deneyin.']);
        }
        RateLimiter::hit($key, 60);
        $user = SetupUser::where('username', $username)->first();
        if (!GolfAccess::eligible($user) || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => 'Kullanıcı adı veya şifre hatalı ya da canlı erişim yetkiniz yok.']);
        }
        RateLimiter::clear($key);
        Auth::guard('golf')->login($user);
        $request->session()->regenerate();
        $request->session()->put('golf_password_version', hash('sha256', $user->password));
        return redirect()->route('home');
    }

    public function desktop(Request $request)
    {
        if (!GolfAccess::localPreview($request) && !GolfAccess::authenticated($request)) return redirect()->route('login');
        return response()->view('app')->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request)
    {
        Auth::guard('golf')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
