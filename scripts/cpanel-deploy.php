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
$backup=$app.'/storage/deploy-backups/'.preg_replace('/[^a-zA-Z0-9_-]/','',$release['commit']);
$copy=function(string $source,string $target,int $mode=0600)use($app,$public,$backup):void{
 if(is_link($source)||is_link($target))throw new RuntimeException('Symlink not allowed.');
 if(is_file($target)){
  $relative=strpos($target,$public.'/')===0?'public/'.substr($target,strlen($public)+1):'app/'.substr($target,strlen($app)+1);
  $previous=$backup.'/'.$relative;
  if(!is_file($previous)){
   if(!is_dir(dirname($previous))&&!mkdir(dirname($previous),0700,true))throw new RuntimeException('Cannot create backup directory.');
   if(!copy($target,$previous)||!chmod($previous,0600))throw new RuntimeException('Cannot back up previous code.');
  }
 }
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
foreach(['app/Http/Controllers/SetupRecordsController.php','app/Http/Controllers/ContractDocumentController.php','app/Http/Controllers/AppReleaseController.php','app/Http/Controllers/GolfLoginController.php','config/services.php','routes/api.php'] as $relative)$copy($repo.'/'.$relative,$app.'/'.$relative);
// Clear generated config and routes without touching sessions or application data.
foreach(glob($app.'/bootstrap/cache/routes*.php') as $cached)if(!unlink($cached))throw new RuntimeException('Cannot clear route cache.');
if(is_file($app.'/bootstrap/cache/config.php')&&!unlink($app.'/bootstrap/cache/config.php'))throw new RuntimeException('Cannot clear configuration cache.');
$htaccess=$public.'/.htaccess';
$headers="\n# BEGIN GOLF RELEASE CACHE\n<IfModule mod_headers.c>\n<FilesMatch \"\\.(html|php|json)$\">\nHeader always set Cache-Control \"no-store, no-cache, must-revalidate, max-age=0\"\n</FilesMatch>\n</IfModule>\n# END GOLF RELEASE CACHE\n";
$existing=file_get_contents($htaccess);
if(strpos($existing,'# BEGIN GOLF RELEASE CACHE')===false){
 $copy($htaccess,$app.'/storage/deploy-backups/'.basename($backup).'/previous.htaccess');
 $temporary=$htaccess.'.golf-deploy-tmp';
 if(file_put_contents($temporary,$existing.$headers)===false||!chmod($temporary,0644)||!rename($temporary,$htaccess))throw new RuntimeException('Cannot update cache headers.');
}
$copy($repo.'/public/build/manifest.json',$public.'/build/manifest.json',0644);
$copy($repo.'/release.json',$public.'/build/release.json',0644);
$copy($repo.'/release.json',$marker);
echo 'Golf release deployed successfully.'.PHP_EOL;
