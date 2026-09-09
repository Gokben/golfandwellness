<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VouchersTest extends TestCase
{
    public function test_voucher_import_preserves_prefixes_counters_and_existing_records(): void
    {
        config(['database.connections.setup_mysql'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();
        $this->withHeaders(['X-Requested-With'=>'XMLHttpRequest']);
        $rows=json_decode(file_get_contents(resource_path('js/voucherSource.json')),true);
        foreach($rows as &$row){$row['id']='kirpii-voucher-'.$row['id'];$row['agencyKey']=$row['name'];}unset($row);
        $url='/api/setup-records/agency-vouchers';
        $this->postJson($url.'/initialize',['records'=>$rows])->assertOk()->assertJsonCount(26,'records')->assertJsonPath('records.0.shortCode','AQM')->assertJsonPath('records.0.count','00001')->assertJsonPath('records.0.lastCount','00012');
        $this->postJson($url.'/initialize',['records'=>[]])->assertOk()->assertJsonCount(26,'records');
        $this->assertSame(0,DB::connection('setup_mysql')->table('setup_record_backups')->count());
        $invalid=$rows;$invalid[0]['count']=1;
        $this->putJson($url,['records'=>$invalid,'version'=>1])->assertStatus(422);
        $invalid=$rows;$invalid[0]['lastCount']='00000';
        $this->putJson($url,['records'=>$invalid,'version'=>1])->assertStatus(422);
        $invalid=$rows;$invalid[1]['id']=$invalid[0]['id'];
        $this->putJson($url,['records'=>$invalid,'version'=>1])->assertStatus(422);
        $this->getJson($url)->assertOk()->assertJsonPath('version',1)->assertJsonCount(26,'records');
    }
}
