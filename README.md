# LMPA
Laragon MultiPHP per App

This tools allow you to run multiple PHP version per app with Laragon, so you can have multiple site running different php vesion.
You can also custumize the ini per app in .user.ini and through this
You can install PHP PECL module like YAML and Image Magic
You can enable and disable native module
The tools lookup the ini from orginal file and try to apply it, so it better to configure default before add other php version
You can custumize ini per installation

## Installation

> Run Laragon at lease one time before running LMPA to let it set it default config :) You could also enable SSL in apache > SSL > enabled, since it required by LMPA

> Extract the release into: C:\laragon\etc\apps\LMPA then double click on LMPA shortcut to run it

> Don't forget to run setup when you run it for the first time

## TODO
- More detailed readme
- Code review for less duplicate code
- Keep all DDL from pecl in a database to fully uninstall
- Try to look to restart the process of HTTP
- Try to set the PATH for PHP cli
- Global ini edition
