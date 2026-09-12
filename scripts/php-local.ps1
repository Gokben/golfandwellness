$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$phpConfig = Join-Path $projectDirectory 'storage\releases\local-runtime\php.ini'
if (!(Test-Path -LiteralPath $phpConfig)) { throw 'Yerel PHP yapılandırması bulunamadı.' }
& php -c $phpConfig @args
exit $LASTEXITCODE
