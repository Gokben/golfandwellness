<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ProposalsTest extends TestCase
{
    public function test_three_proposal_types_persist_lines_validate_links_and_protect_versions(): void
    {
        config(['database.connections.setup_mysql'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();$this->withHeaders(['X-Requested-With'=>'XMLHttpRequest']);
        $sets=['agencies'=>[['name'=>'AQUAMICE','code'=>'AQUAMICE']],'hotels'=>[['id'=>27,'name'=>'Cornelia','code'=>'Cornelia']],'golf-courses'=>[['name'=>'Carya','code'=>'Carya','contractKey'=>'Carya']], 'catalog-room-types'=>[['id'=>'room','code'=>'STD','name'=>'Standard','children'=>[['id'=>'child','name'=>'Partial','code'=>'PART','hotel'=>'Cornelia']]]],'catalog-board-types'=>[['id'=>'board','code'=>'ALL IN','name'=>'ALL INCLUSIVE']]];
        foreach($sets as $kind=>$rows)DB::connection('setup_mysql')->table('setup_record_sets')->insert(['kind'=>$kind,'records'=>json_encode($rows),'version'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $line=['id'=>'line','category'=>'golf','hotel'=>'27','course'=>'Carya','firstDate'=>'2026-11-01','lastDate'=>'2026-11-08','pax'=>3,'freePax'=>1,'rooms'=>1,'infants'=>0,'children'=>0,'rounds'=>'1','basis'=>'PERSON','priceType'=>'TOHG','agencyPriceType'=>'TOHG','buyPrice'=>'10','sellPrice'=>'20','buyExtras'=>'0','sellExtras'=>'0','buyCurrency'=>'EUR','sellCurrency'=>'GBP','freeStatus'=>[],'extraIds'=>[],'hotelAllotment'=>false,'hotelGuarantee'=>false,'agencyAllotment'=>false,'agencyGuarantee'=>false,'board'=>'board','roomType'=>'room','roomName'=>'child','teeTime'=>'12:00'];
        foreach(['golf','hotel','hotel-golf'] as $kind){
            $url='/api/setup-records/proposals-'.$kind;$line['category']=$kind;
            $row=['id'=>'proposal','title'=>'Test '.$kind,'createDate'=>'2026-09-10','optionDate'=>'2026-10-01','agency'=>'AQUAMICE','creator'=>'Test','status'=>'OPTIONAL','note'=>'','lines'=>[$line]];
            $this->postJson($url.'/initialize',['records'=>[$row]])->assertOk()->assertJsonCount(1,'records.0.lines')->assertJsonPath('records.0.lines.0.buyCurrency','EUR');
            $this->postJson($url.'/initialize',['records'=>[]])->assertOk()->assertJsonCount(1,'records');
            $bad=$row;$bad['lines'][0]['freePax']=4;$this->putJson($url,['records'=>[$bad],'version'=>1])->assertStatus(422);
            $bad=$row;$bad['lines'][0]['lastDate']='2026-10-01';$this->putJson($url,['records'=>[$bad],'version'=>1])->assertStatus(422);
            $bad=$row;$bad['lines'][0]['buyCurrency']='CAD';$this->putJson($url,['records'=>[$bad],'version'=>1])->assertStatus(422);
            $bad=$row;$bad['lines'][0][$kind==='golf'?'course':'roomName']='missing';$this->putJson($url,['records'=>[$bad],'version'=>1])->assertStatus(422);
            $bad=$row;$bad['status']='CONFIRMED';$bad['lines'][0]['sellPrice']='';$this->putJson($url,['records'=>[$bad],'version'=>1])->assertStatus(422);
            $row['status']='CONFIRMED';$this->putJson($url,['records'=>[$row],'version'=>1])->assertOk()->assertJsonPath('version',2);
            $this->putJson($url,['records'=>[],'version'=>1])->assertStatus(409);
            $this->getJson($url)->assertOk()->assertJsonCount(1,'records.0.lines');
        }
    }
}
