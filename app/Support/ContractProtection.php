<?php
namespace App\Support;

use Illuminate\Validation\ValidationException;

class ContractProtection
{
    public static function needsCheck(string $kind): bool
    {
        return in_array($kind, ['hotels', 'agencies'], true) || str_ends_with($kind, '-reservations') || str_starts_with($kind, 'proposals-');
    }
    public static function ids(string $kind, array $records): array
    {
        $ids = [];
        foreach ($records as $row) {
            if ($kind === 'hotels') {
                foreach ($row['details']['contracts'] ?? [] as $contract) $ids[] = $contract['id'];
            } else {
                foreach ($row['hotelContracts'] ?? [] as $copy) {
                    $ids[] = $copy['id'];
                    foreach ($copy['contracts'] as $contract) $ids[] = $contract['id'];
                }
            }
        }
        return $ids;
    }

    public static function references(array $value): array
    {
        $ids = [];
        foreach ($value as $key => $item) {
            if (is_array($item)) $ids = array_merge($ids, self::references($item));
            elseif (in_array($key, ['hotelContract', 'agencyContract', 'contractId', 'sourceContractId'], true) && is_string($item) && $item !== '') $ids[] = $item;
        }
        return $ids;
    }

    public static function enforce($db, string $kind, array $records, $sets): void
    {
        if (!in_array($kind, ['hotels', 'agencies'], true)) {
            // These locks also serialize reservation creation against contract deletion.
            if (str_ends_with($kind, '-reservations') || str_starts_with($kind, 'proposals-')) {
                $available = [];
                foreach (['hotels', 'agencies'] as $source) {
                    $available = array_merge($available, self::ids($source, isset($sets[$source]) ? json_decode($sets[$source]->records, true) : []));
                }
                $check = function (array $data) use (&$check, $available) {
                    foreach ($data as $key => $value) {
                        if (is_array($value)) $check($value);
                        elseif (in_array($key, ['hotelContract', 'agencyContract'], true) && $value && !in_array($value, $available, true)) {
                            throw ValidationException::withMessages(['records'=>'Seçilen otel veya acente kontratı artık mevcut değil. Listeyi yenileyin.']);
                        }
                    }
                };
                $check($records);
            }
            return;
        }
        $before = isset($sets[$kind]) ? json_decode($sets[$kind]->records, true) : [];
        $removed = array_diff(self::ids($kind, $before), self::ids($kind, $records));
        if (!$removed) return;
        foreach ($sets as $set) {
            if ($set->kind === $kind) continue;
            if (array_intersect($removed, self::references(json_decode($set->records, true)))) self::used();
        }
        foreach ($db->table('setup_record_backups')->where(function ($query) {
            $query->where('kind', 'like', '%-reservations')->orWhere('kind', 'like', 'proposals-%');
        })->orderBy('id')->cursor() as $backup) {
            if (array_intersect($removed, self::references(json_decode($backup->records, true)))) self::used();
        }
    }

    private static function used(): void
    {
        throw ValidationException::withMessages(['records'=>'Bu kontrat bir rezervasyonda, teklifte veya kayıt geçmişinde kullanılmış. Silinemez.']);
    }
}
