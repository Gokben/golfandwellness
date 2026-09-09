<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSetupExchangeRates extends Command
{
    protected $signature = 'setup:import-exchange-rates';
    protected $description = 'Copy local SQLite exchange rates to setup MySQL without modifying the source';

    public function handle(): int
    {
        if (!app()->environment('local')) { $this->error('Bu aktarım yalnızca yerel ortam içindir.'); return self::FAILURE; }
        $target = DB::connection('setup_mysql');
        $rates = DB::connection('sqlite')->table('exchange_rates')->get();
        $target->transaction(function () use ($target, $rates) {
            foreach ($rates as $rate) {
                $data = (array) $rate;
                unset($data['id']);
                $existing = $target->table('exchange_rates')->where('rate_date', $rate->rate_date)->where('currency_code', $rate->currency_code)->first();
                if ($existing) {
                    foreach (['currency_name', 'forex_buying', 'forex_selling', 'banknote_buying', 'banknote_selling'] as $field) {
                        if ($existing->$field != $rate->$field) throw new \RuntimeException('Farklı MySQL kaydı bulundu; aktarım geri alındı.');
                    }
                } else {
                    $target->table('exchange_rates')->insert($data);
                }
            }
        });
        $this->info($rates->count().' döviz kaydı MySQL ile doğrulandı; SQLite kaynağı korunuyor.');
        return self::SUCCESS;
    }
}
