<?php
// Local, UI-only deployment; never includes credentials, data, migrations or PHP controllers.
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !class_exists(ZipArchive::class)) exit(1);
$root = dirname(__DIR__);
$out = $root.'/storage/releases/20260903-warnings';
if (!is_dir($out)) mkdir($out, 0700, true);
$path = $out.'/golf-warnings-20260903.zip';
if (file_exists($path)) throw new RuntimeException('Reviewed package already exists.');
$original = new ZipArchive(); $previous = new ZipArchive();
if ($original->open($root.'/storage/releases/20260902/golf-release-20260902.zip') !== true
    || $previous->open($root.'/storage/releases/20260903/golf-update-20260903.zip') !== true) throw new RuntimeException('Missing deployment baseline.');
$sources = ['resources/js/app.ts', 'resources/js/pages.ts', 'resources/js/pages-login.ts',
    'resources/js/voxDialogs.ts', 'resources/js/useVoxMessages.ts', 'resources/js/setupVoxDialogs.ts',
    'resources/views/auth/login.blade.php'];
foreach (['VoxDialogHost', 'GolfCourseModule', 'GolfGamesModule', 'GolfCourseContracts', 'AgenciesModule',
    'AgencyExtras', 'AgeTablesModule', 'CourseGamesModule', 'ExchangeCurrencyModule', 'HotelBoardTypes',
    'HotelModule', 'ParityModule', 'SetupRecordsModule', 'UsersModule'] as $component) $sources[] = 'resources/js/components/'.$component.'.vue';
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Cannot create package.');
$files = [];
$add = function (string $source, string $target, bool $public = false) use ($zip, &$files): void {
    if (!is_file($source) || is_link($source)) throw new RuntimeException('Missing or linked source.');
    $zip->addFile($source, $target);
    $zip->setExternalAttributesName($target, ZipArchive::OPSYS_UNIX, ($public ? 0100644 : 0100600) << 16);
    $files[$target] = hash_file('sha256', $source);
};
foreach ($sources as $source) {
    $target = 'golf_release_20260902/'.$source;
    $old = $previous->getFromName($target);
    if ($old === false) $old = $original->getFromName($target);
    if ($old === false || hash('sha256', $old) !== hash_file('sha256', $root.'/'.$source)) $add($root.'/'.$source, $target);
}
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$assets = [];
foreach ($manifest as $entry) { $assets[] = $entry['file']; foreach ($entry['css'] ?? [] as $css) $assets[] = $css; }
foreach (array_unique($assets) as $asset) {
    if (!preg_match('~^assets/[A-Za-z0-9_.-]+\.(js|css)$~', $asset)) throw new RuntimeException('Unexpected asset path.');
    $add($root.'/public/build/'.$asset, 'public_html/golf/build/'.$asset, true);
}
// Publish the manifest last; leave previous hashed assets in place for open sessions.
$add($root.'/public/build/manifest.json', 'public_html/golf/build/manifest.json', true);
$zip->close(); $original->close(); $previous->close();
file_put_contents($out.'/manifest.json', json_encode(['archive'=>basename($path), 'sha256'=>hash_file('sha256', $path), 'files'=>$files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo json_encode(['path'=>$path,'bytes'=>filesize($path),'files'=>array_keys($files)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
