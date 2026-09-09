<?php

namespace App\Support;

use App\Models\SetupUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class GolfAccess
{
    public static function localPreview(Request $request): bool
    {
        $loopback = ['localhost', '127.0.0.1', '::1', '[::1]'];
        return app()->environment(['local', 'testing'])
            && in_array($request->ip(), ['127.0.0.1', '::1'], true)
            && in_array($request->getHost(), $loopback, true)
            && (!$request->header('Origin') || in_array(parse_url($request->header('Origin'), PHP_URL_HOST), $loopback, true));
    }

    public static function eligible(?SetupUser $user): bool
    {
        // Temporary, explicit launch allow-list. Role permissions will be specified separately.
        return $user && $user->username === 'admin2' && $user->active
            && $user->role === 'Admin' && !empty($user->password) && !$user->trashed();
    }

    public static function authenticated(Request $request): bool
    {
        $user = Auth::guard('golf')->user();
        return self::eligible($user) && hash_equals(
            hash('sha256', $user->password), (string) $request->session()->get('golf_password_version', '')
        );
    }

    public static function authorizeApi(Request $request): void
    {
        if (!self::localPreview($request)) {
            abort_unless(self::authenticated($request), 403, 'Bu işlem için yetkili kullanıcı girişi gereklidir.');
            if ($origin = $request->header('Origin')) {
                abort_unless($origin === $request->getSchemeAndHttpHost(), 403);
            }
        }
        if (!$request->isMethod('GET')) {
            abort_unless($request->isJson() && $request->header('X-Requested-With') === 'XMLHttpRequest', 415);
        }
    }
}
