Get-Childitem -Path "..\..\..\bin\php\php-*\php.exe" | ForEach-Object {
    $arg = '/c ' + $_.FullName + ' ' + $(Resolve-Path -Path ".") + '\index.phar & pause';
	Start-Process -FilePath cmd -ArgumentList $arg; break;
}