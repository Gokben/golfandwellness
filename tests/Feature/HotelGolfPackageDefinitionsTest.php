<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HotelGolfPackageDefinitionsTest extends TestCase
{
    public function test_parent_edits_preserve_all_children_and_duplicate_codes_are_scoped_to_course(): void
    {
        config(['database.connections.setup_mysql'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();
        $this->withHeaders(['X-Requested-With'=>'XMLHttpRequest']);
        $source=json_decode(file_get_contents(resource_path('js/hotelGolfPackageSource.json')),true);
        $records=[];
        foreach($source['parents'] as $parent){
            $children=[];
            foreach($source['courses'] as $index=>$course)for($i=0;$i<8;$i++)$children[]=['id'=>(string)($parent['firstChildId']-$index*8+$i),'name'=>$i===7?'UNLIMITED':($i+1).' ROUND','code'=>$i===7?'UNL':(string)($i+1),'courseKey'=>$course['key']];
            $records[]=['id'=>'kirpii-hgp-'.$parent['id'],'name'=>$parent['name'],'code'=>$parent['code'],'children'=>$children];
        }
        $url='/api/setup-records/hotel-golf-package-definitions';
        $this->postJson($url.'/initialize',['records'=>$records])->assertOk()->assertJsonCount(3,'records')->assertJsonCount(120,'records.0.children')->assertJsonCount(120,'records.1.children')->assertJsonCount(120,'records.2.children')->assertJsonPath('records.0.children.7.code','UNL')->assertJsonPath('records.2.children.0.id','396');
        $this->assertSame(0,DB::connection('setup_mysql')->table('setup_record_backups')->count());
        $records[0]['name']='Updated ADD PAX';
        $this->putJson($url,['records'=>$records,'version'=>1])->assertOk();
        $saved=$this->getJson($url)->assertOk()->json('records');
        $this->assertSame($records,$saved);
        $invalid=$records;$invalid[0]['children'][1]['code']='1';
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        $invalid=$records;$invalid[1]['children'][0]['id']=$invalid[0]['children'][0]['id'];
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        $invalid=$records;$invalid[1]['code']=$invalid[0]['code'];
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        $this->getJson($url)->assertJsonPath('version',2)->assertJsonPath('records.0.children.1.code','2');
    }
}
