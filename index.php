<?php
const LMPA_VERSION = '0.6.1';
define("APP_DIRECTORY", dirname(__FILE__));

if(PHP_OS !== "WINNT") exit("Only work on windows");

ini_set('max_execution_time', '-1');
require_once('vendor/autoload.php');

$bugsnag = Bugsnag\Client::make('279c41fe0baa7abbf7d0014d9f369262');
Bugsnag\Handler::register($bugsnag);

$climate = new League\CLImate\CLImate;
$climate->extend(['ctable' => \Lib\CompactTable::class]);
$progress = $climate->progress(100);

$updateAvailable = check_latest_version();

while(true) {
	$climate->clear();

	$climate->out("
<red>██╗     </red><green>███╗   ███╗</green><yellow>██████╗ </yellow><blue>█████╗  </blue>
<red>██║     </red><green>████╗ ████║</green><yellow>██╔══██╗</yellow><blue>██╔══██╗</blue>
<red>██║     </red><green>██╔████╔██║</green><yellow>██████╔╝</yellow><blue>███████║</blue>
<red>██║     </red><green>██║╚██╔╝██║</green><yellow>██╔═══╝ </yellow><blue>██╔══██║</blue>
<red>███████╗</red><green>██║ ╚═╝ ██║</green><yellow>██║     </yellow><blue>██║  ██║</blue>
<red>╚══════╝</red><green>╚═╝     ╚═╝</green><yellow>╚═╝     </yellow><blue>╚═╝  ╚═╝</blue>

<red>Laragon</red> <green>Multi</green><yellow>PHP</yellow> per <blue>App</blue> V: ". LMPA_VERSION);

	if ($updateAvailable) {
		$climate->yellow()->border('═', 58);
		$climate->yellow("  Update available: " . LMPA_VERSION . " → " . $updateAvailable);
		$climate->yellow("  https://github.com/dansleboby/LMPA/releases/latest");
		$climate->yellow()->border('═', 58);
	}

	$climate->border();

	$main = menu([
			 'Status',
			 'Manage PHP versions',
			 'Manage PHP extensions (imagick, yaml, Xdebug, redis, APCu, memcached, mongodb etc...)',
			 'Manage PHP modules (curl, exif, gettext, intl, gmp, mysqli, pdo, ftp etc...)',
			 'Manage vhosts',
			 'Settings',
			 'Initial Setup',
			 'Exit'
		 ]);

	if($main === "7") break;

	if($main === "0")
		new \Controller\StatusController($climate);
	if($main === "1")
		new \Controller\PHPVersionsController($climate);
	if($main === "2")
		new \Controller\PHPExtensionsController($climate);
	if($main === "3")
		new \Controller\PHPModulesController($climate);
	if($main === "4")
		new \Controller\AppsController($climate);
	if($main === "5")
		new \Controller\SettingsController($climate);
	if($main === "6")
		new \Controller\SetupController($climate);

}
