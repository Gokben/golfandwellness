<?php

namespace App\Http\Controllers;

final class AppReleaseController extends Controller
{
    public function __invoke()
    {
        $path = public_path('build/manifest.json');
        $manifest = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $asset = basename($manifest['resources/js/app.ts']['file'] ?? '');
        $releasePath = public_path('build/release.json');
        $release = is_file($releasePath) ? json_decode(file_get_contents($releasePath), true) : [];
        $version = $release['version'] ?? null;
        return response()->json(['asset' => $asset, 'version' => is_string($version) && preg_match('/^\d{5}\.\d{2,}$/', $version) ? $version : null])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
