<?php
namespace Tests\Feature;

use App\Support\LinkedRecords;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgencyContractCopyTest extends TestCase
{
    private function agency(): array
    {
        $imports = json_decode(file_get_contents(base_path('docs/imports/remaining-hotels-2026-09-10.json')), true)['imports'];
        $source = array_values(array_filter($imports, fn ($hotel) => !empty($hotel['details']['contracts'])))[0];
        $contract = $source['details']['contracts'][0];
        return ['name'=>'Test Agency', 'code'=>'TEST', 'hotelContracts'=>[[
            'id'=>'copy', 'name'=>'Winter', 'hotelName'=>'Test Hotel', 'sourceSeasonId'=>'source',
            'currency'=>$contract['currency'], 'markupMode'=>'percent', 'markupAmount'=>10, 'contracts'=>[$contract],
        ]]];
    }

    public function test_preserves_agency_contracts_and_nested_prices(): void
    {
        $agency = $this->agency();
        $this->assertSame([$agency], LinkedRecords::validate('agencies', [$agency]));
    }

    public function test_rejects_different_currency_in_copy(): void
    {
        $agency = $this->agency();
        $agency['hotelContracts'][0]['currency'] = $agency['hotelContracts'][0]['currency'] === 'GBP' ? 'EUR' : 'GBP';
        $this->expectException(ValidationException::class);
        LinkedRecords::validate('agencies', [$agency]);
    }

    public function test_rejects_negative_markup(): void
    {
        $agency = $this->agency();
        $agency['hotelContracts'][0]['markupAmount'] = -10;
        $this->expectException(ValidationException::class);
        LinkedRecords::validate('agencies', [$agency]);
    }
}
