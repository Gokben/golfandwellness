<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cases = [
    [[], true],
    [['price'=>'0','currency'=>'GBP'], true],
    [['price'=>'12.50','currency'=>'EUR'], true],
    [['price'=>'-1','currency'=>'GBP'], false],
    [['price'=>'1.001','currency'=>'GBP'], false],
    [['price'=>'12'], false],
    [['currency'=>'GBP'], false],
    [['price'=>'12','currency'=>'BAD'], false],
];
foreach ($cases as [$fields, $valid]) {
    try {
        App\Support\LinkedRecords::validate('catalog-directions', [['name'=>'Test','code'=>'TEST'] + $fields]);
        $accepted = true;
    } catch (Illuminate\Validation\ValidationException $e) { $accepted = false; }
    if ($accepted !== $valid) throw new RuntimeException('Unexpected validation: '.json_encode($fields));
}
echo "8 direction pricing checks passed.\n";
