<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetupRecordsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.setup_mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('setup_mysql');
        (require database_path('migrations/2026_09_02_000001_create_setup_record_sets.php'))->up();
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function row(string $id = '1', string $code = 'T'): array
    {
        return ['id' => $id, 'fields' => ['Test', $code], 'children' => []];
    }

    public function test_currency_aliases_are_normalized_and_other_currencies_rejected(): void
    {
        $url = '/api/setup-records/golf-tee-times';
        $row = ['id' => 'currency-test', 'course' => 'Course', 'date' => '2026-09-10', 'time' => '10:00', 'pax' => 4, 'price' => '7.50', 'currency' => 'EU', 'special' => false, 'sales' => 0, 'optionDate' => '2026-09-09'];
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk()->assertJsonPath('records.0.currency', 'EUR')->assertJsonPath('records.0.price', '7.50');
        foreach (['USD', 'GBP', 'TL', 'EUR', 'TRY'] as $i => $currency) {
            $row['currency'] = $currency;
            $this->putJson($url, ['records' => [$row], 'version' => $i + 1])->assertOk()->assertJsonPath('records.0.currency', $currency === 'TRY' ? 'TL' : $currency);
        }
        $row['currency'] = 'JPY';
        $this->putJson($url, ['records' => [$row], 'version' => 6])->assertStatus(422);
    }

    public function test_tee_times_persist_validate_and_protect_concurrent_updates(): void
    {
        $url = '/api/setup-records/golf-tee-times';
        $row = ['id' => 'tee-1', 'course' => 'Titanic Course', 'date' => '2019-02-28', 'time' => '13:55', 'pax' => 3, 'price' => '7.50', 'currency' => 'EU', 'special' => true, 'sales' => 0, 'optionDate' => '2019-02-28'];
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk();
        $row['price'] = '8.25';
        $this->putJson($url, ['records' => [$row], 'version' => 1])->assertOk();
        $this->getJson($url)->assertJsonPath('records.0.price', '8.25');
        foreach (['pax' => 0, 'date' => '2019-02-30', 'time' => '25:00', 'price' => '-5', 'currency' => 'INVALID'] as $field => $value) {
            $this->putJson($url, ['records' => [array_replace($row, [$field => $value])], 'version' => 2])->assertStatus(422);
        }
        $this->putJson($url, ['records' => [], 'version' => 1])->assertStatus(409);
        $this->putJson($url, ['records' => [], 'version' => 2])->assertOk();
        $this->getJson($url)->assertJsonCount(0, 'records');
    }

    public function test_linked_cards_and_complete_reservations_are_persistent(): void
    {
        $agency = ['name' => 'Agency', 'code' => 'A', 'extrasKey' => 'agency-key', 'citizen' => 'TR', 'market' => 'EURO', 'contactEmail' => 'contact@example.com', 'contactName' => 'Contact', 'additionalInfo' => 'Details'];
        $this->postJson('/api/setup-records/agencies/initialize', ['records' => [$agency]])->assertOk();
        $this->getJson('/api/setup-records/agencies')->assertJsonPath('records.0.contactEmail', 'contact@example.com');
        $hotel = ['id' => 20, 'name' => 'Hotel', 'code' => 'H', 'roomType' => 'STD'];
        $this->postJson('/api/setup-records/hotels/initialize', ['records' => [$hotel]])->assertOk();
        $reservation = ['id' => 'r1', 'no' => 'RSV-1', 'hotel' => '20', 'agency' => 'agency-key', 'state' => 'REQUEST', 'checkIn' => '2026-09-10', 'checkOut' => '2026-09-12', 'night' => '2', 'roomCount' => '1', 'voucher' => 'ABC', 'note' => 'Keep me', 'agencyGuarantee' => true];
        $this->postJson('/api/setup-records/hotel-reservations/initialize', ['records' => [$reservation]])->assertOk();
        $this->getJson('/api/setup-records/hotel-reservations')->assertJsonPath('records.0.note', 'Keep me')->assertJsonPath('records.0.agencyGuarantee', true);
        $reservation['checkOut'] = '2026-09-09';
        $this->putJson('/api/setup-records/hotel-reservations', ['records' => [$reservation], 'version' => 1])->assertStatus(422);
    }

    public function test_room_catalog_rename_updates_hotel_references_and_preserves_children(): void
    {
        $room = ['id' => 'room-key', 'name' => 'Standard', 'code' => 'STD', 'children' => [['id' => 'child-key', 'name' => 'Sea View', 'code' => 'SV', 'hotel' => 'Hotel']]];
        $this->postJson('/api/setup-records/catalog-room-types/initialize', ['records' => [$room]])->assertOk();
        $this->postJson('/api/setup-records/hotels/initialize', ['records' => [['id' => 20, 'name' => 'Hotel', 'code' => 'H', 'roomType' => 'STD']]])->assertOk();
        $room['code'] = 'STANDARD';
        $this->putJson('/api/setup-records/catalog-room-types', ['records' => [$room], 'version' => 1])->assertOk()->assertJsonPath('relatedKinds.0', 'hotels')->assertJsonPath('records.0.children.0.id', 'child-key');
        $this->getJson('/api/setup-records/hotels')->assertJsonPath('records.0.roomType', 'STANDARD')->assertJsonPath('version', 2);
    }

    public function test_course_rename_links_legacy_tee_times_and_retains_details(): void
    {
        $course = ['name' => 'Old Course', 'code' => 'C', 'hotels' => '', 'contractKey' => 'C'];
        $tee = ['id' => 't1', 'course' => 'Old Course', 'date' => '2026-09-10', 'time' => '10:00', 'pax' => 4, 'price' => '10', 'currency' => 'EU', 'special' => false, 'sales' => 0, 'optionDate' => '2026-09-09'];
        $this->postJson('/api/setup-records/golf-courses/initialize', ['records' => [$course]])->assertOk();
        $this->postJson('/api/setup-records/golf-tee-times/initialize', ['records' => [$tee]])->assertOk();
        $course['name'] = 'New Course';
        $course['details'] = ['description' => 'Saved description', 'map' => '', 'active' => true, 'mustNumber' => 2, 'nearby' => [], 'closings' => [], 'extras' => []];
        $this->putJson('/api/setup-records/golf-courses', ['records' => [$course], 'version' => 1])->assertOk()->assertJsonPath('records.0.details.description', 'Saved description');
        $this->getJson('/api/setup-records/golf-tee-times')->assertJsonPath('records.0.course', 'New Course')->assertJsonPath('records.0.courseKey', 'C');
    }

    public function test_reservations_validate_course_closures_tee_capacity_and_references(): void
    {
        $this->postJson('/api/setup-records/hotels/initialize', ['records' => [['id' => 20, 'name' => 'Hotel', 'code' => 'H']]])->assertOk();
        $this->postJson('/api/setup-records/agencies/initialize', ['records' => [['name' => 'Agency', 'code' => 'A']]])->assertOk();
        $course = ['name' => 'Course', 'code' => 'C', 'hotels' => '', 'details' => ['active' => true, 'closings' => [['closedFrom' => '2026-10-01', 'closedTo' => '2026-10-05']]]];
        $this->postJson('/api/setup-records/golf-courses/initialize', ['records' => [$course]])->assertOk();
        $tee = ['id' => 't1', 'course' => 'Course', 'courseKey' => 'C', 'date' => '2026-09-10', 'time' => '10:00', 'pax' => 2, 'price' => '10', 'currency' => 'EU', 'special' => false, 'sales' => 0, 'optionDate' => '2026-09-09'];
        $this->postJson('/api/setup-records/golf-tee-times/initialize', ['records' => [$tee]])->assertOk();
        $row = ['id' => 'r1', 'no' => 'GLF-1', 'hotel' => '20', 'agency' => 'A', 'course' => 'C', 'state' => 'CONFIRM', 'gameDate' => '2026-09-10', 'time' => '10:00', 'pax' => '2', 'freePax' => '0', 'teeTimeId' => 't1'];
        $url = '/api/setup-records/golf-reservations';
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk();
        foreach ([['pax' => '3'], ['gameDate' => '2026-10-02', 'teeTimeId' => ''], ['time' => '11:00'], ['course' => 'missing'], ['agency' => 'missing'], ['contract' => 'missing']] as $change) {
            $this->putJson($url, ['records' => [array_replace($row, $change)], 'version' => 1])->assertStatus(422);
        }
        $this->getJson($url)->assertJsonPath('records.0.pax', '2')->assertJsonPath('version', 1);
    }

    public function test_initialize_does_not_overwrite_existing_records(): void
    {
        $url = '/api/setup-records/citizens';
        $this->getJson($url)->assertOk()->assertJsonPath('initialized', false);
        $this->postJson($url.'/initialize', ['records' => [$this->row()], 'importHash' => str_repeat('a', 64)])->assertOk();
        $this->postJson($url.'/initialize', ['records' => []])->assertOk()->assertJsonCount(1, 'records');
        $this->getJson($url)->assertJsonPath('importHash', str_repeat('a', 64));
    }

    public function test_update_is_versioned_and_backed_up(): void
    {
        $url = '/api/setup-records/markets';
        $this->postJson($url.'/initialize', ['records' => [$this->row()]])->assertOk();
        $this->putJson($url, ['records' => [$this->row('1', 'NEW')], 'version' => 1])->assertOk()->assertJsonPath('version', 2);
        $this->putJson($url, ['records' => [], 'version' => 1])->assertStatus(409);
        $this->getJson($url)->assertJsonPath('records.0.fields.1', 'NEW');
        $backup = DB::connection('setup_mysql')->table('setup_record_backups')->first();
        $this->assertSame('T', json_decode($backup->records, true)[0]['fields'][1]);
        $this->putJson($url, ['records' => [], 'version' => 2])->assertOk()->assertJsonCount(0, 'records');
        $this->getJson($url)->assertJsonPath('initialized', true)->assertJsonCount(0, 'records');
    }

    public function test_duplicate_codes_and_invalid_parity_are_rejected(): void
    {
        $this->postJson('/api/setup-records/citizens/initialize', ['records' => [$this->row(), $this->row('2', 't')]])->assertStatus(422);
        $this->postJson('/api/setup-records/parity/initialize', ['records' => [['pax' => -1, 'inf' => 0, 'chd' => 0, 'roomType' => 'STD', 'parity' => '1']]])->assertStatus(422);
    }

    public function test_parent_renaming_preserves_children(): void
    {
        $row = $this->row();
        $row['children'] = [$this->row('child', 'CHILD')];
        $url = '/api/setup-records/extra-sellings';
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk();
        $row['fields'] = ['Renamed', 'NEW'];
        $this->putJson($url, ['records' => [$row], 'version' => 1])->assertOk()->assertJsonPath('records.0.children.0.fields.1', 'CHILD');
    }

    public function test_unknown_groups_and_external_origins_are_rejected(): void
    {
        $this->getJson('/api/setup-records/unknown')->assertNotFound();
        $this->withHeaders(['Origin' => 'https://untrusted.example'])->postJson('/api/setup-records/citizens/initialize', ['records' => []])->assertForbidden();
    }

    public function test_hotel_and_golf_extras_keep_separate_children_and_fixed_groups(): void
    {
        $url = '/api/setup-records/hotel-golf-extras';
        $groups = [
            ['id' => 'hotel-golf-extras-0', 'fields' => ['HOTEL EXTRAS', 'HOTEL'], 'children' => []],
            ['id' => 'hotel-golf-extras-1', 'fields' => ['GOLF EXTRAS', 'GOLF'], 'children' => []],
        ];
        $this->postJson($url.'/initialize', ['records' => $groups])->assertOk()->assertJsonCount(2, 'records');
        $groups[0]['children'] = [$this->row('hotel-child', 'EXTRA')];
        $groups[1]['children'] = [$this->row('golf-child', 'EXTRA')];
        $this->putJson($url, ['records' => $groups, 'version' => 1])->assertOk();
        $this->getJson($url)->assertJsonPath('records.0.children.0.id', 'hotel-child')->assertJsonPath('records.1.children.0.id', 'golf-child');
        $groups[0]['fields'][0] = 'Changed';
        $this->putJson($url, ['records' => $groups, 'version' => 2])->assertStatus(422);
        $this->getJson($url)->assertJsonPath('version', 2)->assertJsonPath('records.0.fields.0', 'HOTEL EXTRAS');
    }

    public function test_course_deletion_persists_is_backed_up_and_leaves_related_sets_untouched(): void
    {
        $url = '/api/setup-records/golf-courses';
        $carya = ['name' => 'Carya Course', 'hotels' => 'Zeynep Golf, Regnum Carya', 'code' => 'Carya'];
        $zeynep = ['name' => 'Zeynep Golf', 'hotels' => 'Zeynep Golf', 'code' => 'Zeynep Golf'];
        $this->postJson($url.'/initialize', ['records' => [$carya, $zeynep]])->assertOk()->assertJsonPath('version', 1);
        $game = ['id' => 'zeynep-game', 'courseKey' => 'Zeynep Golf', 'name' => '18 Holes', 'code' => 'Z18', 'round' => 1];
        $this->postJson('/api/setup-records/golf-games/initialize', ['records' => [$game]])->assertOk();
        $this->postJson('/api/setup-records/golf-contracts/initialize', ['records' => []])->assertOk();
        $related = DB::connection('setup_mysql')->table('setup_record_sets')->whereIn('kind', ['golf-games', 'golf-contracts'])->get()->toJson();
        $this->putJson($url, ['records' => [$carya], 'version' => 1])->assertOk()->assertJsonPath('version', 2);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'records')->assertJsonPath('records.0', $carya);
        $this->postJson($url.'/initialize', ['records' => [$carya, $zeynep]])->assertOk()->assertJsonCount(1, 'records');
        $backup = DB::connection('setup_mysql')->table('setup_record_backups')->where('kind', 'golf-courses')->first();
        $this->assertSame([$carya, $zeynep], json_decode($backup->records, true));
        $this->assertSame($related, DB::connection('setup_mysql')->table('setup_record_sets')->whereIn('kind', ['golf-games', 'golf-contracts'])->get()->toJson());
        $this->putJson($url, ['records' => [], 'version' => 1])->assertStatus(409);
        $carya['contractKey'] = 'Carya'; $carya['code'] = 'Carya-new'; $carya['hotels'] = '';
        $this->putJson($url, ['records' => [$carya], 'version' => 2])->assertOk()->assertJsonPath('records.0.contractKey', 'Carya')->assertJsonPath('records.0.hotels', '');
        $this->putJson($url, ['records' => [], 'version' => 3])->assertOk();
        $this->getJson($url)->assertJsonPath('initialized', true)->assertJsonCount(0, 'records');
        $this->postJson($url.'/initialize', ['records' => [$carya, $zeynep]])->assertOk()->assertJsonCount(0, 'records');
    }

    public function test_course_records_reject_invalid_values_and_duplicate_identity(): void
    {
        $url = '/api/setup-records/golf-courses';
        $row = ['name' => 'Carya Course', 'hotels' => '', 'code' => 'Carya', 'contractKey' => 'stable-carya'];
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk();
        foreach ([['name' => ' '], ['code' => ''], ['hotels' => []], ['contractKey' => ''], ['name' => str_repeat('a', 151)], ['unexpected' => true]] as $patch) {
            $this->putJson($url, ['records' => [array_replace($row, $patch)], 'version' => 1])->assertStatus(422);
        }
        foreach ([['code' => 'carya', 'contractKey' => 'other'], ['code' => 'other']] as $patch) {
            $this->putJson($url, ['records' => [$row, array_replace($row, $patch)], 'version' => 1])->assertStatus(422);
        }
        $this->getJson($url)->assertJsonPath('version', 1)->assertJsonCount(1, 'records');
    }

    public function test_course_games_are_persistent_validated_and_scoped_by_course(): void
    {
        $url = '/api/setup-records/golf-games';
        $row = ['id' => 'carya-18', 'courseKey' => 'Carya', 'name' => '18 Holes Game', 'code' => 'C18 holes', 'round' => 1];
        $other = array_replace($row, ['id' => 'dunes-18', 'courseKey' => 'Dunes']);
        $this->postJson($url.'/initialize', ['records' => [$row, $other]])->assertOk();
        $row['round'] = 2;
        $row['name'] = 'Updated Game';
        $this->putJson($url, ['records' => [$row, $other], 'version' => 1])->assertOk()->assertJsonPath('version', 2);
        $this->getJson($url)->assertJsonPath('records.0.round', 2)->assertJsonPath('records.1.round', 1);
        $third = array_replace($row, ['id' => 'carya-9', 'code' => 'C9 holes']);
        $this->putJson($url, ['records' => [$row, $other, $third], 'version' => 2])->assertOk();
        foreach ([['name' => ''], ['code' => ' '], ['round' => 0], ['round' => 1.5], ['round' => '1'], ['round' => 1001], ['courseKey' => '']] as $patch) {
            $this->putJson($url, ['records' => [array_replace($row, $patch), $other], 'version' => 3])->assertStatus(422);
        }
        $this->putJson($url, ['records' => [$row, array_replace($row, ['id' => 'duplicate', 'code' => 'c18 HOLES'])], 'version' => 3])->assertStatus(422);
        $this->putJson($url, ['records' => [$row, $row], 'version' => 3])->assertStatus(422);
        $this->putJson($url, ['records' => [], 'version' => 2])->assertStatus(409);
        $this->putJson($url, ['records' => [$other], 'version' => 3])->assertOk()->assertJsonCount(1, 'records');
        $this->postJson($url.'/initialize', ['records' => [$row, $other]])->assertOk()->assertJsonCount(1, 'records');
        $this->getJson($url)->assertJsonPath('records.0.courseKey', 'Dunes');
        $this->assertSame(3, DB::connection('setup_mysql')->table('setup_record_backups')->where('kind', 'golf-games')->count());
    }

    private function ageRow(): array
    {
        return ['id' => 'age-01211', 'code' => '01211', 'infantFrom' => 0, 'infantTo' => 1, 'childFrom' => 2, 'childTo' => 11];
    }

    public function test_agency_extras_preserve_other_agencies_and_backup_edits(): void
    {
        $url = '/api/setup-records/agency-extras';
        $row = ['id' => 'aqua-1', 'agencyKey' => 'AQUAMICE', 'firstDate' => '2018-12-01', 'lastDate' => '2019-12-31', 'description' => 'GOLF BAG', 'buyPrice' => '15', 'sellPrice' => '15', 'currency' => 'EU', 'priceType' => 'PP', 'obligation' => false, 'ageTable' => '01211', 'buyInfant' => '0', 'sellInfant' => '0', 'buyChild' => '0', 'sellChild' => '0'];
        $other = array_replace($row, ['id' => 'atl-1', 'agencyKey' => 'ATL']);
        $this->postJson($url.'/initialize', ['records' => [$row, $other]])->assertOk();
        $row['sellPrice'] = '22.5000';
        $row['obligation'] = true;
        $this->putJson($url, ['records' => [$row, $other], 'version' => 1])->assertOk()->assertJsonPath('version', 2);
        $this->getJson($url)->assertJsonPath('records.0.sellPrice', '22.5000')->assertJsonPath('records.0.ageTable', '01211')->assertJsonPath('records.0.obligation', true)->assertJsonPath('records.1.sellPrice', '15');
        foreach ([['firstDate' => '2019-02-29'], ['lastDate' => '2017-01-01'], ['buyPrice' => '-1'], ['sellInfant' => '1.12345'], ['buyChild' => 2], ['description' => ''], ['agencyKey' => ''], ['ageTable' => ''], ['priceType' => 'XXX'], ['currency' => 'XXX'], ['obligation' => 'false'], ['obligation' => 1]] as $patch) {
            $this->putJson($url, ['records' => [array_replace($row, $patch), $other], 'version' => 2])->assertStatus(422);
        }
        $this->putJson($url, ['records' => [$row, $row], 'version' => 2])->assertStatus(422);
        $this->putJson($url, ['records' => [], 'version' => 1])->assertStatus(409);
        $this->putJson($url, ['records' => [$other], 'version' => 2])->assertOk()->assertJsonCount(1, 'records');
        $this->postJson($url.'/initialize', ['records' => [$row, $other]])->assertOk()->assertJsonCount(1, 'records');
        $this->assertSame(2, DB::connection('setup_mysql')->table('setup_record_backups')->where('kind', 'agency-extras')->count());
        $this->getJson($url)->assertJsonPath('records.0.agencyKey', 'ATL');
    }

    public function test_golf_contracts_save_prices_dates_and_preserve_other_courses(): void
    {
        $url = '/api/setup-records/golf-contracts';
        $row = ['id' => 'carya-xx', 'courseKey' => 'Carya', 'game' => '2018-2019 XX', 'name' => '2 Round Package', 'firstDate' => '2023-01-19', 'lastDate' => '2023-01-22', 'rrOhg' => '222', 'toOhg' => '222', 'toHg' => '222', 'rrHg' => '222', 'currency' => 'USD', 'contractType' => 'BUY', 'seasonType' => 'MAIN', 'status' => 'ACTIVE'];
        $other = array_replace($row, ['id' => 'dunes', 'courseKey' => 'Dunes']);
        $this->postJson($url.'/initialize', ['records' => [$row, $other]])->assertOk();
        $row['groupId'] = 'parent-carya';
        $row['rrOhg'] = '12.5000';
        $row['seasonType'] = 'ACTION';
        $this->putJson($url, ['records' => [$row, $other], 'version' => 1])->assertOk();
        $this->getJson($url)->assertJsonPath('records.0.groupId', 'parent-carya')->assertJsonPath('records.0.rrOhg', '12.5000')->assertJsonPath('records.1.rrOhg', '222');
        foreach ([['groupId' => ''], ['groupId' => []], ['groupId' => str_repeat('x', 151)], ['lastDate' => '2022-01-01'], ['firstDate' => '2023-02-30'], ['rrOhg' => '-1'], ['toOhg' => '1.23456'], ['toHg' => 22], ['game' => ''], ['currency' => 'XXX']] as $change) {
            $this->putJson($url, ['records' => [array_replace($row, $change), $other], 'version' => 2])->assertStatus(422);
        }
        $this->putJson($url, ['records' => [$row, $row], 'version' => 2])->assertStatus(422);
        $this->putJson($url, ['records' => [$other], 'version' => 1])->assertStatus(409);
        $this->putJson($url, ['records' => [$other], 'version' => 2])->assertOk()->assertJsonCount(1, 'records');
        $this->postJson($url.'/initialize', ['records' => [$row, $other]])->assertOk()->assertJsonCount(1, 'records');
        $this->getJson($url)->assertJsonPath('records.0.courseKey', 'Dunes');
        $this->assertSame(2, DB::connection('setup_mysql')->table('setup_record_backups')->where('kind', 'golf-contracts')->count());
    }

    public function test_age_tables_create_edit_delete_and_preserve_leading_zeros(): void
    {
        $url = '/api/setup-records/age-tables';
        $row = $this->ageRow();
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk()->assertJsonPath('records.0.code', '01211');
        $row['childTo'] = 12;
        $this->putJson($url, ['records' => [$row], 'version' => 1])->assertOk()->assertJsonPath('version', 2);
        $this->getJson($url)->assertJsonPath('records.0.code', '01211')->assertJsonPath('records.0.childTo', 12)->assertJsonPath('records.0.infantFrom', 0);
        $backup = DB::connection('setup_mysql')->table('setup_record_backups')->where('kind', 'age-tables')->first();
        $this->assertSame(11, json_decode($backup->records, true)[0]['childTo']);
        $this->putJson($url, ['records' => [], 'version' => 2])->assertOk();
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk()->assertJsonCount(0, 'records');
    }

    public function test_invalid_age_tables_do_not_change_saved_data(): void
    {
        $url = '/api/setup-records/age-tables';
        $row = $this->ageRow();
        $this->postJson($url.'/initialize', ['records' => [$row]])->assertOk();
        foreach ([['code' => 1211], ['code' => ''], ['infantTo' => 1.5], ['infantTo' => '1'], ['infantFrom' => -1], ['childTo' => 121], ['childFrom' => 1], ['childTo' => 1], ['infantFrom' => 2]] as $change) {
            $this->putJson($url, ['records' => [array_replace($row, $change)], 'version' => 1])->assertStatus(422);
        }
        $this->putJson($url, ['records' => [$row, array_replace($row, ['id' => 'second'])], 'version' => 1])->assertStatus(422);
        $this->putJson($url, ['records' => [$row, array_replace($row, ['code' => 'OTHER'])], 'version' => 1])->assertStatus(422);
        $this->getJson($url)->assertJsonPath('version', 1)->assertJsonPath('records.0.childTo', 11);
    }
}
