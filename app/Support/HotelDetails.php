<?php
namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HotelDetails
{
    public static function validate(array $details): void
    {
        $rules = [
            'details' => 'array:contractsStatus,accountingStatus,contracts,extras,packages',
            'details.contractsStatus' => 'required|in:available,empty,unavailable',
            'details.accountingStatus' => 'required|in:empty,unavailable',
            'details.extras' => 'present|array|max:1000',
            'details.packages' => 'present|array|max:1000',
            'details.extras.*.id' => 'required|string|max:150|distinct',
            'details.extras.*.description' => 'required|string|max:150',
            'details.extras.*.currency' => 'required|in:USD,GBP,TL,EUR',
            'details.extras.*.priceType' => 'required|in:PP,PROOM,FIX',
            'details.extras.*.obligation' => 'required|boolean',
            'details.extras.*.ageTable' => 'required|string|max:150',
            'details.packages.*.id' => 'required|string|max:150|distinct',
            'details.packages.*.name' => 'required|string|max:150',
            'details.packages.*.roomType' => 'required|string|max:150',
            'details.packages.*.roomName' => 'required|string|max:150',
            'details.packages.*.nights' => 'required|integer|min:1',
            'details.packages.*.contractType' => 'required|in:MAIN,ACTION',
            'details.packages.*.calculationType' => 'required|in:Check In Base,Accommodation Base,Average Base',
            'details.packages.*.status' => 'required|in:ACTIVE,PENDING',
            'details.packages.*.rounds' => 'present|array|max:1000',
            'details.packages.*.rounds.*.id' => 'required|string|max:150',
            'details.packages.*.rounds.*.courseKey' => 'required|string|max:150',
            'details.packages.*.rounds.*.rounds' => 'present|nullable|integer|min:1',
            'details.packages.*.rounds.*.accommodation' => 'required|string|max:150',
            'details.packages.*.rounds.*.price' => 'required|numeric|min:0',
            'details.packages.*.rounds.*.currency' => 'required|in:USD,GBP,TL,EUR',
        ];
        foreach (['buyPrice','sellPrice','buyInfant','sellInfant','buyChild','sellChild'] as $field) $rules['details.extras.*.'.$field] = 'required|numeric|min:0';
        $dated = ['details.packages.*'];
        foreach (['firstDate','lastDate'] as $field) {
            $rules['details.extras.*.'.$field] = 'present|nullable|date_format:Y-m-d';
            $rules['details.extras.*.sourceDateIssue.'.$field] = 'required_with:details.extras.*.sourceDateIssue|date_format:Y-m-d';
        }
        $rules['details.extras.*.sourceDateIssue'] = 'sometimes|array:firstDate,lastDate';
        foreach (['bonus' => ['daysTill','reduction','days','childReduction','order'], 'reduction' => ['reduction','payment','order'], 'golferFree' => ['golferPax','freePax','payingPax','order']] as $group => $fields) {
            $prefix = 'details.packages.*.'.$group;
            $rules[$prefix] = 'present|array|max:1000';
            $rules[$prefix.'.*.id'] = 'required|string|max:150';
            foreach ($fields as $field) $rules[$prefix.'.*.'.$field] = 'required|numeric|min:0';
            $dated[] = $prefix.'.*';
        }
        $rules['details.packages.*.bonus.*.calculation'] = 'required|in:PP,PP*PDay,P.Res';
        foreach (['hotelExtras','golfExtras'] as $group) {
            $prefix = 'details.packages.*.'.$group;
            $rules[$prefix] = 'present|array|max:1000';
            foreach (['id','description'] as $field) $rules[$prefix.'.*.'.$field] = 'required|string|max:150';
            foreach (['buyPrice','sellPrice'] as $field) $rules[$prefix.'.*.'.$field] = 'required|numeric|min:0';
            $rules[$prefix.'.*.priceType'] = 'required|in:PP,PROOM,FIX';
            $dated[] = $prefix.'.*';
        }
        $rules['details.contracts'] = 'sometimes|array|max:1000';
        foreach (['id','name','roomType','roomName','market','board'] as $field) $rules['details.contracts.*.'.$field] = 'required|string|max:200';
        $rules['details.contracts.*.id'] .= '|distinct';
        $rules['details.contracts.*.submarket'] = 'present|nullable|string|max:200';
        foreach (['allotment','guarantee'] as $field) $rules['details.contracts.*.'.$field] = 'required|integer|min:0';
        $rules['details.contracts.*.price'] = 'required|numeric|min:0';
        $rules['details.contracts.*.currency'] = 'required|in:USD,GBP,TL,EUR';
        $rules['details.contracts.*.contractType'] = 'required|in:MAIN,ACTION';
        $rules['details.contracts.*.status'] = 'required|in:ACTIVE,PENDING';
        $rules['details.contracts.*.calculationType'] = 'required|in:Accommodation,Chk / In,Average';
        foreach (['validityFirstDate','validityLastDate'] as $field) $rules['details.contracts.*.'.$field] = 'required|date_format:Y-m-d';
        $rules['details.contracts.*.prices'] = 'present|array|max:1000';
        foreach (['id','accommodationId','accommodation','ageTable'] as $field) $rules['details.contracts.*.prices.*.'.$field] = 'required|string|max:150';
        foreach (['pax','infants','children'] as $field) $rules['details.contracts.*.prices.*.'.$field] = 'required|integer|min:0';
        foreach (['parity','price'] as $field) $rules['details.contracts.*.prices.*.'.$field] = 'required|numeric|min:0';
        $rules['details.contracts.*.prices.*.currency'] = 'required|in:USD,GBP,TL,EUR';
        $rules['details.contracts.*.prices.*.manualPrice'] = 'sometimes|boolean';
        $rules['details.contracts.*.conditions'] = 'present|array|max:1000';
        $rules['details.contracts.*.conditions.*.type'] = 'required|in:reduction,stayPay,longStay,ageReduction,freePax';
        $rules['details.contracts.*.conditions.*.id'] = 'required|string|max:150';
        $rules['details.contracts.*.conditions.*.order'] = 'required|integer|min:0';
        $dated[] = 'details.contracts.*';
        $dated[] = 'details.contracts.*.conditions.*';
        foreach (['packages','contracts'] as $group) {
            $rules['details.'.$group.'.*.rules'] = 'present|array|max:1000';
            foreach (['id','appliesTo','excludes'] as $field) $rules['details.'.$group.'.*.rules.*.'.$field] = 'required|string|max:150';
        }
        foreach ($dated as $prefix) foreach (['firstDate','lastDate'] as $field) $rules[$prefix.'.'.$field] = 'required|date_format:Y-m-d';
        Validator::make(['details' => $details], $rules)->validate();
        foreach ($details['extras'] as $extra) {
            $missing = empty($extra['firstDate']) || empty($extra['lastDate']);
            $quarantined = empty($extra['firstDate']) && empty($extra['lastDate']) && isset($extra['sourceDateIssue']);
            if ($missing && !$quarantined) throw ValidationException::withMessages(['records' => 'Ekstra için geçerli bir tarih aralığı girin.']);
        }
        foreach ($details['contracts'] ?? [] as $contract) {
            if ($contract['validityFirstDate'] > $contract['validityLastDate']) throw ValidationException::withMessages(['records' => 'Kontrat geçerlilik bitişi başlangıçtan önce olamaz.']);
            $fieldsByType = ['reduction'=>['reduction','payment'],'stayPay'=>['stayDays','freeDays','paymentDays'],'longStay'=>['minStay','reduction'],'ageReduction'=>['age','reduction'],'freePax'=>['pax','freePax','reduction']];
            foreach ($contract['conditions'] as $condition) {
                $conditionRules = array_fill_keys($fieldsByType[$condition['type']], 'required|numeric|min:0');
                if ($condition['type'] === 'stayPay') $conditionRules['calculation'] = 'required|string|max:100';
                if ($condition['type'] === 'freePax') foreach (['roomType','roomName'] as $field) $conditionRules[$field] = 'required|string|max:150';
                Validator::make($condition, $conditionRules)->validate();
            }
        }
        $checkDates = function ($value) use (&$checkDates) {
            if (!is_array($value)) return;
            if (isset($value['firstDate'], $value['lastDate']) && $value['firstDate'] > $value['lastDate']) throw ValidationException::withMessages(['records' => 'Bitiş tarihi başlangıçtan önce olamaz.']);
            foreach ($value as $key => $child) if ($key !== 'sourceDateIssue') $checkDates($child);
        };
        $checkDates($details);
    }
}
