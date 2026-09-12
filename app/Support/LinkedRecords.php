<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LinkedRecords
{
    private const CATALOGS = ['board-types', 'hotel-types', 'regions', 'room-types', 'catalogs', 'nationalities', 'markets', 'cancel-reasons', 'vehicle-types', 'guides', 'directions'];
    public static function supports(string $kind): bool
    {
        if (in_array($kind, ['proposals-golf', 'proposals-hotel', 'proposals-hotel-golf'], true)) return true;
        return in_array($kind, ['hotels', 'agencies', 'agency-vouchers', 'hotel-golf-package-definitions', 'hotel-stop-sales', 'vehicles', 'hotel-reservations', 'golf-reservations', 'course-details'], true)
            || (str_starts_with($kind, 'catalog-') && in_array(substr($kind, 8), self::CATALOGS, true));
    }
    public static function validate(string $kind, array $records): array
    {
        if (str_starts_with($kind, 'proposals-')) return Proposals::validate(substr($kind, 10), $records);
        if ($kind === 'hotel-stop-sales') return HotelStopSales::validate($records);
        if ($kind === 'hotel-golf-package-definitions') return HotelGolfPackageDefinitions::validate($records);
        if (str_starts_with($kind, 'catalog-')) {
            $check = function (array &$rows, int $depth = 0) use (&$check) {
                if ($depth > 2 || !array_is_list($rows)) throw ValidationException::withMessages(['records' => 'Geçersiz tanım listesi.']);
                foreach ($rows as &$row) {
                    Validator::make($row, ['name' => 'present|nullable|string|max:500', 'code' => 'required|string|max:150', 'hotel' => 'sometimes|nullable|string|max:200', 'hotels' => 'sometimes|array|max:5000', 'hotels.*' => 'required|string|max:200|distinct', 'children' => 'sometimes|array|max:1000'])->validate();
                    $row['name'] = $row['name'] ?? '';
                    $row['id'] = $row['id'] ?? (string) \Illuminate\Support\Str::uuid();
                    if (isset($row['children'])) $check($row['children'], $depth + 1);
                }
            };
            $check($records);
            return $records;
        }
        $rules = match ($kind) {
            'hotels' => ['id' => 'required|integer|min:1', 'name' => 'required|string|max:200', 'code' => 'required|string|max:150'],
            'agencies' => ['name' => 'required|string|max:200', 'code' => 'required|string|max:150', 'extrasKey' => 'sometimes|required|string|max:150', 'contactEmail' => 'sometimes|nullable|email|max:200'],
            'agency-vouchers' => ['id'=>'required|string|max:150','agencyKey'=>'required|string|max:150','name'=>'required|string|max:200','shortCode'=>'required|string|max:150','count'=>['required','string','regex:/^\d{1,12}$/'],'lastCount'=>['required','string','regex:/^\d{1,12}$/']],
            'vehicles' => ['id' => 'required|string|max:150', 'type' => 'required|string|max:150', 'driver' => 'required|string|max:150', 'plate' => 'required|string|max:50'],
            'course-details' => ['id' => 'required|string|max:150', 'courseKey' => 'required|string|max:150', 'description' => 'present|nullable|string|max:50000', 'map' => 'present|nullable|string|max:10000', 'active' => 'required|boolean', 'mustNumber' => 'required|integer|min:0', 'nearby' => 'present|array', 'closings' => 'present|array', 'extras' => 'present|array'],
            default => ['id' => 'required|string|max:150', 'no' => 'required|string|max:150', 'agency' => 'required|string|max:150', 'state' => 'required|in:REQUEST,OPTION,CONFIRM,CANCEL', 'hotel' => 'required|string|max:150'],
        };
        if ($kind === 'hotel-reservations') $rules += ['checkIn' => 'required|date_format:Y-m-d', 'checkOut' => 'required|date_format:Y-m-d|after:checkIn', 'night' => 'required|integer|min:1', 'roomCount' => 'required|integer|min:1'];
        if ($kind === 'golf-reservations') $rules += ['course' => 'required|string|max:150', 'gameDate' => 'required|date_format:Y-m-d', 'time' => 'required|date_format:H:i', 'pax' => 'required|integer|min:1', 'freePax' => 'required|integer|min:0|lte:pax'];
        $identities = []; $codes = [];
        foreach ($records as &$row) {
            if (!is_array($row)) throw ValidationException::withMessages(['records' => 'Geçersiz kayıt.']);
            if ($kind === 'hotel-reservations') Validator::make($row, [
                'persons'=>'sometimes|array|max:500', 'persons.*.id'=>'required|string|max:150|distinct',
                'persons.*.title'=>'required|in:MR,MRS,MS,CHD,INF', 'persons.*.name'=>'required|string|max:200',
                'persons.*.age'=>'nullable|integer|min:0|max:99', 'persons.*.birthDate'=>'nullable|date_format:Y-m-d',
                'persons.*.roomType'=>'nullable|string|max:200', 'persons.*.transfer'=>'nullable|string|max:500',
                'citizen'=>'sometimes|nullable|string|max:150', 'optionDate'=>'sometimes|nullable|date_format:Y-m-d',
                'accommodation'=>'sometimes|nullable|string|max:200', 'pax'=>'sometimes|nullable|integer|min:1|max:999',
                'children'=>'sometimes|nullable|integer|min:0|max:999', 'infants'=>'sometimes|nullable|integer|min:0|max:999',
                'clientName'=>'sometimes|nullable|string|max:200',
                'handling'=>'sometimes|nullable|string|max:200', 'transfer'=>'sometimes|nullable|string|max:200',
                'transferNote'=>'sometimes|nullable|string|max:1000', 'extra'=>'sometimes|nullable|string|max:200',
                'arrivalFlight'=>'sometimes|nullable|string|max:100', 'departureFlight'=>'sometimes|nullable|string|max:100',
                'arrivalDestination'=>'sometimes|nullable|string|max:200', 'departureDestination'=>'sometimes|nullable|string|max:200',
                'arrivalTime'=>'sometimes|nullable|date_format:H:i', 'departureTime'=>'sometimes|nullable|date_format:H:i',
                'arrivalLocalTime'=>'sometimes|nullable|date_format:H:i', 'departureLocalTime'=>'sometimes|nullable|date_format:H:i',
                'arrivalFrom'=>'sometimes|nullable|string|max:200', 'departureFrom'=>'sometimes|nullable|string|max:200',
            ])->validate();
            if ($kind === 'hotels') unset($row['fax']);
            Validator::make($row, $rules)->validate();
            if ($kind === 'agency-vouchers' && (int) $row['lastCount'] < (int) $row['count']) throw ValidationException::withMessages(['records'=>'Son sayaç başlangıç sayacından küçük olamaz.']);
            if ($kind === 'hotels' && isset($row['details'])) {
                Validator::make($row, ['details' => 'array'])->validate();
                HotelDetails::validate($row['details']);
            }
            $key = $row['id'] ?? $row['extrasKey'] ?? $row['code'];
            if (isset($identities[$key])) throw ValidationException::withMessages(['records' => 'Kayıt kimliği tekrar ediyor.']);
            $identities[$key] = true;
            if (isset($row['code'])) {
                $code = mb_strtoupper(trim($row['code']));
                if (isset($codes[$code])) throw ValidationException::withMessages(['records' => 'Bu kod zaten kullanılıyor.']);
                $codes[$code] = true;
            }
            // Preserve blank optional form strings after Laravel's null conversion.
            foreach ($row as &$value) if ($value === null) $value = '';
            unset($value);
        }
        if (in_array($kind, ['hotel-reservations', 'golf-reservations'], true)) self::validateReservationLinks($kind, $records);
        return $records;
    }

    private static function validateReservationLinks(string $kind, array $records): void
    {
        $db = \Illuminate\Support\Facades\DB::connection('setup_mysql');
        $sets = $db->table('setup_record_sets')->whereIn('kind', ['hotels', 'agencies', 'golf-courses', 'golf-tee-times', 'golf-contracts', $kind])->get()->keyBy('kind');
        $read = fn ($key) => isset($sets[$key]) ? json_decode($sets[$key]->records, true) : [];
        $find = function ($rows, $value, $fields) { foreach ($rows as $row) foreach ($fields as $field) if (isset($row[$field]) && (string) $row[$field] === (string) $value) return $row; return null; };
        foreach ($records as $row) {
            $old = $find($read($kind), $row['id'], ['id']);
            foreach (['hotel' => ['hotels', ['id', 'name', 'code']], 'agency' => ['agencies', ['extrasKey', 'code', 'name']]] as $field => [$set, $keys]) {
                if (!$find($read($set), $row[$field], $keys) && (!$old || $old[$field] !== $row[$field])) throw ValidationException::withMessages([$field => 'Seçilen kayıt artık tanımlı değil. Listeyi yenileyin.']);
            }
            if ($kind !== 'golf-reservations') continue;
            $course = $find($read('golf-courses'), $row['course'], ['contractKey', 'code', 'name']);
            if (!$course && (!$old || $old['course'] !== $row['course'])) throw ValidationException::withMessages(['course' => 'Golf sahası bulunamadı.']);
            if (($row['state'] ?? '') === 'CANCEL') continue;
            if (($course['details']['active'] ?? true) === false) throw ValidationException::withMessages(['course' => 'Golf sahası pasif durumda.']);
            foreach ($course['details']['closings'] ?? [] as $closing) {
                if (!empty($closing['closedFrom']) && !empty($closing['closedTo']) && $closing['closedFrom'] <= $row['gameDate'] && $closing['closedTo'] >= $row['gameDate']) throw ValidationException::withMessages(['gameDate' => 'Golf sahası seçilen tarihte kapalı.']);
            }
            $courseKey = $course['contractKey'] ?? $course['code'] ?? $row['course'];
            if (!empty($row['contract'])) {
                $contract = $find($read('golf-contracts'), $row['contract'], ['id']);
                if (!$contract || $contract['courseKey'] !== $courseKey || $contract['status'] !== 'ACTIVE' || $contract['firstDate'] > $row['gameDate'] || $contract['lastDate'] < $row['gameDate']) throw ValidationException::withMessages(['contract' => 'Kontrat bu saha veya oyun tarihi için geçerli değil.']);
            }
            if (empty($row['teeTimeId'])) continue;
            $tee = $find($read('golf-tee-times'), $row['teeTimeId'], ['id']);
            if (!$tee || (($tee['courseKey'] ?? '') !== $courseKey && $tee['course'] !== ($course['name'] ?? '')) || $tee['date'] !== $row['gameDate'] || $tee['time'] !== $row['time']) throw ValidationException::withMessages(['teeTimeId' => 'TeeTimes seçimi saha, tarih ve saat ile uyuşmuyor.']);
            $sold = array_sum(array_map(fn ($other) => ($other['state'] === 'CONFIRM' && ($other['teeTimeId'] ?? '') === $tee['id']) ? (int) $other['pax'] : 0, $records));
            if ($sold > $tee['pax']) throw ValidationException::withMessages(['pax' => 'Seçilen TeeTime için yeterli kontenjan bulunmuyor.']);
        }
    }

    public static function syncReferences($db, string $kind, array $before, array $after): array
    {
        $changes = [];
        $flatten = function (array $rows) use (&$flatten) { $all = $rows; foreach ($rows as $row) if (!empty($row['children'])) $all = array_merge($all, $flatten($row['children'])); return $all; };
        $before = $flatten($before); $after = $flatten($after);
        foreach ($before as $index => $old) {
            $identity = $old['id'] ?? $old['contractKey'] ?? $old['extrasKey'] ?? $old['code'] ?? null;
            if ($identity === null) continue;
            foreach ($after as $new) {
                if (($new['id'] ?? $new['contractKey'] ?? $new['extrasKey'] ?? $new['code'] ?? null) === $identity) { $changes[] = [$old, $new, $identity]; break; }
            }
        }
        if (!$changes) return [];
        $related = [];
        foreach ($db->table('setup_record_sets')->where('kind', '!=', $kind)->lockForUpdate()->get() as $set) {
            $rows = json_decode($set->records, true);
            $original = $rows;
            foreach ($changes as [$old, $new, $identity]) {
                foreach ($rows as &$row) {
                    if ($kind === 'golf-courses' && $set->kind === 'golf-tee-times' && (($row['courseKey'] ?? '') === $identity || (empty($row['courseKey']) && ($row['course'] ?? '') === $old['name']))) {
                        $row['courseKey'] = $identity; $row['course'] = $new['name'];
                    }
                    if ($kind === 'hotels' && $set->kind === 'catalog-room-types') {
                        foreach ($row['children'] ?? [] as $i => $child) if (($child['hotel'] ?? '') === $old['name']) $row['children'][$i]['hotel'] = $new['name'];
                        foreach ($row['children'] ?? [] as $i => $child) if (isset($child['hotels'])) $row['children'][$i]['hotels'] = array_values(array_unique(array_map(fn ($name) => $name === $old['name'] ? $new['name'] : $name, $child['hotels'])));
                    }
                    $hotelFields = ['catalog-hotel-types' => ['type', 'code'], 'catalog-room-types' => ['roomType', 'code'], 'catalog-regions' => ['location1', 'name'], 'catalog-catalogs' => ['catalog', 'code']];
                    if ($set->kind === 'hotels' && isset($hotelFields[$kind])) {
                        [$field, $source] = $hotelFields[$kind];
                        $fields = $kind === 'catalog-regions' ? ['location1', 'location2'] : [$field];
                        foreach ($fields as $field) $row[$field] = implode(', ', array_map(fn ($value) => trim($value) === $old[$source] ? $new[$source] : trim($value), explode(',', $row[$field] ?? '')));
                    }
                    if ($set->kind === 'parity' && $kind === 'catalog-room-types' && ($row['roomType'] ?? '') === $old['code']) $row['roomType'] = $new['code'];
                    if ($set->kind === 'agencies' && in_array($kind, ['citizens', 'markets'])) {
                        $field = $kind === 'citizens' ? 'citizen' : 'market';
                        $row[$field] = implode(',', array_map(fn ($value) => trim($value) === $old['fields'][1] ? $new['fields'][1] : trim($value), explode(',', $row[$field] ?? '')));
                        if ($kind === 'markets' && ($row['subMarket'] ?? '') === $old['fields'][0]) $row['subMarket'] = $new['fields'][0];
                    }
                    if ($set->kind === 'golf-contracts' && $kind === 'golf-games' && ($row['courseKey'] ?? '') === $old['courseKey'] && ($row['name'] ?? '') === $old['name']) $row['name'] = $new['name'];
                    if ($set->kind === 'agency-extras' && $kind === 'age-tables' && ($row['ageTable'] ?? '') === $old['code']) $row['ageTable'] = $new['code'];
                    if ($set->kind === 'agency-extras' && $kind === 'extra-sellings' && ($row['description'] ?? '') === $old['fields'][0]) $row['description'] = $new['fields'][0];
                    if ($set->kind === 'golf-courses' && $kind === 'hotel-golf-extras') foreach ($row['details']['extras'] ?? [] as $i => $extra) if ($extra['description'] === $old['fields'][0]) $row['details']['extras'][$i]['description'] = $new['fields'][0];
                }
                unset($row);
            }
            if ($rows === $original) continue;
            $db->table('setup_record_backups')->insert(['kind' => $set->kind, 'version' => $set->version, 'records' => $set->records, 'created_at' => now()]);
            $db->table('setup_record_sets')->where('kind', $set->kind)->update(['records' => json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'version' => $set->version + 1, 'updated_at' => now()]);
            $related[] = $set->kind;
        }
        return $related;
    }
}
