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
        if ($kind === 'hotel-reservations') self::validateAgencySelection($records, $sets);
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

    private static function validateAgencySelection(array $records, $sets): void
    {
        $read = fn ($kind) => isset($sets[$kind]) ? json_decode($sets[$kind]->records, true) : [];
        $find = function ($rows, $value, $keys) {
            foreach ($rows as $row) foreach ($keys as $key) if (isset($row[$key]) && (string)$row[$key] === (string)$value) return $row;
            return null;
        };
        $previous = array_column($read('hotel-reservations'), null, 'id');
        foreach ($records as $record) {
            if (empty($record['agencyContract'])) continue;
            $agency = $find($read('agencies'), $record['agency'] ?? '', ['extrasKey','code','name']);
            $hotel = $find($read('hotels'), $record['hotel'] ?? '', ['id','code','name']);
            $contract = null;
            foreach ($agency['hotelContracts'] ?? [] as $copy) {
                if (($copy['hotelName'] ?? '') !== ($hotel['name'] ?? null)) continue;
                $contract = $find($copy['contracts'], $record['agencyContract'], ['id']);
                if ($contract) break;
            }
            $valid = $contract && $contract['status'] === 'ACTIVE' && $contract['firstDate'] <= ($record['checkIn'] ?? '') && $contract['lastDate'] >= ($record['checkIn'] ?? '');
            $groups = $read('catalog-room-types');
            $main = $find($groups, $record['mainRoom'] ?? '', ['id','code','name']);
            $rooms = $main['children'] ?? array_merge([], ...array_map(fn ($group) => $group['children'] ?? [], $groups));
            $room = $find($rooms, $record['roomType'] ?? '', ['id','code','name']);
            foreach (['mainRoom'=>[$main,'roomType'], 'roomType'=>[$room,'roomName']] as $field => [$choice,$target]) {
                if (!empty($record[$field])) $valid = $valid && $choice && in_array($contract[$target] ?? '', [$choice['id'] ?? '',$choice['code'] ?? '',$choice['name'] ?? ''], true);
            }
            $old = $previous[$record['id'] ?? ''] ?? [];
            $retained = ($old['agencyContract'] ?? '') === $record['agencyContract'] && ($old['agency'] ?? '') === ($record['agency'] ?? '') && ($old['hotel'] ?? '') === ($record['hotel'] ?? '');
            if ($valid && !$retained && (!empty($contract['bookingFirstDate']) || !empty($contract['bookingLastDate']))) {
                $today = now('Europe/Istanbul')->format('Y-m-d');
                $valid = !empty($contract['bookingFirstDate']) && !empty($contract['bookingLastDate']) && $contract['bookingFirstDate'] <= $today && $contract['bookingLastDate'] >= $today;
            }
            if (!$valid) throw ValidationException::withMessages(['agencyContract'=>'Acente kontratı seçili acente, otel, giriş tarihi, oda veya rezervasyon geçerlilik aralığı ile eşleşmiyor. Yeniden seçin.']);
        }
    }
}
