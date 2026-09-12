$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$phpBinary = (Get-Command php -ErrorAction Stop).Source
$phpConfig = Join-Path $projectDirectory 'storage/releases/local-runtime/php.ini'
$databaseListener = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() | Where-Object Port -eq 3306
if (!$databaseListener) { throw 'XAMPP veritabanını önce başlatın (127.0.0.1:3306).' }
if (!(Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $phpBinary -ArgumentList '-c', "`"$phpConfig`"", 'artisan', 'serve', '--host=127.0.0.1', '--port=8001' -WorkingDirectory $projectDirectory -WindowStyle Hidden
}
if (!(Get-NetTCPConnection -LocalPort 8093 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath npm.cmd -ArgumentList 'run', 'dev', '--', '--host', '127.0.0.1', '--port', '8093', '--strictPort' -WorkingDirectory $projectDirectory -WindowStyle Hidden
}
Write-Output 'Yerel uygulama: http://127.0.0.1:8093/desktop.html (Veritabanı: 127.0.0.1:3306 / krpsoftc_golf)'
