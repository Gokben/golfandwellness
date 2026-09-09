<?php
// Local CLI only. Explicit user-requested updates without creating history copies.
if (PHP_SAPI !== 'cli' || !in_array('--apply-without-backup', $argv, true)) {
    throw new RuntimeException('Explicit --apply-without-backup flag required.');
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$changes = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$allowed = ['golf-contracts', 'golf-courses', 'golf-tee-times', 'hotels'];
$db = Illuminate\Support\Facades\DB::connection('setup_mysql');
$validator = new ReflectionMethod(App\Http\Controllers\SetupRecordsController::class, 'records');
$controller = app(App\Http\Controllers\SetupRecordsController::class);
$result = $db->transaction(function () use ($changes, $allowed, $db, $validator, $controller) {
    $result = [];
    foreach ($changes as $change) {
        $kind = $change['kind'];
        if (!in_array($kind, $allowed, true)) throw new RuntimeException('Unsupported record set');
        $current = $db->table('setup_record_sets')->where('kind', $kind)->lockForUpdate()->first();
        if (!$current || $current->version !== $change['version']) throw new RuntimeException('Records changed; prepare again.');
        $request = Illuminate\Http\Request::create('/', 'POST', ['records' => $change['records']]);
        $records = $validator->invoke($controller, $request, $kind);
        $db->table('setup_record_sets')->where('kind', $kind)->update([
            'records' => json_encode($records, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'version' => $current->version + 1, 'updated_at' => now(),
        ]);
        $result[] = ['kind' => $kind, 'count' => count($records), 'version' => $current->version + 1];
    }
    return $result;
});
echo json_encode(['updated' => $result, 'backupCreated' => false], JSON_THROW_ON_ERROR).PHP_EOL;
