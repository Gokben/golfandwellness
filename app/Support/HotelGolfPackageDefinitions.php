<?php
namespace App\Support;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HotelGolfPackageDefinitions
{
    public static function validate(array $records): array
    {
        Validator::make(['records'=>$records],[
            'records.*'=>'array:id,name,code,children',
            'records.*.id'=>'required|string|max:150|distinct:strict',
            'records.*.name'=>'required|string|max:200',
            'records.*.code'=>'required|string|max:150',
            'records.*.children'=>'present|array|max:2000',
            'records.*.children.*'=>'array:id,name,code,courseKey',
            'records.*.children.*.id'=>'required|string|max:150|distinct:strict',
            'records.*.children.*.name'=>'required|string|max:200',
            'records.*.children.*.code'=>'required|string|max:150',
            'records.*.children.*.courseKey'=>'required|string|max:150',
        ])->validate();
        $codes=[];
        foreach($records as $parent){
            $code=mb_strtoupper(trim($parent['code']));
            if(isset($codes[$code]))throw ValidationException::withMessages(['records'=>'Ana paket kodu zaten kullanılıyor.']);
            $codes[$code]=true;$children=[];
            foreach($parent['children'] as $child){
                $key=json_encode([$child['courseKey'],mb_strtoupper(trim($child['code']))]);
                if(isset($children[$key]))throw ValidationException::withMessages(['records'=>'Bu ana paket ve saha için alt paket kodu zaten kullanılıyor.']);
                $children[$key]=true;
            }
        }
        return $records;
    }
}
