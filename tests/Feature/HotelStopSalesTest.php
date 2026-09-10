<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HotelStopSalesTest extends TestCase
{
    public function test_stop_sales_preserve_dates_validate_relations_and_handle_concurrent_edits(): void
    {
        config(['database.connections.setup_mysql'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();
        $this->withHeaders(['X-Requested-With'=>'XMLHttpRequest']);
        $sets=[
            'hotels'=>[['id'=>27,'name'=>'Cornelia De Luxe Hotel','code'=>'Cornelia'],['id'=>20,'name'=>'Gloria','code'=>'Gloria']],
            'catalog-board-types'=>[['id'=>'board','name'=>'ALL INCLUSIVE','code'=>'ALL IN']],
            'catalog-room-types'=>[['id'=>'room','name'=>'Standard','code'=>'STD','children'=>[['id'=>'child','name'=>'Standard Room Partial View','code'=>'STD Partial','hotel'=>'Cornelia De Luxe Hotel']]]],
            'markets'=>[['id'=>'market','fields'=>['Euro Zone','EURO'],'children'=>[]]],
        ];
        foreach($sets as $kind=>$records)DB::connection('setup_mysql')->table('setup_record_sets')->insert(['kind'=>$kind,'records'=>json_encode($records),'version'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $row=['id'=>'kirpii-stop-sale-2','firstDate'=>'2019-06-20','lastDate'=>'2019-06-22','recordDate'=>'2018-06-29','hotelId'=>'27','boardId'=>'board','roomId'=>'room','subRoomId'=>'child','marketId'=>'market','subMarket'=>'GERMANY'];
        $url='/api/setup-records/hotel-stop-sales';
        $this->postJson($url.'/initialize',['records'=>[$row]])->assertOk()->assertJsonPath('records.0.recordDate','2018-06-29')->assertJsonPath('version',1);
        $this->assertSame(0,DB::connection('setup_mysql')->table('setup_record_backups')->count());
        $this->postJson($url.'/initialize',['records'=>[]])->assertOk()->assertJsonCount(1,'records');
        foreach(['lastDate'=>'2019-06-19','hotelId'=>'20','subRoomId'=>'missing','marketId'=>'missing'] as $field=>$value){
            $bad=$row;$bad[$field]=$value;
            $this->putJson($url,['records'=>[$bad],'version'=>1])->assertStatus(422);
        }
        $this->putJson($url,['records'=>[$row,$row],'version'=>1])->assertStatus(422);
        $this->getJson($url)->assertOk()->assertJsonPath('version',1)->assertJsonPath('records.0.firstDate','2019-06-20');
        $row['lastDate']='2019-06-23';
        $this->putJson($url,['records'=>[$row],'version'=>1])->assertOk()->assertJsonPath('version',2)->assertJsonPath('records.0.lastDate','2019-06-23');
        $this->putJson($url,['records'=>[],'version'=>1])->assertStatus(409);
        $this->getJson($url)->assertOk()->assertJsonCount(1,'records');
        $this->putJson($url,['records'=>[],'version'=>2])->assertOk()->assertJsonCount(0,'records');
    }
}
