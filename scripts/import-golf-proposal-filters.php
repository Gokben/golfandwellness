<?php
// Scoped source import, without backups as requested. Existing details are retained.
if (PHP_SAPI !== 'cli') exit(1);
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('local')) throw new RuntimeException('This source import is for the local workspace only.');
$source=json_decode(file_get_contents(__DIR__.'/../storage/app/proposal-golf-source.json'),true,512,JSON_THROW_ON_ERROR);
$db=Illuminate\Support\Facades\DB::connection('setup_mysql');
$db->transaction(function()use($db,$source){
 $row=$db->table('setup_record_sets')->where('kind','proposals-golf')->lockForUpdate()->first();
 if(!$row)throw new RuntimeException('Existing golf proposal set required.');
 $records=json_decode($row->records,true,512,JSON_THROW_ON_ERROR);
 $added=0;
 foreach($source as $incoming){
  $index=array_search($incoming['id'],array_column($records,'id'),true);
  if($index===false){$records[]=$incoming;$added++;}
  elseif($incoming['sourceId']==='35'){$records[$index]['status']=$incoming['status'];}
 }
 $records=App\Support\Proposals::validate('golf',$records);
 $db->table('setup_record_sets')->where('kind','proposals-golf')->update(['records'=>json_encode($records,JSON_UNESCAPED_UNICODE),'version'=>$row->version+1,'updated_at'=>now()]);
 echo json_encode(['added'=>$added,'total'=>count($records),'cancelled'=>count(array_filter($records,fn($r)=>$r['status']==='CANCELLED'))]);
});
