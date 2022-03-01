Get-Childitem -Path "C:\laragon\bin\php\php-*\php.exe" | ForEach-Object {
	Start-Process -FilePath $_.FullName -ArgumentList 'C:\laragon\etc\apps\LMPA\index.php'; break;
}