<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Proposals
{
    public static function validate(string $kind,array $records): array
    {
        $sets=DB::connection('setup_mysql')->table('setup_record_sets')->whereIn('kind',['hotels','agencies','golf-courses','golf-contracts','catalog-room-types','catalog-board-types','markets','citizens'])->get()->keyBy('kind');
        $read=fn($key)=>isset($sets[$key])?json_decode($sets[$key]->records,true):[];
        $find=function($rows,$value,$keys=['id','code']){foreach($rows as $r)foreach($keys as $key)if(isset($r[$key])&&(string)$r[$key]===(string)$value)return $r;return null;};
        $ids=[];
        foreach($records as &$record){
            if(!is_array($record))throw ValidationException::withMessages(['records'=>'Geçersiz teklif.']);
            Validator::make($record,[
                'id'=>'required|string|max:150','title'=>'required|string|max:200','createDate'=>'required|date_format:Y-m-d','optionDate'=>'nullable|date_format:Y-m-d','agency'=>'nullable|string|max:150','creator'=>'required|string|max:200','status'=>'required|in:OPTIONAL,CONFIRMED,CHANGED,CANCELLED','note'=>'nullable|string|max:5000','lines'=>'present|array|max:500',
                'sourceId'=>'sometimes|required|string|max:150','sourceTables'=>'sometimes|array|max:10','sourceTables.*'=>'array|max:500','sourceTables.*.*'=>'array|max:30','sourceTables.*.*.*'=>'nullable|string|max:5000',
            ])->validate();
            if(isset($ids[$record['id']]))throw ValidationException::withMessages(['records'=>'Teklif kimliği tekrar ediyor.']);$ids[$record['id']]=true;
            if(empty($record['lines'])&&empty($record['sourceId']))throw ValidationException::withMessages(['lines'=>'En az bir teklif satırı ekleyin.']);
            if(!empty($record['agency'])&&!$find($read('agencies'),$record['agency'],['extrasKey','code']))throw ValidationException::withMessages(['agency'=>'Acente bulunamadı.']);
            if(empty($record['agency'])&&empty($record['sourceId']))throw ValidationException::withMessages(['agency'=>'Acente seçin.']);
            $lineIds=[];
            foreach($record['lines'] as &$line){
                if(!is_array($line))throw ValidationException::withMessages(['lines'=>'Geçersiz satır.']);
                $rules=['id'=>'required|string|max:150','category'=>'required|in:golf,hotel,hotel-golf','firstDate'=>'required|date_format:Y-m-d','lastDate'=>'required|date_format:Y-m-d|after_or_equal:firstDate','teeTime'=>'nullable|date_format:H:i',
                    'pax'=>'required|integer|min:1|max:10000','freePax'=>'required|integer|min:0|lte:pax','rooms'=>'required|integer|min:1|max:10000','infants'=>'required|integer|min:0|max:10000','children'=>'required|integer|min:0|max:10000',
                    'rounds'=>'required|in:1,2,3,4,5,6,7,8,9,10,UNLIMITED','basis'=>'required|in:PERSON,PERSON_NIGHT,ROOM_NIGHT,FIXED','priceType'=>'required|in:TOHG,TO-OHG,RRHG,RR-OHG','agencyPriceType'=>'required|in:TOHG,TO-OHG,RRHG,RR-OHG',
                    'buyPrice'=>'nullable|numeric|min:0|max:999999999','sellPrice'=>'nullable|numeric|min:0|max:999999999','buyExtras'=>'required|numeric|min:0|max:999999999','sellExtras'=>'required|numeric|min:0|max:999999999','buyCurrency'=>'required|in:USD,GBP,TL,EUR','sellCurrency'=>'required|in:USD,GBP,TL,EUR',
                    'freeStatus'=>'present|array|max:3','freeStatus.*'=>'in:HANDLING,TRANSFER,EXTRAS','extraIds'=>'present|array|max:100','extraIds.*'=>'string|max:150','note'=>'nullable|string|max:5000'];
                foreach(['hotel','course','board','roomType','roomName','accommodation','golfContract','hotelContract','packageId','tariffId','citizen','market','subMarket','handling','transfer'] as $f)$rules[$f]='nullable|string|max:150';
                foreach(['hotelAllotment','hotelGuarantee','agencyAllotment','agencyGuarantee'] as $f)$rules[$f]='required|boolean';
                Validator::make($line,$rules)->validate();
                if(isset($lineIds[$line['id']]))throw ValidationException::withMessages(['lines'=>'Satır kimliği tekrar ediyor.']);$lineIds[$line['id']]=true;
                if($kind!=='hotel-golf'&&$line['category']!==$kind)throw ValidationException::withMessages(['lines'=>'Bu satır teklif türüne uygun değil.']);
                if($record['status']==='CONFIRMED'&&($line['sellPrice']??null)===null)throw ValidationException::withMessages(['sellPrice'=>'Onay için satış fiyatı girilmelidir.']);
                $hotel=$find($read('hotels'),$line['hotel']??'');
                if(!empty($line['hotel'])&&!$hotel)throw ValidationException::withMessages(['hotel'=>'Otel bulunamadı.']);
                if($line['category']==='golf'){
                    $course=$find($read('golf-courses'),$line['course']??'',['contractKey','code']);
                    if(!$course)throw ValidationException::withMessages(['course'=>'Golf sahası seçin.']);
                    if(!empty($line['golfContract'])){
                        $c=$find($read('golf-contracts'),$line['golfContract']);
                        if(!$c||$c['courseKey']!==$line['course']||$c['status']!=='ACTIVE'||$c['firstDate']>$line['firstDate']||$c['lastDate']<$line['lastDate'])throw ValidationException::withMessages(['golfContract'=>'Kontrat saha veya tarih aralığına uygun değil.']);
                    }
                }else{
                    if(!$hotel)throw ValidationException::withMessages(['hotel'=>'Otel seçin.']);
                    if($line['firstDate']>=$line['lastDate'])throw ValidationException::withMessages(['lastDate'=>'Çıkış tarihi girişten sonra olmalıdır.']);
                    $room=$find($read('catalog-room-types'),$line['roomType']??'');$child=$find($room['children']??[],$line['roomName']??'');
                    if(!$child||(!empty($child['hotel'])&&$child['hotel']!==$hotel['name']))throw ValidationException::withMessages(['roomName'=>'Oda seçilen otele ait değil.']);
                    if(!$find($read('catalog-board-types'),$line['board']??''))throw ValidationException::withMessages(['board'=>'Pansiyon seçin.']);
                    foreach(['hotelContract'=>'contracts','packageId'=>'packages'] as $field=>$collection)if(!empty($line[$field])){
                        $c=$find($hotel['details'][$collection]??[],$line[$field]);
                        if(!$c||$c['status']!=='ACTIVE'||$c['firstDate']>$line['firstDate']||$c['lastDate']<$line['lastDate'])throw ValidationException::withMessages([$field=>'Tarife otel veya tarih aralığına uygun değil.']);
                    }
                    if(!empty($line['tariffId'])){
                        $package=$line['category']==='hotel-golf';$parent=$find($hotel['details'][$package?'packages':'contracts']??[],$line[$package?'packageId':'hotelContract']??'');
                        if(!$find($parent[$package?'rounds':'prices']??[],$line['tariffId']))throw ValidationException::withMessages(['tariffId'=>'Fiyat satırı seçilen tarifeye ait değil.']);
                    }
                }
                foreach(['citizen'=>'citizens','market'=>'markets'] as $field=>$set)if(!empty($line[$field])&&!$find($read($set),$line[$field]))throw ValidationException::withMessages([$field=>'Seçilen tanım bulunamadı.']);
                foreach($line as &$value)if($value===null)$value='';unset($value);
                foreach(['pax','freePax','rooms','infants','children'] as $field)$line[$field]=(int)$line[$field];
                foreach(['buyPrice','sellPrice','buyExtras','sellExtras'] as $field)$line[$field]=(string)$line[$field];
            }unset($line);
            foreach(['agency','note','optionDate'] as $field)$record[$field]=$record[$field]??'';
        }unset($record);
        return $records;
    }
}
