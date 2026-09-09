<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/** @return never */
function fail(string $message, int $status = 500): never
{
    http_response_code($status);
    echo json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/** @return array{date: string, rates: array<int, array<string, string>>} */
function storedRates(PDO $pdo, string $date): array
{
    $query = $pdo->prepare(
        'SELECT currency_code, currency_name, forex_buying, forex_selling, banknote_buying, banknote_selling
         FROM golf_exchange_rates WHERE rate_date = :date
         ORDER BY FIELD(currency_code, "GBP", "EUR", "USD")'
    );
    $query->execute(['date' => $date]);
    $rates = array_map(static fn (array $row): array => [
        'code' => $row['currency_code'], 'name' => $row['currency_name'],
        'forexBuying' => $row['forex_buying'], 'forexSelling' => $row['forex_selling'],
        'banknoteBuying' => $row['banknote_buying'], 'banknoteSelling' => $row['banknote_selling'],
    ], $query->fetchAll(PDO::FETCH_ASSOC));
    return ['date' => $date, 'rates' => $rates];
}

$configPath = dirname(__DIR__, 2) . '/golf-db.php';
if (!is_file($configPath) || !is_array($config = require $configPath)) {
    fail('Veritabanı yapılandırması bulunamadı.');
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['database']),
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec('CREATE TABLE IF NOT EXISTS golf_exchange_rates (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, rate_date DATE NOT NULL, currency_code VARCHAR(8) NOT NULL,
        currency_name VARCHAR(100) NOT NULL, forex_buying DECIMAL(18,6) NULL, forex_selling DECIMAL(18,6) NULL,
        banknote_buying DECIMAL(18,6) NULL, banknote_selling DECIMAL(18,6) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY golf_exchange_rates_date_code (rate_date, currency_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
} catch (Throwable $error) {
    error_log('Golf exchange rate database error: ' . $error->getMessage());
    fail('Veritabanı bağlantısı kurulamadı.');
}

$requestedDate = $_GET['date'] ?? '';
if (is_string($requestedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
    echo json_encode(storedRates($pdo, $requestedDate), JSON_UNESCAPED_UNICODE);
    exit;
}

$context = stream_context_create(['https' => ['timeout' => 20, 'header' => "User-Agent: VOX-Golf/1.0\r\n"]]);
libxml_use_internal_errors(true);
$xmlContent = @file_get_contents('https://www.tcmb.gov.tr/kurlar/today.xml', false, $context);
if ($xmlContent === false && function_exists('curl_init')) {
    $curl = curl_init('https://www.tcmb.gov.tr/kurlar/today.xml');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'VOX-Golf/1.0',
    ]);
    $xmlContent = curl_exec($curl);
    curl_close($curl);
}
$xml = $xmlContent === false ? false : simplexml_load_string($xmlContent);
if ($xml === false) { fail('TCMB kurları alınamadı.', 502); }

$xmlDate = (string) $xml['Tarih'];
$date = DateTimeImmutable::createFromFormat('d.m.Y', $xmlDate)?->format('Y-m-d');
if ($date === null) { fail('TCMB kur tarihi okunamadı.', 502); }

$save = $pdo->prepare('INSERT INTO golf_exchange_rates
 (rate_date, currency_code, currency_name, forex_buying, forex_selling, banknote_buying, banknote_selling)
 VALUES (:date, :code, :name, :forexBuying, :forexSelling, :banknoteBuying, :banknoteSelling)
 ON DUPLICATE KEY UPDATE currency_name=VALUES(currency_name), forex_buying=VALUES(forex_buying),
 forex_selling=VALUES(forex_selling), banknote_buying=VALUES(banknote_buying), banknote_selling=VALUES(banknote_selling)');
$rates = [];
foreach ($xml->Currency as $currency) {
    $code = (string) $currency['CurrencyCode'];
    if (!in_array($code, ['USD', 'EUR', 'GBP'], true)) { continue; }
    $rate = ['code' => $code, 'name' => (string) $currency->Isim,
        'forexBuying' => (string) $currency->ForexBuying, 'forexSelling' => (string) $currency->ForexSelling,
        'banknoteBuying' => (string) $currency->BanknoteBuying, 'banknoteSelling' => (string) $currency->BanknoteSelling];
    $save->execute(['date' => $date] + $rate);
    $rates[] = $rate;
}
echo json_encode(['date' => $date, 'rates' => $rates], JSON_UNESCAPED_UNICODE);
