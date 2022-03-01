Get-Childitem -Path "C:\laragon\bin\php\php-*\php.exe" | ForEach-Object {
    $arg = '/c ' + $_.FullName + ' C:\laragon\etc\apps\LMPA\index.phar & pause';
	Start-Process -FilePath cmd -ArgumentList $arg; break;
}