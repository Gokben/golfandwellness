<?php

// Standalone checks for installations without development Composer packages.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });

use App\Support\ContractDocumentReader;
use App\Http\Controllers\ContractDocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

function check(bool $value, string $label): void {
    if (!$value) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
function archiveBytes(array $files): string {
    $path = tempnam(sys_get_temp_dir(), 'golf-test-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) $zip->addFromString($name, $content);
    $zip->close();
    $bytes = file_get_contents($path); unlink($path);
    return $bytes;
}
function emptySchema(array $schema): mixed {
    if ($schema['type'] === 'string') return '';
    if ($schema['type'] === 'array') return [];
    return array_map(fn ($property) => emptySchema($property), $schema['properties']);
}

$reader = new ContractDocumentReader;
$word = archiveBytes(['word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Example Resort 120 EUR</w:t></w:r></w:p></w:body></w:document>']);
check(str_contains($reader->read($word, 'docx'), '120 EUR'), 'Word text extraction');
$excel = archiveBytes([
    'xl/workbook.xml' => '<workbook xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Rates" r:id="rId1"/></sheets></workbook>',
    'xl/_rels/workbook.xml.rels' => '<Relationships><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>',
    'xl/sharedStrings.xml' => '<sst><si><t>Double room</t></si></sst>',
    'xl/worksheets/sheet1.xml' => '<worksheet><sheetData><row><c r="A1" t="s"><v>0</v></c><c r="C1"><v>120</v></c><c r="D1"><f>C1*2</f></c></row></sheetData><mergeCells><mergeCell ref="A1:B1"/></mergeCells></worksheet>',
]);
$text = $reader->read($excel, 'xlsx');
check(str_contains($text, 'Double room') && str_contains($text, 'C1') && str_contains($text, 'A1:B1') && str_contains($text, 'Formül sonucu yok'), 'Excel cells, shared strings, merges and uncached formulas');
foreach (['broken' => 'not a zip', 'entity' => archiveBytes(['word/document.xml' => '<!DOCTYPE x [<!ENTITY a SYSTEM "file:///test">]><x>&a;</x>']), 'empty' => archiveBytes(['word/document.xml' => '<document/>'])] as $label => $bytes) {
    try { $reader->read($bytes, 'docx'); check(false, $label.' rejected'); }
    catch (ValidationException) { check(true, $label.' rejected'); }
}

$controller = new ContractDocumentController;
$input = ['filename' => 'contract.docx', 'document' => base64_encode($word), 'hotelName' => 'Example Resort', 'roomTypes' => ['STD']];
$request = Request::create('/', 'POST', $input);
config(['services.openai.key' => null]);
check($controller->status()->getData(true)['configured'] === false, 'Missing key reported without disclosure');
try { $controller->store($request, $reader); check(false, 'Missing key rejected'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $error) { check($error->getStatusCode() === 503, 'Missing key rejected'); }
config(['services.openai.key' => 'test-only', 'services.openai.model' => 'gpt-4.1-mini']);
Http::preventStrayRequests();
$contract = emptySchema(ContractDocumentController::schema()['properties']['contracts']['items']);
$contract['name'] = 'Example contract';
$payload = ['contracts' => [$contract], 'warnings' => ['Source: paragraph 1; missing dates.']];
Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($payload)]]]]])]);
$result = $controller->store($request, $reader)->getData(true);
check($result['contracts'][0]['status'] === 'PENDING' && strlen($result['contracts'][0]['id']) === 36, 'Review draft receives server ID and pending status');
check(count($result['warnings']) === 2, 'Missing required fields produce validation warning');
check(Http::recorded(fn ($r) => $r['store'] === false && $r['text']['format']['strict'] === true && !isset($r['tools']))->count() === 1, 'Structured extraction uses no tools and disables response storage');
$pdf = "%PDF-1.4\n%%EOF";
$pdfInput = array_replace($input, ['filename' => 'contract.PDF', 'document' => base64_encode($pdf)]);
$pdfResult = $controller->store(Request::create('/', 'POST', $pdfInput), $reader)->getData(true);
check($pdfResult['contracts'][0]['status'] === 'PENDING', 'PDF remains a review-only draft');
check(Http::recorded(fn ($r) => is_array($r['input']) && $r['input'][0]['content'][1]['type'] === 'input_file' && $r['input'][0]['content'][1]['file_data'] === 'data:application/pdf;base64,'.base64_encode($pdf) && $r['store'] === false)->count() === 1, 'PDF bytes use inline file input without persistent upload');
foreach (['invalid header' => base64_encode('not a PDF'), 'invalid base64' => '!!!', 'oversize' => base64_encode('%PDF-'.str_repeat('x', 4 * 1024 * 1024))] as $label => $document) {
    try { $controller->store(Request::create('/', 'POST', array_replace($pdfInput, ['document' => $document])), $reader); check(false, $label.' PDF rejected'); }
    catch (ValidationException) { check(true, $label.' PDF rejected'); }
}
Http::swap(new Illuminate\Http\Client\Factory);
Http::preventStrayRequests();
Http::fake(['api.openai.com/*' => Http::response(['status' => 'incomplete'])]);
try { $controller->store($request, $reader); check(false, 'Incomplete result rejected'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $error) { check($error->getStatusCode() === 422, 'Incomplete result rejected'); }
Http::swap(new Illuminate\Http\Client\Factory);
Http::preventStrayRequests();
Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => '{"contracts":[{}],"warnings":[]}']]]]])]);
try { $controller->store($request, $reader); check(false, 'Malformed draft rejected'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $error) { check($error->getStatusCode() === 502, 'Malformed draft rejected'); }
