<?php
namespace App\Support;
use Illuminate\Validation\ValidationException;
class ReservationVouchers {
    public static function assign(array $before,array $after,array &$vouchers): array {
        $existing=array_column($before,null,'id');
        foreach($after as &$record){
            if(isset($existing[$record['id']])){ $record['voucher']=$existing[$record['id']]['voucher']??''; continue; }
            $found=false;
            foreach($vouchers as &$voucher){
                if($voucher['agencyKey']!==$record['agency'] && $voucher['name']!==$record['agency'])continue;
                $last=(string)$voucher['lastCount'];
                if(!preg_match('/^\d{1,12}$/',$last)||(int)$last>=999999999999)throw ValidationException::withMessages(['records'=>'Voucher sayacı geçersiz veya dolmuş.']);
                $voucher['lastCount']=str_pad((string)((int)$last+1),strlen($last),'0',STR_PAD_LEFT);
                $record['voucher']=$voucher['shortCode'].'-'.$voucher['lastCount'];$found=true;break;
            }
            unset($voucher);
            if(!$found)throw ValidationException::withMessages(['records'=>'Seçilen acente için voucher tanımı bulunamadı. Önce acente voucher bilgilerini tanımlayın.']);
        }
        unset($record);return $after;
    }
}
