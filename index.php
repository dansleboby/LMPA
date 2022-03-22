<?php
const LMPA_VERSION = '0.3.3';
define("APP_DIRECTORY", dirname(__FILE__));

if(PHP_OS !== "WINNT") exit("Only work on windows");

ini_set('max_execution_time', '-1');
require_once('vendor/autoload.php');
$bugsnag = Bugsnag\Client::make('279c41fe0baa7abbf7d0014d9f369262');
Bugsnag\Handler::register($bugsnag);

$climate = new League\CLImate\CLImate;
$climate->extend(['ctable' => \Lib\CompactTable::class]);
$progress = $climate->progress(100);

$climate->clear();

$climate->out("
<red>██╗     </red><green>███╗   ███╗</green><yellow>██████╗ </yellow><blue>█████╗  </blue>
<red>██║     </red><green>████╗ ████║</green><yellow>██╔══██╗</yellow><blue>██╔══██╗</blue>
<red>██║     </red><green>██╔████╔██║</green><yellow>██████╔╝</yellow><blue>███████║</blue>
<red>██║     </red><green>██║╚██╔╝██║</green><yellow>██╔═══╝ </yellow><blue>██╔══██║</blue>
<red>███████╗</red><green>██║ ╚═╝ ██║</green><yellow>██║     </yellow><blue>██║  ██║</blue>
<red>╚══════╝</red><green>╚═╝     ╚═╝</green><yellow>╚═╝     </yellow><blue>╚═╝  ╚═╝</blue>

<red>Laragon</red> <green>Multi</green><yellow>PHP</yellow> per <blue>App</blue> V: ". LMPA_VERSION);

$climate->border();

$main = menu([
		 'Manage PHP versions',
		 'Manage PHP PECL modules (imagick, yaml, Xdebug, redis, APCu, memcached, mongodb etc...)',
		 'Manage PHP modules (curl, exif, gettext, intl, gmp, mysqli, pdo, ftp etc...)',
		 'Manage vhosts',
		 'Initial Setup'
	 ]);

if($main === "0")
	new \Controller\PHPVersionsController($climate);
if($main === "1")
	new \Controller\PHPPECLModulesController($climate);
if($main === "2")
	new \Controller\PHPModulesController($climate);
if($main === "3")
	new \Controller\AppsController($climate);
if($main === "4")
	new \Controller\SetupController($climate);
