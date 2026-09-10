<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class GolfReservationPrices {
    public static function ensure(): void {
        DB::connection('setup_mysql')->statement("CREATE TABLE IF NOT EXISTS golf_reservation_prices (reservation_id VARCHAR(150) PRIMARY KEY, buy_price DECIMAL(15,2) NULL, sell_price DECIMAL(15,2) NULL, currency VARCHAR(3) NOT NULL DEFAULT 'EUR', created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB");
    }
    public static function attach(array $records): array {
        $prices=DB::connection('setup_mysql')->table('golf_reservation_prices')->get()->keyBy('reservation_id');
        foreach($records as &$record){$p=$prices->get($record['id']);if($p){$record['buyPrice']=$p->buy_price===null?'':(string)$p->buy_price;$record['sellPrice']=$p->sell_price===null?'':(string)$p->sell_price;$record['currency']=$p->currency;}}
        return $records;
    }
    public static function persist($db,array $before,array $records): array {
        foreach($records as &$record){
            if(!array_key_exists('buyPrice',$record)&&!array_key_exists('sellPrice',$record))continue;
            $price=['buyPrice'=>$record['buyPrice']??null,'sellPrice'=>$record['sellPrice']??null,'currency'=>$record['currency']??'EUR'];
            foreach(['buyPrice','sellPrice'] as $key)if($price[$key]==='')$price[$key]=null;
            Validator::make($price,['buyPrice'=>'nullable|numeric|min:0|max:9999999999999.99','sellPrice'=>'nullable|numeric|min:0|max:9999999999999.99','currency'=>'required|in:EUR,USD,GBP,TL'])->validate();
            $db->table('golf_reservation_prices')->upsert([['reservation_id'=>$record['id'],'buy_price'=>$price['buyPrice'],'sell_price'=>$price['sellPrice'],'currency'=>$price['currency'],'created_at'=>now(),'updated_at'=>now()]],['reservation_id'],['buy_price','sell_price','currency','updated_at']);
            unset($record['buyPrice'],$record['sellPrice'],$record['currency']);
        }
        unset($record);
        $removed=array_diff(array_column($before,'id'),array_column($records,'id'));
        if($removed)$db->table('golf_reservation_prices')->whereIn('reservation_id',$removed)->delete();
        return $records;
    }
}
