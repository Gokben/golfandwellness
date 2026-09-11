<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SetupRecordsController;
use App\Http\Controllers\SetupUsersController;
use App\Http\Controllers\ContractDocumentController;
use App\Http\Controllers\AppReleaseController;
use App\Http\Controllers\GolfAgentController;

Route::get('/app-release', AppReleaseController::class);

Route::get('/agent', [\App\Http\Controllers\GolfAgentController::class, 'index']);
Route::get('/agent/hotels', [\App\Http\Controllers\GolfAgentController::class, 'hotels']);
Route::post('/agent/chat', [\App\Http\Controllers\GolfAgentController::class, 'chat'])->middleware('throttle:10,1');
Route::post('/agent/knowledge', [\App\Http\Controllers\GolfAgentController::class, 'save'])->middleware('throttle:20,1');
Route::post('/agent/knowledge/{id}', [\App\Http\Controllers\GolfAgentController::class, 'moderate'])->whereUuid('id');
Route::post('/agent/example', [\App\Http\Controllers\GolfAgentController::class, 'example'])->middleware('throttle:6,1');

Route::get('/contract-document/status', [ContractDocumentController::class, 'status']);
Route::post('/contract-document', [ContractDocumentController::class, 'store'])->middleware('throttle:6,1');
Route::post('/contract-document/review', [ContractDocumentController::class, 'review'])->middleware('throttle:6,1');
Route::get('/agent/contracts', [GolfAgentController::class, 'contracts']);
Route::post('/agent/contracts/propose', [GolfAgentController::class, 'proposeContract'])->middleware('throttle:6,1');
Route::post('/agent/contracts/approve', [GolfAgentController::class, 'approveContract'])->middleware('throttle:6,1');

Route::get('/setup-users', [SetupUsersController::class, 'index']);
Route::post('/setup-users', [SetupUsersController::class, 'store']);
Route::put('/setup-users/{id}', [SetupUsersController::class, 'update'])->whereNumber('id');
Route::delete('/setup-users/{id}', [SetupUsersController::class, 'destroy'])->whereNumber('id');

Route::get('/setup-records/{kind}', [SetupRecordsController::class, 'show']);
Route::post('/setup-records/{kind}/initialize', [SetupRecordsController::class, 'initialize']);
Route::put('/setup-records/{kind}', [SetupRecordsController::class, 'update']);

Route::get('/exchange-rates', function (Request $request) {
    $date = $request->query('date', now()->toDateString());
    return response()->json([
        'date' => $date,
        'rates' => DB::connection('setup_mysql')->table('exchange_rates')
            ->whereDate('rate_date', $date)
            ->orderByRaw("case currency_code when 'GBP' then 1 when 'EUR' then 2 when 'USD' then 3 else 4 end")
            ->get()
            ->map(fn ($rate) => [
                'code' => $rate->currency_code,
                'name' => $rate->currency_name,
                'forexBuying' => $rate->forex_buying,
                'forexSelling' => $rate->forex_selling,
                'banknoteBuying' => $rate->banknote_buying,
                'banknoteSelling' => $rate->banknote_selling,
            ]),
    ]);
});

Route::post('/exchange-rates/refresh', function () {
    $response = Http::timeout(12)->get('https://www.tcmb.gov.tr/kurlar/today.xml');
    if (!$response->successful()) {
        return response()->json(['message' => 'TCMB kurları alınamadı.'], 502);
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response->body());
    if ($xml === false) {
        return response()->json(['message' => 'TCMB XML verisi okunamadı.'], 502);
    }

    $xmlDate = str_replace('.', '/', trim((string) $xml['Tarih']));
    $rateDate = Carbon::createFromFormat('d/m/Y', $xmlDate)->toDateString();
    $wanted = ['USD', 'EUR', 'GBP'];
    $rates = [];
    foreach ($xml->Currency as $currency) {
        $code = (string) $currency['CurrencyCode'];
        if (!in_array($code, $wanted, true)) continue;
        $row = [
            'rate_date' => $rateDate,
            'currency_code' => $code,
            'currency_name' => (string) $currency->Isim,
            'forex_buying' => (string) $currency->ForexBuying ?: null,
            'forex_selling' => (string) $currency->ForexSelling ?: null,
            'banknote_buying' => (string) $currency->BanknoteBuying ?: null,
            'banknote_selling' => (string) $currency->BanknoteSelling ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::connection('setup_mysql')->table('exchange_rates')->upsert([$row], ['rate_date', 'currency_code'], ['currency_name', 'forex_buying', 'forex_selling', 'banknote_buying', 'banknote_selling', 'updated_at']);
        $rates[] = [
            'code' => $code, 'name' => $row['currency_name'], 'forexBuying' => $row['forex_buying'], 'forexSelling' => $row['forex_selling'], 'banknoteBuying' => $row['banknote_buying'], 'banknoteSelling' => $row['banknote_selling'],
        ];
    }
    return response()->json(['date' => $rateDate, 'rates' => $rates]);
});
