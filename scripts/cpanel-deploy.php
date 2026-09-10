<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$repo=dirname(__DIR__);
$app='/home/krpsoftc/golf_release_20260902';
$public='/home/krpsoftc/public_html/golf';
if(realpath($repo)!=='/home/krpsoftc/repositories/golfandwellness'||!is_file($app.'/artisan')||!is_file($public.'/index.php'))throw new RuntimeException('Unexpected deployment target.');
$lock=fopen($app.'/storage/golf-deployment.lock','c');
if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit(0);
$release=json_decode(file_get_contents($repo.'/release.json'),true,512,JSON_THROW_ON_ERROR);
$marker=$app.'/storage/golf-deployed-release.json';
if(is_file($marker)&&json_decode(file_get_contents($marker),true)['commit']===$release['commit'])exit(0);
$manifest=json_decode(file_get_contents($repo.'/public/build/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$assets=[];
foreach($manifest as $entry){$assets[]=$entry['file'];foreach($entry['css']??[] as $css)$assets[]=$css;}
foreach($assets as $asset)if(strpos($asset,'..')!==false||!is_file($repo.'/public/build/'.$asset))throw new RuntimeException('Incomplete build.');
$copy=function(string $source,string $target,int $mode=0600):void{
 if(is_link($source)||is_link($target))throw new RuntimeException('Symlink not allowed.');
 if(!is_dir(dirname($target))&&!mkdir(dirname($target),0755,true))throw new RuntimeException('Cannot create directory.');
 $temp=$target.'.golf-deploy-tmp';
 if(!copy($source,$temp)||!chmod($temp,$mode)||!rename($temp,$target))throw new RuntimeException('File deployment failed.');
};
// Publish hashed assets first, then application code, and manifest last.
foreach(array_unique($assets) as $asset)$copy($repo.'/public/build/'.$asset,$public.'/build/'.$asset,0644);
foreach(['app/Support','resources/js','resources/css'] as $directory){
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repo.'/'.$directory,FilesystemIterator::SKIP_DOTS)) as $file){
  if(!$file->isFile())continue;
  $relative=substr($file->getPathname(),strlen($repo)+1);
  $copy($file->getPathname(),$app.'/'.$relative);
 }
}
$copy($repo.'/app/Http/Controllers/SetupRecordsController.php',$app.'/app/Http/Controllers/SetupRecordsController.php');
$copy($repo.'/public/build/manifest.json',$public.'/build/manifest.json',0644);
$copy($repo.'/release.json',$public.'/build/release.json',0644);
$copy($repo.'/release.json',$marker);
echo 'Golf release deployed successfully.'.PHP_EOL;
