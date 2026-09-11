<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); });
use App\Support\AgentContractChanges as Changes;
use App\Http\Controllers\GolfAgentController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
function ok($value,$label) { if (!$value) throw new RuntimeException($label); echo "PASS: $label\n"; }
function rejects(callable $action, int $status, string $label) {
    try { $action(); throw new RuntimeException($label); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { ok($e->getStatusCode()===$status,$label); }
    catch (Illuminate\Validation\ValidationException $e) { ok($status===422,$label); }
}
function req(array $data) { return Request::create('http://127.0.0.1/api/agent/contracts/approve','POST',[],[],[],['REMOTE_ADDR'=>'127.0.0.1','CONTENT_TYPE'=>'application/json','HTTP_X_REQUESTED_WITH'=>'XMLHttpRequest'],json_encode($data)); }
foreach (['contracts'=>'GET','contracts/propose'=>'POST','contracts/approve'=>'POST'] as $path=>$method) {
    $route=$app['router']->getRoutes()->match(Request::create('/api/agent/'.$path,$method));
    ok($route->getController() instanceof GolfAgentController,'Agent route resolves: '.$path);
}
// Only an in-memory database is used; no local or live business records are changed.
config(['database.connections.setup_mysql'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'app.key'=>'base64:'.base64_encode(random_bytes(32))]);
DB::purge('setup_mysql');
(require __DIR__.'/../database/migrations/2026_09_02_000001_create_setup_record_sets.php')->up();
$app->instance('env','testing');
$c=['id'=>'c1','name'=>'Test contract','firstDate'=>'2026-11-01','lastDate'=>'2026-11-22','validityFirstDate'=>'2026-11-01','validityLastDate'=>'2026-11-22','roomType'=>'STD','roomName'=>'Test room','allotment'=>'','guarantee'=>'','contractType'=>'','status'=>'PENDING','price'=>'100.00','currency'=>'GBP','market'=>'UK','submarket'=>'','board'=>'AI','calculationType'=>'','reviewRequired'=>true,'prices'=>[
    ['id'=>'p1','accommodationId'=>'','accommodation'=>'2 Adults','ageTable'=>'','pax'=>'2','infants'=>'0','children'=>'0','parity'=>'2','price'=>'200.00','currency'=>'GBP'],
    ['id'=>'p2','accommodationId'=>'','accommodation'=>'3 Adults','ageTable'=>'','pax'=>'3','infants'=>'0','children'=>'0','parity'=>'2.7','price'=>'225.00','currency'=>'GBP','manualPrice'=>true],
], 'conditions'=>[], 'rules'=>[]];
$records=[['id'=>1,'name'=>'Test hotel','details'=>['contractsStatus'=>'available','accountingStatus'=>'empty','contracts'=>[$c],'extras'=>[],'packages'=>[]]],['id'=>2,'name'=>'Other hotel','details'=>['contracts'=>[]]]];
$db=DB::connection('setup_mysql');
$db->table('setup_record_sets')->insert(['kind'=>'hotels','records'=>json_encode($records),'version'=>1,'created_at'=>now(),'updated_at'=>now()]);
$edits=[['rowId'=>'','field'=>'allotment','value'=>'20'],['rowId'=>'','field'=>'price','value'=>'110']];
$p=Changes::proposal($records,1,'local-preview','1','c1',$edits);
ok(count($p['changes'])===3,'Preview includes derived automatic price');
ok((int)$db->table('setup_record_sets')->value('version')===1,'Proposal does not write records');
rejects(fn()=>Changes::approve($p['token'],'other'),403,'Another user cannot approve');
rejects(fn()=>Changes::approve($p['token'].'invalid','local-preview'),422,'Modified token rejected');
$expired=Changes::approvedPayload($p['token'],'local-preview'); $expired['expires']=time()-1;
rejects(fn()=>Changes::approve(Crypt::encryptString(json_encode($expired)),'local-preview'),409,'Expired approval rejected');
rejects(fn()=>Changes::patch($records,'1','c1',[['rowId'=>'','field'=>'status','value'=>'ACTIVE']]),422,'Activation outside allowed fields');
rejects(fn()=>Changes::patch($records,'1','c1',[['rowId'=>'alien','field'=>'price','value'=>'100']]),422,'Other contract price row rejected');
rejects(fn()=>Changes::patch($records,'1','c1',[['rowId'=>'','field'=>'guarantee','value'=>'-1']]),422,'Negative room count rejected');
rejects(fn()=>Changes::patch($records,'1','c1',[['rowId'=>'','field'=>'lastDate','value'=>'2026-10-01']]),422,'Reversed dates rejected');
$controller=new GolfAgentController;
rejects(fn()=>$controller->approveContract(req(['token'=>$p['token']])),422,'Explicit confirmation required');
$result=$controller->approveContract(req(['token'=>$p['token'],'confirmed'=>true]))->getData(true);
$saved=json_decode($db->table('setup_record_sets')->value('records'),true);
ok($result['saved'] && $saved[0]['details']['contracts'][0]['prices'][0]['price']==='220.00','Confirmed action saved atomically');
ok($saved[0]['details']['contracts'][0]['prices'][1]['price']==='225.00' && $saved[1]===$records[1],'Manual prices and unrelated hotel preserved');
ok($db->table('setup_record_backups')->where('kind','hotels')->count()===1 && $db->table('setup_record_backups')->where('kind','agent-contract-audit')->count()===1,'Backup and actor audit saved');
rejects(fn()=>Changes::approve($p['token'],'local-preview'),409,'Replay or stale version rejected');
$direct=Changes::patch($records,'1','c1',[['rowId'=>'p1','field'=>'price','value'=>'240']]);
ok($direct[0][0]['details']['contracts'][0]['prices'][0]['manualPrice']===true && count($direct[1])===2,'Direct price change previews manual mode');
config(['services.openai.key'=>'test-only']); Http::preventStrayRequests();
Http::fake(['api.openai.com/*'=>Http::response(['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>json_encode(['explanation'=>'Kontenjan önerisi','changes'=>[['rowId'=>'','field'=>'allotment','value'=>'25']]])]]]]])]);
$model=$controller->proposeContract(req(['hotelId'=>'1','contractId'=>'c1','message'=>'Kontenjan 25 olsun']))->getData(true);
ok($model['proposal']['changes'][0]['after']==='25' && (int)$db->table('setup_record_sets')->value('version')===2,'Model creates a validated proposal without executing it');
ok(Http::recorded(fn($r)=>$r['store']===false && !isset($r['tools']) && $r['text']['format']['strict']===true)->count()===1,'Model never receives a write tool');
config(['services.openai.agent_enabled'=>false]);
rejects(fn()=>$controller->approveContract(req(['token'=>$model['proposal']['token'],'confirmed'=>true])),503,'Kill switch also blocks approvals');
config(['services.openai.agent_enabled'=>true]);
$batch=$records;
$batch[0]['details']['contracts']=[];
for ($i=1;$i<=72;$i++) {
    $copy=$c; $copy['id']='batch-'.$i; $copy['name']='Contract '.$i;
    foreach ($copy['prices'] as &$row) $row['id'].='-'.$i;
    unset($row); $batch[0]['details']['contracts'][]=$copy;
}
$ids=array_column($batch[0]['details']['contracts'],'id');
$change=[['rowId'=>'','field'=>'allotment','value'=>'25']];
$db->table('setup_record_sets')->where('kind','hotels')->update(['records'=>json_encode($batch),'version'=>3]);
$proposal=$controller->proposeContract(req(['hotelId'=>'1','contractIds'=>$ids,'message'=>'Hepsinin kontenjanini 25 yap']))->getData(true)['proposal'];
ok(count($proposal['changes'])===72 && (int)$db->table('setup_record_sets')->value('version')===3,'72-contract model proposal has no writes');
rejects(fn()=>Changes::batchPatch($batch,'1',[$ids[0],$ids[0]],$change),422,'Duplicate selection rejected');
rejects(fn()=>Changes::batchPatch($batch,'1',array_merge($ids,['alien']),$change),404,'Unselected hotel contract rejected');
rejects(fn()=>Changes::batchPatch($batch,'1',array_map(fn($i)=>'c'.$i,range(1,101)),$change),422,'Batch size limit enforced');
rejects(fn()=>Changes::batchPatch($batch,'1',$ids,[['rowId'=>'p1-1','field'=>'price','value'=>'25']]),422,'Shared row edits rejected');
$mixed=$batch; $mixed[0]['details']['contracts'][1]['currency']='EUR';
rejects(fn()=>Changes::batchPatch($mixed,'1',$ids,[['rowId'=>'','field'=>'price','value'=>'110']]),422,'Mixed-currency batch prices rejected');
$invalid=$batch; $invalid[0]['details']['contracts'][71]['firstDate']='2027-12-01';
$db->table('setup_record_sets')->where('kind','hotels')->update(['records'=>json_encode($invalid)]);
rejects(fn()=>Changes::approve($proposal['token'],'local-preview'),422,'Invalid final contract rolls back entire batch');
ok((int)$db->table('setup_record_sets')->value('version')===3 && json_decode($db->table('setup_record_sets')->value('records'),true)[0]['details']['contracts'][0]['allotment']==='','Failed batch writes nothing');
$db->table('setup_record_sets')->where('kind','hotels')->update(['records'=>json_encode($batch)]);
rejects(fn()=>Changes::approve($proposal['token'],'other'),403,'Batch owner enforced');
$controller->approveContract(req(['token'=>$proposal['token'],'confirmed'=>true]));
$saved=json_decode($db->table('setup_record_sets')->value('records'),true);
ok(count(array_filter($saved[0]['details']['contracts'],fn($c)=>$c['allotment']==='25'))===72 && $saved[1]===$batch[1],'72 approved contracts saved together; other hotel unchanged');
rejects(fn()=>Changes::approve($proposal['token'],'local-preview'),409,'Batch replay rejected');
$partial=$batch; $partial[0]['details']['contracts'][0]['allotment']='25';
ok(count(Changes::batchPatch($partial,'1',$ids,$change)[1])===71,'Already matching values omitted from diff');
$app->instance('env','production');
rejects(fn()=>$controller->approveContract(Request::create('https://example.com','POST')),403,'Anonymous writes rejected');
