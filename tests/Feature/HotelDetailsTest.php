<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HotelDetailsTest extends TestCase
{
    public function test_remaining_hotel_import_preserves_nested_data_and_rejects_invalid_edits(): void
    {
        config(['database.connections.setup_mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
        $imports = json_decode(file_get_contents(base_path('docs/imports/remaining-hotels-2026-09-10.json')), true)['imports'];
        $hotels = array_map(fn ($h) => ['id'=>$h['hotelId'], ...$h['information'], 'details'=>$h['details']], $imports);
        $url = '/api/setup-records/hotels';
        $this->postJson($url.'/initialize', ['records'=>$hotels])->assertOk();
        $saved = $this->getJson($url)->assertJsonCount(16,'records')->json('records');
        $index = array_search(27,array_column($saved,'id'));
        $details = $saved[$index]['details'];
        $this->assertCount(2,$details['contracts']);
        $this->assertCount(9,$details['contracts'][0]['prices']);
        $this->assertCount(5,$details['contracts'][1]['conditions']);
        $this->assertSame('AGE REDUCTION',$details['contracts'][1]['rules'][0]['excludes']);
        $pack = array_values(array_filter($details['packages'], fn($p)=>$p['id']==='kirpii-hotel-package-23'))[0];
        $this->assertCount(6,$pack['rounds']);
        $this->assertCount(1,$pack['hotelExtras']);
        $this->assertCount(3,$pack['golfExtras']);
        $this->assertSame('75',$pack['hotelExtras'][0]['buyPrice']);
        $this->assertSame('2019-12-31',$details['extras'][2]['sourceDateIssue']['firstDate']);
        $this->assertEmpty($details['extras'][2]['firstDate']);
        $this->assertSame('01211',$details['contracts'][0]['prices'][0]['ageTable']);
        // A normal unrelated edit must save even while a source date issue is unresolved.
        $saved[$index]['details']['contracts'][0]['prices'][0]['price']='51';
        $this->putJson($url,['records'=>$saved,'version'=>1])->assertOk();
        $invalid=$saved; $invalid[$index]['details']['contracts'][0]['prices'][0]['currency']='JPY';
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        $invalid=$saved; $invalid[$index]['details']['contracts'][1]['conditions'][0]['payment']='-1';
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        $invalid=$saved; $invalid[$index]['details']['contracts'][1]['validityLastDate']='2020-01-01';
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        $invalid=$saved; $invalid[$index]['details']['extras'][2]['firstDate']='2019-12-31'; $invalid[$index]['details']['extras'][2]['lastDate']='2019-01-01';
        $this->putJson($url,['records'=>$invalid,'version'=>2])->assertStatus(422);
        // Resolving the quarantined range retains the original source values for comparison.
        $saved[$index]['details']['extras'][2]['firstDate']='2018-12-31';
        $saved[$index]['details']['extras'][2]['lastDate']='2019-01-01';
        $this->putJson($url,['records'=>$saved,'version'=>2])->assertOk();
        $this->getJson($url)->assertJsonPath('version',3)->assertJsonPath('records.'.$index.'.details.contracts.0.prices.0.price','51');
    }

    public function test_hotel_details_roundtrip_and_invalid_edits_preserve_saved_data(): void
    {
        config(['database.connections.setup_mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
        $source = json_decode(file_get_contents(base_path('docs/imports/gloria-hotel-2026-09-10.json')), true);
        $hotel = ['id' => 20, ...$source['information'], 'details' => $source['details']];
        $other = ['id' => 27, 'name' => 'Other Hotel', 'code' => 'OTHER'];
        $url = '/api/setup-records/hotels';
        $this->postJson($url.'/initialize', ['records' => [$hotel, $other]])->assertOk();
        $this->getJson($url)->assertJsonCount(2,'records.0.details.extras')->assertJsonCount(4,'records.0.details.packages.0.rounds')->assertJsonPath('records.0.details.extras.0.ageTable','0237');
        $hotel['details']['packages'][0]['rounds'][0]['price'] = '53';
        $this->putJson($url,['records'=>[$hotel,$other],'version'=>1])->assertOk();
        $this->getJson($url)->assertJsonPath('records.0.details.packages.0.rounds.0.price','53')->assertJsonPath('records.1.name','Other Hotel');
        $invalid=$hotel; $invalid['details']['extras'][0]['lastDate']='2018-01-01';
        $this->putJson($url,['records'=>[$invalid,$other],'version'=>2])->assertStatus(422);
        $invalid=$hotel; $invalid['details']['packages'][0]['rounds'][0]['currency']='XXX';
        $this->putJson($url,['records'=>[$invalid,$other],'version'=>2])->assertStatus(422);
        $invalid=$hotel; $invalid['details']['packages'][0]['bonus'][0]['reduction']='-1';
        $this->putJson($url,['records'=>[$invalid,$other],'version'=>2])->assertStatus(422);
        $this->getJson($url)->assertJsonPath('version',2)->assertJsonPath('records.0.details.packages.0.rounds.0.price','53');
    }
}
