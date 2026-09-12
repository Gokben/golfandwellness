<?php

namespace App\Http\Controllers;

use App\Support\GolfAccess;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetupRecordsController extends Controller
{
    private const KINDS = ['citizens', 'markets', 'cancel-reasons', 'extra-sellings', 'hotel-golf-extras', 'age-tables', 'golf-contracts', 'golf-games', 'golf-courses', 'golf-tee-times', 'agency-extras', 'parity'];

    private function guard(Request $request, string $kind): void
    {
        abort_unless(in_array($kind, self::KINDS, true) || \App\Support\LinkedRecords::supports($kind), 404);
        GolfAccess::authorizeApi($request);
        if($kind==='golf-reservations')\App\Support\GolfReservationPrices::ensure();
    }

    private function payload($row): array
    {
        return $row ? ['initialized' => true, 'records' => \App\Support\Currencies::normalize($row->kind==='golf-reservations'?\App\Support\GolfReservationPrices::attach(json_decode($row->records,true)):json_decode($row->records,true)), 'version' => $row->version, 'importHash' => $row->import_hash]
            : ['initialized' => false, 'records' => [], 'version' => 0, 'importHash' => null];
    }

    public function show(Request $request, string $kind)
    {
        $this->guard($request, $kind);
        return response()->json($this->payload(DB::connection('setup_mysql')->table('setup_record_sets')->where('kind', $kind)->first()))->header('Cache-Control', 'no-store');
    }

    private function records(Request $request, string $kind): array
    {
        $data = $request->validate(['records' => 'present|array|max:5000']);
        $records = \App\Support\Currencies::normalize($data['records']);
        \App\Support\DateRules::validate($records);
        if (!array_is_list($records)) throw ValidationException::withMessages(['records' => 'Kayıtlar bir liste olmalıdır.']);
        if (\App\Support\LinkedRecords::supports($kind)) return \App\Support\LinkedRecords::validate($kind, $records);
        if ($kind === 'hotel-golf-extras') {
            $expected = [['HOTEL EXTRAS', 'HOTEL'], ['GOLF EXTRAS', 'GOLF']];
            if (count($records) !== 2) throw ValidationException::withMessages(['records' => 'Otel ve golf ekstra grupları korunmalıdır.']);
            foreach ($expected as $index => $fields) {
                if (($records[$index]['id'] ?? null) !== 'hotel-golf-extras-'.$index || ($records[$index]['fields'] ?? null) !== $fields) {
                    throw ValidationException::withMessages(['records' => 'Otel ve golf ekstra grupları değiştirilemez.']);
                }
            }
        }
        if ($kind === 'golf-courses') {
            Validator::make(['records' => $records], [
                'records.*' => 'required|array:name,hotels,code,contractKey,hotelIds,details',
                'records.*.name' => 'required|string|max:150',
                'records.*.hotels' => 'present|nullable|string|max:1000',
                'records.*.code' => 'required|string|max:150',
                'records.*.contractKey' => 'sometimes|required|string|max:150',
                'records.*.hotelIds' => 'sometimes|array',
                'records.*.hotelIds.*' => 'string|max:150',
                'records.*.details' => 'sometimes|array:description,map,active,mustNumber,nearby,closings,extras',
                'records.*.details.description' => 'sometimes|nullable|string|max:50000',
                'records.*.details.map' => 'sometimes|nullable|string|max:10000',
                'records.*.details.active' => 'sometimes|boolean',
                'records.*.details.mustNumber' => 'sometimes|integer|min:0',
                'records.*.details.nearby' => 'sometimes|array',
                'records.*.details.nearby.*' => 'string|max:150',
                'records.*.details.closings' => 'sometimes|array|max:1000',
                'records.*.details.closings.*' => 'array:closedFrom,closedTo,buggyFrom,buggyTo,description',
                'records.*.details.closings.*.closedFrom' => 'nullable|date_format:Y-m-d',
                'records.*.details.closings.*.closedTo' => 'nullable|date_format:Y-m-d',
                'records.*.details.closings.*.buggyFrom' => 'nullable|date_format:Y-m-d',
                'records.*.details.closings.*.buggyTo' => 'nullable|date_format:Y-m-d',
                'records.*.details.closings.*.description' => 'nullable|string|max:1000',
                'records.*.details.extras' => 'sometimes|array|max:1000',
                'records.*.details.extras.*' => 'array:firstDate,lastDate,description,buyPrice,sellPrice,currency,type,obligation',
                'records.*.details.extras.*.firstDate' => 'required|date_format:Y-m-d',
                'records.*.details.extras.*.lastDate' => 'required|date_format:Y-m-d',
                'records.*.details.extras.*.description' => 'required|string|max:150',
                'records.*.details.extras.*.buyPrice' => 'required|numeric|min:0',
                'records.*.details.extras.*.sellPrice' => 'required|numeric|min:0',
                'records.*.details.extras.*.currency' => 'required|in:USD,GBP,TL,EUR',
                'records.*.details.extras.*.type' => 'required|in:PP,PA,PROOM,FIX',
                'records.*.details.extras.*.obligation' => 'required|boolean',
            ])->validate();
            $codes = []; $keys = [];
            foreach ($records as &$row) {
                // Laravel converts an empty hotel selection to null.
                $row['hotels'] = $row['hotels'] ?? '';
                foreach ($row['details']['closings'] ?? [] as $closing) {
                    foreach ([['closedFrom', 'closedTo'], ['buggyFrom', 'buggyTo']] as [$from, $to]) {
                        if (empty($closing[$from]) !== empty($closing[$to]) || (!empty($closing[$from]) && $closing[$from] > $closing[$to])) throw ValidationException::withMessages(['records' => 'Kapanış ve buggy tarih aralıkları geçerli olmalıdır.']);
                    }
                }
                foreach ($row['details']['extras'] ?? [] as $extra) if ($extra['firstDate'] > $extra['lastDate']) throw ValidationException::withMessages(['records' => 'Ekstra bitiş tarihi başlangıçtan önce olamaz.']);
                $code = mb_strtoupper(trim($row['code']));
                $key = $row['contractKey'] ?? $row['code'];
                if (isset($codes[$code]) || isset($keys[$key])) throw ValidationException::withMessages(['records' => 'Golf sahası kodu veya kayıt kimliği zaten kullanılıyor.']);
                $codes[$code] = true; $keys[$key] = true;
            }
            unset($row);
        } elseif ($kind === 'golf-tee-times') {
            Validator::make(['records' => $records], [
                'records.*' => 'required|array:id,course,courseKey,hidden,date,time,pax,price,currency,special,sales,optionDate',
                'records.*.id' => 'required|string|max:150|distinct:strict',
                'records.*.course' => 'required|string|max:150',
                'records.*.courseKey' => 'sometimes|nullable|string|max:150',
                'records.*.hidden' => 'sometimes|boolean',
                'records.*.date' => 'required|date_format:Y-m-d',
                'records.*.time' => 'required|date_format:H:i',
                'records.*.pax' => 'required|integer|min:1|max:10000',
                'records.*.price' => ['required', 'string', 'regex:/^\d{1,9}(?:\.\d{1,4})?$/'],
                'records.*.currency' => 'required|in:USD,GBP,TL,EUR',
                'records.*.special' => 'required|boolean',
                'records.*.sales' => 'required|integer|min:0',
                'records.*.optionDate' => 'required|date_format:Y-m-d',
            ])->validate();
        } elseif ($kind === 'agency-extras') {
            $rules = [
                'records.*' => 'required|array:id,agencyKey,firstDate,lastDate,description,buyPrice,sellPrice,currency,priceType,obligation,ageTable,buyInfant,sellInfant,buyChild,sellChild',
                'records.*.id' => 'required|string|max:150|distinct:strict',
                'records.*.agencyKey' => 'required|string|max:150',
                'records.*.firstDate' => 'required|date_format:Y-m-d',
                'records.*.lastDate' => 'required|date_format:Y-m-d',
                'records.*.description' => 'required|string|max:150',
                'records.*.currency' => 'required|in:USD,GBP,TL,EUR',
                'records.*.priceType' => 'required|in:PP,PROOM,FIX',
                'records.*.obligation' => 'required|boolean',
                'records.*.ageTable' => 'required|string|max:150',
            ];
            foreach (['buyPrice', 'sellPrice', 'buyInfant', 'sellInfant', 'buyChild', 'sellChild'] as $field) $rules['records.*.'.$field] = ['required', 'string', 'regex:/^\d{1,9}(?:\.\d{1,4})?$/'];
            Validator::make(['records' => $records], $rules)->validate();
            foreach ($records as $row) {
                if (!is_bool($row['obligation'])) throw ValidationException::withMessages(['records' => 'Zorunlu alanı doğru/yanlış olmalıdır.']);
                if ($row['firstDate'] > $row['lastDate']) throw ValidationException::withMessages(['records' => 'Ekstra bitiş tarihi başlangıçtan önce olamaz.']);
            }
        } elseif ($kind === 'golf-games') {
            Validator::make(['records' => $records], [
                'records.*' => 'required|array:id,courseKey,name,code,round',
                'records.*.id' => 'required|string|max:150|distinct:strict',
                'records.*.courseKey' => 'required|string|max:150',
                'records.*.name' => 'required|string|max:150',
                'records.*.code' => 'required|string|max:150',
                'records.*.round' => 'required|integer|min:1|max:1000',
            ])->validate();
            $codes = [];
            foreach ($records as $row) {
                if (!is_int($row['round'])) throw ValidationException::withMessages(['records' => 'Round tam sayı olmalıdır.']);
                $key = json_encode([$row['courseKey'], mb_strtoupper(trim($row['code']))], JSON_THROW_ON_ERROR);
                if (isset($codes[$key])) throw ValidationException::withMessages(['records' => 'Bu saha için oyun kodu zaten kayıtlı.']);
                $codes[$key] = true;
            }
        } elseif ($kind === 'golf-contracts') {
            $rules = [
                'records.*' => 'required|array:id,courseKey,game,name,firstDate,lastDate,rrOhg,toOhg,toHg,rrHg,currency,contractType,seasonType,status,groupId',
                'records.*.groupId' => 'sometimes|required|string|max:150',
                'records.*.id' => 'required|string|max:150|distinct:strict',
                'records.*.courseKey' => 'required|string|max:150',
                'records.*.game' => 'required|string|max:150',
                'records.*.name' => 'required|string|max:150',
                'records.*.firstDate' => 'required|date_format:Y-m-d',
                'records.*.lastDate' => 'required|date_format:Y-m-d',
                'records.*.currency' => 'required|in:USD,GBP,TL,EUR',
                'records.*.contractType' => 'required|in:BUY,SELL',
                'records.*.seasonType' => 'required|in:MAIN,ACTION',
                'records.*.status' => 'required|in:ACTIVE,PASSIVE',
            ];
            foreach (['rrOhg', 'toOhg', 'toHg', 'rrHg'] as $field) $rules['records.*.'.$field] = ['required', 'string', 'regex:/^\d{1,9}(?:\.\d{1,4})?$/'];
            Validator::make(['records' => $records], $rules)->validate();
            foreach ($records as $row) {
                if ($row['firstDate'] > $row['lastDate']) throw ValidationException::withMessages(['records' => 'Kontrat bitiş tarihi başlangıçtan önce olamaz.']);
            }
        } elseif ($kind === 'age-tables') {
            Validator::make(['records' => $records], [
                'records.*' => 'required|array:id,code,infantFrom,infantTo,childFrom,childTo',
                'records.*.id' => 'required|string|max:100|distinct:strict',
                'records.*.code' => 'required|string|max:30',
                'records.*.infantFrom' => 'required|integer|min:0|max:120',
                'records.*.infantTo' => 'required|integer|min:0|max:120',
                'records.*.childFrom' => 'required|integer|min:0|max:120',
                'records.*.childTo' => 'required|integer|min:0|max:120',
            ])->validate();
            $codes = [];
            foreach ($records as $row) {
                $code = mb_strtoupper(trim($row['code']));
                if (isset($codes[$code])) throw ValidationException::withMessages(['records' => 'Bu yaş tablosu kodu zaten kayıtlı.']);
                $codes[$code] = true;
                foreach (['infantFrom', 'infantTo', 'childFrom', 'childTo'] as $field) {
                    if (!is_int($row[$field])) throw ValidationException::withMessages(['records' => 'Yaşlar tam sayı olmalıdır.']);
                }
                if ($row['infantFrom'] > $row['infantTo'] || $row['infantTo'] >= $row['childFrom'] || $row['childFrom'] > $row['childTo']) {
                    throw ValidationException::withMessages(['records' => 'Yaş aralıkları sıralı olmalı; bebek ve çocuk aralıkları çakışmamalıdır.']);
                }
            }
        } elseif ($kind === 'parity') {
            Validator::make(['records' => $records], [
                'records.*' => 'array:pax,inf,chd,roomType,parity',
                'records.*.pax' => 'required|integer|min:1|max:10000',
                'records.*.inf' => 'required|integer|min:0|max:10000',
                'records.*.chd' => 'required|integer|min:0|max:10000',
                'records.*.roomType' => 'required|string|max:80',
                'records.*.parity' => ['required', 'string', 'numeric', function ($attribute, $value, $fail) { if ((float) $value < 0 || (float) $value > 1000000) $fail('Geçersiz Parity değeri.'); }],
            ])->validate();
        } else {
            $ids = [];
            $validateRows = function (array $rows, int $depth = 0) use (&$validateRows, &$ids, $kind): void {
                if (!array_is_list($rows)) throw ValidationException::withMessages(['records' => 'Alt kalemler bir liste olmalıdır.']);
                $codes = [];
                foreach ($rows as $row) {
                    Validator::make(['row' => $row], [
                        'row' => 'required|array:id,fields,children',
                        'row.id' => 'required|string|max:100',
                        'row.fields' => 'required|array|size:2',
                        'row.fields.*' => 'required|string|max:150',
                        'row.children' => 'present|array|max:5000',
                    ])->validate();
                    if (!array_is_list($row['fields'])) throw ValidationException::withMessages(['records' => 'Geçersiz alan sırası.']);
                    $code = mb_strtoupper(trim($row['fields'][$kind === 'cancel-reasons' ? 0 : 1]));
                    if (isset($ids[$row['id']]) || isset($codes[$code])) {
                        throw ValidationException::withMessages(['records' => 'Tekrarlanan kayıt kimliği veya kodu.']);
                    }
                    $ids[$row['id']] = true;
                    $codes[$code] = true;
                    if ($row['children'] && (!in_array($kind, ['markets', 'extra-sellings', 'hotel-golf-extras'], true) || $depth > 0)) {
                        throw ValidationException::withMessages(['records' => 'Geçersiz alt kalem seviyesi.']);
                    }
                    $validateRows($row['children'], $depth + 1);
                }
            };
            $validateRows($records);
        }
        return $records;
    }

    public function initialize(Request $request, string $kind)
    {
        $this->guard($request, $kind);
        $records = $this->records($request, $kind);
        $data = $request->validate(['importHash' => 'nullable|regex:/^[a-f0-9]{64}$/']);
        $db = DB::connection('setup_mysql');
        return $db->transaction(function () use ($db, $kind, $records, $data) {
            // A second browser must never overwrite an already initialized set.
            $db->table('setup_record_sets')->insertOrIgnore([
                'kind' => $kind, 'records' => json_encode($records, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'version' => 1, 'import_hash' => $data['importHash'] ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = $db->table('setup_record_sets')->where('kind', $kind)->first();
            return response()->json($this->payload($row));
        });
    }

    public function update(Request $request, string $kind)
    {
        $this->guard($request, $kind);
        $records = $this->records($request, $kind);
        $version = $request->validate(['version' => 'required|integer|min:1'])['version'];
        $db = DB::connection('setup_mysql');
        return $db->transaction(function () use ($db, $kind, $records, $version) {
            $row = $db->table('setup_record_sets')->where('kind', $kind)->lockForUpdate()->first();
            if (!$row || (int) $row->version !== (int) $version) return response()->json(['message' => 'Kayıtlar başka bir pencerede değişti. Listeyi yeniden yükleyin.'], 409);
            $voucherChanged=false;
            if(in_array($kind,['hotel-reservations','golf-reservations'],true)){
                $before=json_decode($row->records,true);
                $newIds=array_diff(array_column($records,'id'),array_column($before,'id'));
                if($newIds){
                    $voucherSet=$db->table('setup_record_sets')->where('kind','agency-vouchers')->lockForUpdate()->first();
                    $vouchers=$voucherSet?json_decode($voucherSet->records,true):[];
                    $records=\App\Support\ReservationVouchers::assign($before,$records,$vouchers);
                    $db->table('setup_record_sets')->where('kind','agency-vouchers')->update(['records'=>json_encode($vouchers,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'version'=>$voucherSet->version+1,'updated_at'=>now()]);
                    $voucherChanged=true;
                }else{
                    $unused=[];$records=\App\Support\ReservationVouchers::assign($before,$records,$unused);
                }
            }
            if($kind==='golf-reservations')$records=\App\Support\GolfReservationPrices::persist($db,json_decode($row->records,true),$records);
            $db->table('setup_record_backups')->insert(['kind' => $kind, 'version' => $row->version, 'records' => $row->records, 'created_at' => now()]);
            $db->table('setup_record_sets')->where('kind', $kind)->update([
                'records' => json_encode($records, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'version' => $version + 1, 'updated_at' => now(),
            ]);
            $related = \App\Support\LinkedRecords::syncReferences($db, $kind, json_decode($row->records, true), $records);
            if($voucherChanged)$related[]='agency-vouchers';
            return response()->json($this->payload($db->table('setup_record_sets')->where('kind', $kind)->first()) + ['relatedKinds' => array_values(array_unique($related))]);
        });
    }
}
