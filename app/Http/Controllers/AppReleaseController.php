<?php

namespace App\Http\Controllers;

final class AppReleaseController extends Controller
{
    public function __invoke()
    {
        $path = public_path('build/manifest.json');
        $manifest = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $asset = basename($manifest['resources/js/app.ts']['file'] ?? '');
        return response()->json(['asset' => $asset])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
