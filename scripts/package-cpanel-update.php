<?php
// Code-only update for the existing cPanel installation. Run locally, never via HTTP.
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !class_exists(ZipArchive::class)) exit(1);
$root = dirname(__DIR__);
$out = $root.'/storage/releases/20260903';
if (!is_dir($out)) mkdir($out, 0700, true);
$path = $out.'/golf-update-20260903.zip';
if (file_exists($path)) throw new RuntimeException('Reviewed update already exists.');
$baseline = new ZipArchive();
if ($baseline->open($root.'/storage/releases/20260902/golf-release-20260902.zip') !== true) throw new RuntimeException('Missing original release.');
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Cannot create update.');
$files = [];
$add = function (string $source, string $target, bool $public = false) use ($zip, &$files): void {
    $zip->addFile($source, $target);
    $zip->setExternalAttributesName($target, ZipArchive::OPSYS_UNIX, ($public ? 0100644 : 0100600) << 16);
    $files[$target] = hash_file('sha256', $source);
};
// Only changed PHP controller and front-end sources; no config, env, auth, vendor or DB dump.
$relative = 'app/Http/Controllers/SetupRecordsController.php';
$add($root.'/'.$relative, 'golf_release_20260902/'.$relative);
foreach (['resources/js', 'resources/css'] as $dir) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (!$file->isFile() || $file->isLink()) continue;
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $target = 'golf_release_20260902/'.$relative;
        $old = $baseline->getFromName($target);
        if ($old === false || hash('sha256', $old) !== hash_file('sha256', $file->getPathname())) $add($file->getPathname(), $target);
    }
}
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$assets = ['manifest.json'];
foreach ($manifest as $entry) {
    $assets[] = $entry['file'];
    foreach ($entry['css'] ?? [] as $css) $assets[] = $css;
}
// Assets precede manifest in the archive. Old hashed assets stay in place for open sessions.
$assets = array_values(array_unique($assets));
usort($assets, fn ($a, $b) => ($a === 'manifest.json' ? 1 : 0) <=> ($b === 'manifest.json' ? 1 : 0));
foreach ($assets as $asset) {
    if (str_contains($asset, '..') || !is_file($root.'/public/build/'.$asset)) throw new RuntimeException('Invalid built asset.');
    $add($root.'/public/build/'.$asset, 'public_html/golf/build/'.$asset, true);
}
$zip->close(); $baseline->close();
file_put_contents($out.'/manifest.json', json_encode(['archive'=>basename($path), 'sha256'=>hash_file('sha256', $path), 'files'=>$files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo json_encode(['path'=>$path,'bytes'=>filesize($path),'files'=>array_keys($files)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
