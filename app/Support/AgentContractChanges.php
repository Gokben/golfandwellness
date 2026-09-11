<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class AgentContractChanges
{
    public const FIELDS = ['allotment', 'guarantee', 'price', 'firstDate', 'lastDate', 'validityFirstDate', 'validityLastDate'];
    private const LABELS = ['allotment'=>'Kontenjan', 'guarantee'=>'Garanti oda', 'price'=>'Kişi başı fiyat', 'firstDate'=>'İlk tarih', 'lastDate'=>'Son tarih', 'validityFirstDate'=>'Geçerlilik başlangıcı', 'validityLastDate'=>'Geçerlilik bitişi'];

    public static function schema(): array
    {
        return ['type'=>'object', 'additionalProperties'=>false, 'required'=>['explanation','changes'], 'properties'=>[
            'explanation'=>['type'=>'string'],
            'changes'=>['type'=>'array', 'items'=>['type'=>'object', 'additionalProperties'=>false, 'required'=>['rowId','field','value'], 'properties'=>[
                'rowId'=>['type'=>'string'], 'field'=>['type'=>'string','enum'=>self::FIELDS], 'value'=>['type'=>'string'],
            ]]],
        ]];
    }

    public static function locate(array $records, string $hotelId, string $contractId): array
    {
        foreach ($records as $hi => $hotel) {
            if ((string) $hotel['id'] !== $hotelId) continue;
            foreach ($hotel['details']['contracts'] ?? [] as $ci => $contract) if ($contract['id'] === $contractId) return [$hi, $ci];
        }
        abort(404, 'Seçili kontrat bulunamadı.');
    }

    public static function patch(array $records, string $hotelId, string $contractId, array $changes, bool $validate = true, bool $allowUnchanged = false): array
    {
        Validator::make(['changes'=>$changes], [
            'changes'=>'required|array|min:1|max:20', 'changes.*'=>'array:rowId,field,value',
            'changes.*.rowId'=>'present|nullable|string|max:150', 'changes.*.field'=>'required|in:'.implode(',',self::FIELDS),
            'changes.*.value'=>'required|string|max:100',
        ])->validate();
        [$hi, $ci] = self::locate($records, $hotelId, $contractId);
        $before = $records[$hi]['details']['contracts'][$ci];
        $after = $before;
        $seen = [];
        foreach ($changes as $change) {
            $field = $change['field']; $rowId = $change['rowId'] ?? ''; $value = $change['value'];
            $key = $rowId.':'.$field;
            abort_if(isset($seen[$key]), 422, 'Aynı alana birden fazla değişiklik önerildi.');
            $seen[$key] = true;
            if (in_array($field, ['allotment','guarantee'])) {
                abort_unless(preg_match('/^\d{1,6}$/D', $value), 422, 'Oda sayısı geçersiz.');
                $value = (string) (int) $value;
            } elseif ($field === 'price') {
                abort_unless(preg_match('/^\d{1,9}(\.\d{1,2})?$/D', $value), 422, 'Fiyat geçersiz.');
                $value = number_format((float) $value, 2, '.', '');
            } else {
                Validator::make(['date'=>$value], ['date'=>'required|date_format:Y-m-d'])->validate();
            }
            if ($rowId !== '') {
                abort_unless($field === 'price', 422, 'Konaklama satırında yalnızca fiyat değişebilir.');
                $found = false;
                foreach ($after['prices'] as &$price) if ($price['id'] === $rowId) {
                    $price['price'] = $value; $price['manualPrice'] = true; $found = true;
                }
                unset($price);
                abort_unless($found, 422, 'Fiyat satırı seçili kontrata ait değil.');
            } else {
                // A missing base may represent a unit-priced source: do not reinterpret it as per-person.
                abort_if($field === 'price' && ($before['price'] ?? '') === '', 422, 'Kişi başı fiyat temeli eksik. Ünite fiyatını konaklama satırından değiştirin.');
                $after[$field] = $value;
            }
        }
        if ($before['price'] !== $after['price']) foreach ($after['prices'] as &$price) {
            if (!empty($price['manualPrice'])) continue;
            abort_unless(is_numeric($price['parity']) && (float) $price['parity'] >= 0, 422, 'Otomatik fiyat satırının paritesi eksik.');
            $amount = (float) $after['price'] * (float) $price['parity'];
            abort_unless(is_finite($amount) && $amount <= 999999999, 422, 'Hesaplanan fiyat sınırı aşıyor.');
            $price['price'] = number_format($amount, 2, '.', '');
        }
        unset($price);
        $diff = [];
        foreach (self::FIELDS as $field) if (($before[$field] ?? '') !== ($after[$field] ?? '')) $diff[] = ['label'=>self::LABELS[$field], 'before'=>(string) ($before[$field] ?? ''), 'after'=>(string) ($after[$field] ?? ''), 'currency'=>$field==='price' ? $after['currency'] : ''];
        foreach ($after['prices'] as $i => $price) {
            $old = $before['prices'][$i];
            if ($old['price'] !== $price['price']) $diff[] = ['label'=>'Konaklama: '.$price['accommodation'], 'before'=>(string) $old['price'], 'after'=>(string) $price['price'], 'currency'=>$price['currency']];
            if (empty($old['manualPrice']) && !empty($price['manualPrice'])) $diff[] = ['label'=>'Fiyat yöntemi: '.$price['accommodation'], 'before'=>'Otomatik', 'after'=>'Elle belirlenen fiyat', 'currency'=>''];
        }
        abort_if(!$diff && !$allowUnchanged, 422, 'Kaydedilecek değişiklik bulunamadı.');
        $records[$hi]['details']['contracts'][$ci] = $after;
        if ($validate) HotelDetails::validate($records[$hi]['details']);
        return [$records, $diff];
    }

    public static function batchPatch(array $records, string $hotelId, array $contractIds, array $changes): array
    {
        Validator::make(['ids'=>$contractIds], ['ids'=>'required|array|min:1|max:100','ids.*'=>'required|string|max:150|distinct'])->validate();
        foreach ($changes as $change) abort_if(!empty($change['rowId']),422,'Toplu işlemde yalnızca ortak kontrat alanları değişebilir.');
        $diff=[]; $currencies=[];
        foreach ($contractIds as $id) {
            [$hi,$ci]=self::locate($records,$hotelId,$id);
            $name=$records[$hi]['details']['contracts'][$ci]['name'];
            $currencies[]=$records[$hi]['details']['contracts'][$ci]['currency'];
            [$records,$rows]=self::patch($records,$hotelId,$id,$changes,false,true);
            foreach ($rows as $row) $diff[]=array_merge($row,['contractId'=>$id,'contractName'=>$name]);
        }
        abort_if(in_array('price',array_column($changes,'field'),true) && count(array_unique($currencies))>1,422,'Toplu fiyat değişikliğinde para birimleri aynı olmalı.');
        abort_if(!$diff,422,'Kaydedilecek değişiklik bulunamadı.');
        HotelDetails::validate($records[$hi]['details']);
        return [$records,$diff];
    }

    public static function batchProposal(array $records, int $version, string $owner, string $hotelId, array $contractIds, array $changes): array
    {
        [, $diff]=self::batchPatch($records,$hotelId,$contractIds,$changes);
        [$hi]=self::locate($records,$hotelId,$contractIds[0]);
        $payload=['id'=>(string) Str::uuid(),'owner'=>$owner,'hotelId'=>$hotelId,'contractIds'=>$contractIds,'version'=>$version,'changes'=>$changes,'expires'=>time()+900];
        return ['token'=>Crypt::encryptString(json_encode($payload,JSON_THROW_ON_ERROR)),'hotelName'=>$records[$hi]['name'],'contractName'=>count($contractIds).' kontrat','changes'=>$diff];
    }

    public static function proposal(array $records, int $version, string $owner, string $hotelId, string $contractId, array $changes): array
    {
        [, $diff] = self::patch($records, $hotelId, $contractId, $changes);
        [$hi, $ci] = self::locate($records, $hotelId, $contractId);
        $payload = ['id'=>(string) Str::uuid(), 'owner'=>$owner, 'hotelId'=>$hotelId, 'contractId'=>$contractId, 'version'=>$version, 'changes'=>$changes, 'expires'=>time()+900];
        return ['token'=>Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)), 'hotelName'=>$records[$hi]['name'], 'contractName'=>$records[$hi]['details']['contracts'][$ci]['name'], 'changes'=>$diff];
    }

    public static function approvedPayload(string $token, string $owner): array
    {
        try { $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { abort(422, 'Öneri doğrulanamadı. Yeniden oluşturun.'); }
        abort_unless(($payload['owner'] ?? null) === $owner, 403, 'Bu öneri başka bir kullanıcıya ait.');
        abort_if(($payload['expires'] ?? 0) < time(), 409, 'Önerinin süresi doldu. Yeniden oluşturun.');
        return $payload;
    }

    public static function approve(string $token, string $owner): array
    {
        $p = self::approvedPayload($token, $owner);
        $db = DB::connection('setup_mysql');
        return $db->transaction(function () use ($db, $p, $owner) {
            $set = $db->table('setup_record_sets')->where('kind','hotels')->lockForUpdate()->first();
            abort_unless($set && (int) $set->version === $p['version'], 409, 'Kayıtlar değişti veya öneri zaten uygulandı. Yeniden öneri oluşturun.');
            $original=json_decode($set->records,true,512,JSON_THROW_ON_ERROR);
            [$records, $diff] = isset($p['contractIds'])
                ? self::batchPatch($original,$p['hotelId'],$p['contractIds'],$p['changes'])
                : self::patch($original, $p['hotelId'], $p['contractId'], $p['changes']);
            $db->table('setup_record_backups')->insert(['kind'=>'hotels','version'=>$set->version,'records'=>$set->records,'created_at'=>now()]);
            $db->table('setup_record_backups')->insert(['kind'=>'agent-contract-audit','version'=>$set->version,'records'=>json_encode(['actor'=>$owner,'proposal'=>$p['id'],'hotelId'=>$p['hotelId'],'contractIds'=>$p['contractIds'] ?? [$p['contractId']],'changes'=>$diff],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'created_at'=>now()]);
            $db->table('setup_record_sets')->where('kind','hotels')->update(['records'=>json_encode($records,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'version'=>$set->version+1,'updated_at'=>now()]);
            return ['saved'=>true, 'message'=>'Kontrat güncellendi. Diğer açık ekranlar eski veriyi gösterebilir.', 'version'=>$set->version+1];
        });
    }
}
