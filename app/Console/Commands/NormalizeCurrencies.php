<?php

namespace App\Console\Commands;

use App\Support\Currencies;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeCurrencies extends Command
{
    protected $signature = 'golf:normalize-currencies';
    protected $description = 'Back up and normalize EU/TRY currency fields to EUR/TL without changing amounts.';
    public function handle(): int
    {
        $db = DB::connection('setup_mysql');
        $count = $db->transaction(function () use ($db) {
            $count = 0;
            foreach ($db->table('setup_record_sets')->lockForUpdate()->get() as $set) {
                $records = json_decode($set->records, true, 512, JSON_THROW_ON_ERROR);
                $normalized = Currencies::normalize($records);
                if ($normalized === $records) continue;
                $db->table('setup_record_backups')->insert(['kind' => $set->kind, 'version' => $set->version, 'records' => $set->records, 'created_at' => now()]);
                $db->table('setup_record_sets')->where('kind', $set->kind)->update(['records' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'version' => $set->version + 1, 'updated_at' => now()]);
                $count++;
            }
            return $count;
        });
        $this->info("Normalized and backed up {$count} record sets.");
        return self::SUCCESS;
    }
}
