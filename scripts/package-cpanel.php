<?php

// Local packaging only. Never upload this script as an HTTP endpoint.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
if (!class_exists(ZipArchive::class)) throw new RuntimeException('Run with the PHP zip extension enabled.');
require dirname(__DIR__).'/vendor/autoload.php';
$base = dirname(__DIR__);
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('local')) throw new RuntimeException('Packaging must run against the local app.');
$admin = App\Models\SetupUser::where('username', 'admin2')->firstOrFail();
if (!App\Support\GolfAccess::eligible($admin)) throw new RuntimeException('The approved administrator must set a password first.');
if (!is_file($base.'/public/build/manifest.json')) throw new RuntimeException('Run npm run build first.');

$release = 'golf_release_20260902';
$public = 'golf_public_20260902';
$out = $base.'/storage/releases/20260902';
if (is_dir($out) && array_diff(scandir($out), ['.', '..', 'golf-setup-import.sql'])) throw new RuntimeException('Release exists. Do not replace a previously reviewed archive.');
if (!is_dir($out)) mkdir($out, 0700, true);

// New tables only; source/live table lists were checked before preparing this release.
$db = Illuminate\Support\Facades\DB::connection('setup_mysql');
$sql = "-- Import only into krpsoftc_golf. Never replace existing tables.\nUSE `krpsoftc_golf`;\nSET NAMES utf8mb4;\n";
$counts = [];
$literal = static fn ($value) => $value === null ? 'NULL' : ((string) $value === '' ? "''" : 'CONVERT(0x'.bin2hex((string) $value).' USING utf8mb4)');
$db->beginTransaction();
try {
    foreach (['setup_record_sets', 'setup_record_backups', 'setup_users', 'exchange_rates'] as $table) {
        $schema = (array) $db->selectOne('SHOW CREATE TABLE `'.$table.'`');
        $sql .= $schema['Create Table'].";\n";
        $rows = $db->table($table)->get();
        $counts[$table] = $rows->count();
        foreach ($rows as $row) {
            $values = (array) $row;
            $sql .= 'INSERT INTO `'.$table.'` (`'.implode('`,`', array_keys($values)).'`) VALUES ('.implode(',', array_map($literal, array_values($values))).");\n";
        }
    }
    $db->commit();
} catch (Throwable $e) { $db->rollBack(); throw $e; }
// Live TCMB history takes precedence over overlapping local dates; original stays intact.
$sql .= <<<'SQL'
INSERT INTO exchange_rates (rate_date,currency_code,currency_name,forex_buying,forex_selling,banknote_buying,banknote_selling,created_at,updated_at)
SELECT rate_date,currency_code,currency_name,forex_buying,forex_selling,banknote_buying,banknote_selling,created_at,updated_at FROM golf_exchange_rates
ON DUPLICATE KEY UPDATE currency_name=VALUES(currency_name),forex_buying=VALUES(forex_buying),forex_selling=VALUES(forex_selling),banknote_buying=VALUES(banknote_buying),banknote_selling=VALUES(banknote_selling),updated_at=VALUES(updated_at);
SQL;
file_put_contents($out.'/golf-setup-import.sql', $sql);

$zip = new ZipArchive;
if ($zip->open($out.'/golf-release-20260902.zip', ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Cannot create archive.');
$addDirectory = static function (string $name, bool $private = true) use ($zip) {
    $zip->addEmptyDir($name);
    $zip->setExternalAttributesName($name.'/', ZipArchive::OPSYS_UNIX, ($private ? 040700 : 040755) << 16);
};
$addDirectory($release);
$addDirectory($public, false);
$addTree = static function (string $source, string $target, bool $private = true) use ($zip, $addDirectory) {
    $addDirectory($target, $private);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
        if ($file->isLink()) throw new RuntimeException('Symlinks are not allowed in the release.');
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
        $name = $target.'/'.$relative;
        if ($file->isDir()) $addDirectory($name, $private);
        else {
            $zip->addFile($file->getPathname(), $name);
            $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, ($private ? 0100600 : 0100644) << 16);
        }
    }
};
foreach (['app','config','routes','resources','vendor'] as $dir) $addTree($base.'/'.$dir, $release.'/'.$dir);
$addDirectory($release.'/bootstrap');
foreach (['app.php','providers.php'] as $name) $zip->addFile($base.'/bootstrap/'.$name, $release.'/bootstrap/'.$name);
$addDirectory($release.'/bootstrap/cache');
foreach (['storage','storage/app','storage/app/private','storage/framework','storage/framework/views','storage/framework/sessions','storage/framework/cache','storage/framework/cache/data','storage/logs'] as $dir) $addDirectory($release.'/'.$dir);
foreach (['artisan','composer.json','composer.lock'] as $name) $zip->addFile($base.'/'.$name, $release.'/'.$name);
$addTree($base.'/public/build', $public.'/build', false);
$addTree($base.'/public/images', $public.'/images', false);
foreach (['favicon.png','favicon.ico'] as $name) $zip->addFile($base.'/public/'.$name, $public.'/'.$name);
$key = 'base64:'.base64_encode(random_bytes(32));
$env = "APP_NAME=golfandwellness\nAPP_ENV=production\nAPP_DEBUG=false\nAPP_KEY=$key\nAPP_URL=https://krpsoft.com.tr/golf\nAPP_LOCALE=tr\nAPP_FALLBACK_LOCALE=en\nDB_CONNECTION=setup_mysql\nSETUP_DB_CONFIG=/home/krpsoftc/golf-db.php\nSESSION_DRIVER=file\nSESSION_COOKIE=golf_live_session\nSESSION_PATH=/golf\nSESSION_SECURE_COOKIE=true\nSESSION_HTTP_ONLY=true\nSESSION_SAME_SITE=lax\nSESSION_LIFETIME=120\nCACHE_STORE=file\nQUEUE_CONNECTION=sync\nLOG_CHANNEL=single\nLOG_LEVEL=error\nMAIL_MAILER=log\n";
$zip->addFromString($release.'/.env', $env);
$zip->setExternalAttributesName($release.'/.env', ZipArchive::OPSYS_UNIX, 0100600 << 16);
$zip->addFromString($public.'/index.php', <<<'PHP'
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$privateApp = dirname(__DIR__, 2).'/golf_release_20260902';
require $privateApp.'/vendor/autoload.php';
$app = require $privateApp.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
PHP);
$zip->addFromString($public.'/.htaccess', <<<'APACHE'
DirectoryIndex index.php
Options -MultiViews -Indexes
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} !=on
    RewriteRule ^ https://krpsoft.com.tr%{REQUEST_URI} [R=301,L]
    RewriteRule (^|/)\. - [F,L]
    RewriteRule \.(zip|sql|log|bak|env)$ - [F,L,NC]
    RewriteRule ^exchange-rates\.php$ - [F,L,NC]
    RewriteRule ^(desktop|login|index)\.html$ index.php [L]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "same-origin"
</IfModule>
APACHE);
$zip->close();
file_put_contents($out.'/manifest.json', json_encode(['release'=>$release,'public'=>$public,'counts'=>$counts,'archiveSha256'=>hash_file('sha256',$out.'/golf-release-20260902.zip'),'sqlSha256'=>hash_file('sha256',$out.'/golf-setup-import.sql')], JSON_PRETTY_PRINT));
echo json_encode(['directory'=>$out,'counts'=>$counts,'archiveBytes'=>filesize($out.'/golf-release-20260902.zip')], JSON_PRETTY_PRINT).PHP_EOL;
