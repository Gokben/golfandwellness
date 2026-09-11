import {cpSync,mkdirSync,writeFileSync,readFileSync,existsSync} from 'node:fs';
import {nextReleaseVersion} from './release-version.mjs';
import {resolve} from 'node:path';
const target=resolve(process.argv[2]||'storage/releases/github-release');
mkdirSync(target,{recursive:true});
writeFileSync(resolve(target,'.gitignore'),'error_log\n');
for(const path of ['app/Support','app/Http/Controllers/SetupRecordsController.php','app/Http/Controllers/ContractDocumentController.php','app/Http/Controllers/AppReleaseController.php','app/Http/Controllers/GolfLoginController.php','config/services.php','routes/api.php','resources/js','resources/css','public/build']){
 mkdirSync(resolve(target,path,'..'),{recursive:true});
 cpSync(path,resolve(target,path),{recursive:true});
}
mkdirSync(resolve(target,'scripts'),{recursive:true});
cpSync('scripts/cpanel-deploy.php',resolve(target,'scripts/cpanel-deploy.php'));
writeFileSync(resolve(target,'.cpanel.yml'),'---\ndeployment:\n  tasks:\n    - /usr/local/bin/php /home/krpsoftc/repositories/golfandwellness/scripts/cpanel-deploy.php\n');
const releasePath = resolve(target, 'release.json');
const previous = existsSync(releasePath) ? JSON.parse(readFileSync(releasePath, 'utf8')) : null;
const commit = process.env.GITHUB_SHA || 'local';
writeFileSync(releasePath,JSON.stringify({commit,version:nextReleaseVersion(previous,commit),builtAt:new Date().toISOString()})+'\n');
