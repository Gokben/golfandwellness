<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.connections.contract_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
$db = Illuminate\Support\Facades\DB::connection('contract_test');
$db->statement('CREATE TABLE setup_record_backups (id INTEGER PRIMARY KEY, kind TEXT, records TEXT)');
$sets = collect([
 'hotels'=>(object)['kind'=>'hotels','records'=>json_encode([['id'=>1,'details'=>['contracts'=>[['id'=>'hotel-c']]]]])],
 'agencies'=>(object)['kind'=>'agencies','records'=>json_encode([['code'=>'A','hotelContracts'=>[['id'=>'copy','contracts'=>[['id'=>'agency-c']]]]]])],
]);
$blocked = function ($fn, $label) {
 try { $fn(); } catch (Illuminate\Validation\ValidationException $e) { echo "PASS $label\n"; return; }
 throw new RuntimeException("Expected rejection: $label");
};
App\Support\ContractProtection::enforce($db,'hotels',[], $sets);
echo "PASS unused hotel may be removed\n";
$sets['hotel-reservations']=(object)['kind'=>'hotel-reservations','records'=>'[{"hotelContract":"hotel-c","agencyContract":"agency-c","state":"CANCEL"}]'];
$blocked(fn()=>App\Support\ContractProtection::enforce($db,'hotels',[],$sets),'used hotel protected including cancelled reservations');
$blocked(fn()=>App\Support\ContractProtection::enforce($db,'agencies',[],$sets),'used agency protected');
$sets->forget('hotel-reservations');
$db->table('setup_record_backups')->insert(['kind'=>'hotel-reservations','records'=>'[{"hotelContract":"hotel-c"}]']);
$blocked(fn()=>App\Support\ContractProtection::enforce($db,'hotels',[],$sets),'historical reservation protected');
$blocked(fn()=>App\Support\ContractProtection::enforce($db,'hotel-reservations',[['hotelContract'=>'deleted']],$sets),'cannot reserve deleted contract');
$sets['proposals-hotel']=(object)['kind'=>'proposals-hotel','records'=>'[{"lines":[{"hotelContract":"hotel-c"}]}]'];
$blocked(fn()=>App\Support\ContractProtection::enforce($db,'hotels',[],$sets),'proposal reference protected');
$imports=json_decode(file_get_contents(__DIR__.'/../docs/imports/remaining-hotels-2026-09-10.json'),true)['imports'];
$hotel=array_values(array_filter($imports,fn($h)=>!empty($h['details']['contracts'])))[0];
$details=$hotel['details'];
$details['contracts'][0]['bookingFirstDate']=$details['contracts'][0]['firstDate'];
$details['contracts'][0]['bookingLastDate']='2099-12-31';
$blocked(fn()=>App\Support\HotelDetails::validate($details),'booking end cannot exceed contract end');
$details['contracts'][0]['bookingLastDate']=$details['contracts'][0]['lastDate'];
App\Support\HotelDetails::validate($details);
echo "PASS booking end equal to contract end\n";
$a=$details['contracts'][0];
$a['firstDate']='2026-11-01';$a['lastDate']='2026-11-22';
$a['validityFirstDate']='2026-11-01';$a['validityLastDate']='2027-03-31';$a['conditions']=[];$a['rules']=[];
$a['bookingFirstDate']='2026-01-01';$a['bookingLastDate']='2027-03-31';
$a['sourceNotes']=['Source: GLORIA SERENITY RESORT 2026 - 2027 WINTER SEASON POUND RATES (1).pdf;'];
$b=$a;$b['id']='later';$b['firstDate']='2027-03-01';$b['lastDate']='2027-03-31';
$details['contracts']=[$a,$b];
// The date bound must use the season, not its earliest period.
App\Support\HotelDetails::validate($details);
echo "PASS season end used across periods\n";
