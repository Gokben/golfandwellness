<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void { fwrite(STDERR, $error->getMessage()."\n"); exit(1); });
use App\Support\AgentKnowledge;
use App\Http\Controllers\GolfAgentController;
use Illuminate\Http\Request;

function checkAgent(bool $condition, string $label): void { if (!$condition) throw new RuntimeException($label); echo "PASS: $label\n"; }
$rows = [
    ['id'=>'own','owner'=>'a','scope'=>'personal','status'=>'approved'],
    ['id'=>'other','owner'=>'b','scope'=>'personal','status'=>'approved'],
    ['id'=>'pending','owner'=>'b','scope'=>'shared','status'=>'pending'],
    ['id'=>'shared','owner'=>'b','scope'=>'shared','status'=>'approved'],
    ['id'=>'disabled','owner'=>'a','scope'=>'personal','status'=>'disabled'],
];
checkAgent(array_column(AgentKnowledge::visible($rows,'a',false),'id') === ['own','shared','disabled'], 'Users cannot list other private or pending examples');
checkAgent(!in_array('other',array_column(AgentKnowledge::visible($rows,'a',true),'id')), 'Admin cannot list another user private examples');
checkAgent(array_column(AgentKnowledge::context($rows,'a'),'id') === ['own','shared'], 'Only active authorized knowledge enters model context');
$controller = new GolfAgentController;
$app->instance('env','production');
try { $controller->index(Request::create('https://example.com/api/agent')); checkAgent(false,'anonymous blocked'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { checkAgent($e->getStatusCode()===403,'Anonymous production access blocked'); }
$app->instance('env','testing');
$originalStorage=$app->storagePath();
$testStorage=sys_get_temp_dir().'/golf-agent-test-'.bin2hex(random_bytes(6));
$app->useStoragePath($testStorage);
function agentRequest(array $data): Request {
    return Request::create('http://127.0.0.1/api/agent/knowledge','POST',[],[],[],['REMOTE_ADDR'=>'127.0.0.1','CONTENT_TYPE'=>'application/json','HTTP_X_REQUESTED_WITH'=>'XMLHttpRequest'],json_encode($data));
}
try {
    $controller->save(agentRequest(['title'=>'Example','content'=>'Correct answer','scope'=>'shared']));
    $row=AgentKnowledge::transaction(fn (&$state)=>$state['rows'][0]);
    checkAgent($row['status']==='pending','Shared example requires explicit approval');
    $controller->moderate(agentRequest(['action'=>'approve','version'=>1]),$row['id']);
    $row=AgentKnowledge::transaction(fn (&$state)=>$state['rows'][0]);
    checkAgent($row['status']==='approved' && $row['version']===2,'Approval stored and versioned');
    try { $controller->moderate(agentRequest(['action'=>'disable','version'=>1]),$row['id']); checkAgent(false,'stale update rejected'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { checkAgent($e->getStatusCode()===409,'Stale update rejected'); }
    $controller->save(agentRequest(['id'=>$row['id'],'version'=>2,'title'=>'Revised','content'=>'Revised example','scope'=>'shared']));
    $row=AgentKnowledge::transaction(fn (&$state)=>$state['rows'][0]);
    checkAgent($row['status']==='pending','Editing shared knowledge revokes previous approval');
    $controller->moderate(agentRequest(['action'=>'disable','version'=>3]),$row['id']);
    checkAgent(AgentKnowledge::transaction(fn (&$state)=>AgentKnowledge::context($state['rows'],'local-preview'))===[],'Disabled examples are not used');
    config(['services.openai.key'=>'test-only']);
    Illuminate\Support\Facades\Http::preventStrayRequests();
    $response = ['status'=>'completed', 'output'=>[['content'=>[['type'=>'output_text','text'=>'Test answer']]]]];
    Illuminate\Support\Facades\Http::fake(['api.openai.com/*'=>Illuminate\Support\Facades\Http::response($response)]);
    checkAgent($controller->chat(agentRequest(['message'=>'Otel listesi']))->getData(true)['answer']==='Test answer','Model response parsed');
    checkAgent(Illuminate\Support\Facades\Http::recorded(fn ($request)=>$request['store']===false && !isset($request['tools']))->count()===1,'Agent cannot invoke write tools and response storage is disabled');
    config(['services.openai.agent_enabled'=>false]);
    try { $controller->index(Request::create('http://127.0.0.1/api/agent','GET',[],[],[],['REMOTE_ADDR'=>'127.0.0.1'])); checkAgent(false,'kill switch'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { checkAgent($e->getStatusCode()===503,'Server kill switch blocks agent'); }
} finally {
    foreach (glob($testStorage.'/app/private/golf-agent/*') as $file) unlink($file);
    rmdir($testStorage.'/app/private/golf-agent'); rmdir($testStorage.'/app/private'); rmdir($testStorage.'/app'); rmdir($testStorage);
    $app->useStoragePath($originalStorage);
}
