$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$mysqlBinary = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe'
$mysqlBase = 'C:/laragon/bin/mysql/mysql-8.4.3-winx64'
$phpBinary = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
$mysqlData = (Join-Path $projectDirectory 'storage/mysql-setup').Replace('\', '/')
if (!(Test-Path -LiteralPath (Join-Path $mysqlData 'auto.cnf'))) { throw 'Yerel MySQL veri klasörü bulunamadı. Yeniden başlatmak yerine kurulumu kontrol edin.' }
if (!(Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $mysqlBinary -ArgumentList '--no-defaults', "--basedir=$mysqlBase", "--datadir=$mysqlData", '--port=3307', '--bind-address=127.0.0.1', '--mysqlx=OFF', "--log-error=$mysqlData/server.err" -WindowStyle Hidden
}
if (!(Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $phpBinary -ArgumentList 'artisan', 'serve', '--host=127.0.0.1', '--port=8001' -WorkingDirectory $projectDirectory -WindowStyle Hidden
}
if (!(Get-NetTCPConnection -LocalPort 8093 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath npm.cmd -ArgumentList 'run', 'dev', '--', '--host', '127.0.0.1', '--port', '8093', '--strictPort' -WorkingDirectory $projectDirectory -WindowStyle Hidden
}
Write-Output 'Yerel uygulama: http://127.0.0.1:8093/desktop.html (MySQL: 127.0.0.1:3307)'
