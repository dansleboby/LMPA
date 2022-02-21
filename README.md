# LMPA
Laragon MultiPHP per App

This tools allow you to run multiple PHP version per app with Laragon, so you can have multiple site running different php vesion.
You can also custumize the ini per app in .user.ini and through this
You can install PHP PECL module like YAML and Image Magic
You can enable and disable native module
The tools lookup the ini from orginal file and try to apply it, so it better to configure default before add other php version
You can custumize ini per installation

To use it simply put it in laragon => etc => apps => LMPA => index.phar, after simply call it via php index.phar inside the folder

## TODO
- More detailed readme
- Code review for less duplicate code
- Keep all DDL from pecl in a database to fully uninstall
- Try to look to restart the process of HTTP
- Try to set the PATH for PHP cli
- Global ini edition
