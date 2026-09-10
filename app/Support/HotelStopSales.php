<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HotelStopSales
{
    public static function validate(array $records): array
    {
        $sets=DB::connection('setup_mysql')->table('setup_record_sets')->whereIn('kind',['hotels','catalog-board-types','catalog-room-types','markets'])->get()->keyBy('kind');
        $read=fn($kind)=>isset($sets[$kind])?json_decode($sets[$kind]->records,true):[];
        $find=function($rows,$id){foreach($rows as $r)if((string)($r['id']??$r['code'])===$id)return $r;return null;};
        $ids=[];
        foreach($records as $r){
            if(!is_array($r))throw ValidationException::withMessages(['records'=>'Geçersiz kayıt.']);
            Validator::make($r,[
                'id'=>'required|string|max:150','firstDate'=>'required|date_format:Y-m-d','lastDate'=>'required|date_format:Y-m-d|after_or_equal:firstDate','recordDate'=>'required|date_format:Y-m-d',
                'hotelId'=>'required|string|max:150','boardId'=>'required|string|max:150','roomId'=>'required|string|max:150','subRoomId'=>'required|string|max:150','marketId'=>'required|string|max:150','subMarket'=>'required|string|max:150',
            ])->validate();
            if(isset($ids[$r['id']]))throw ValidationException::withMessages(['records'=>'Kayıt kimliği tekrar ediyor.']);
            $ids[$r['id']]=true;
            foreach(['hotelId'=>'hotels','boardId'=>'catalog-board-types','roomId'=>'catalog-room-types','marketId'=>'markets'] as $field=>$kind){
                if(!$find($read($kind),$r[$field]))throw ValidationException::withMessages([$field=>'Seçilen kayıt bulunamadı. Listeyi yenileyin.']);
            }
            $hotel=$find($read('hotels'),$r['hotelId']);
            $room=$find($read('catalog-room-types'),$r['roomId']);
            $child=$find($room['children']??[],$r['subRoomId']);
            if(!$child||(!empty($child['hotel'])&&$child['hotel']!==$hotel['name']))throw ValidationException::withMessages(['subRoomId'=>'Alt oda tipi seçilen otel ve oda tipine ait değil.']);
        }
        return $records;
    }
}
