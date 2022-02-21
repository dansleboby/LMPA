<?php
const LMPA_VERSION = '0.0.0';

ini_set('max_execution_time', '-1');
require_once('vendor/autoload.php');

$climate = new League\CLImate\CLImate;
$climate->extend(['ctable' => \Lib\CompactTable::class]);
$progress = $climate->progress(100);

$climate->clear();

$climate->addArt('art');

$climate->animation('logo')->enterFrom('top');
$climate->border();

$main = menu([
		 'Manage PHP versions',
		 'Manage PHP PECL modules (imagick, yaml, Xdebug, redis, APCu, memcached, mongodb etc...)',
		 'Manage PHP modules (curl, exif, gettext, intl, gmp, mysqli, pdo, ftp etc...)',
		 'Manage vhosts'
	 ]);

if($main === "0")
	new \Controller\PHPVersionsController($climate);
if($main === "1")
	new \Controller\PHPPECLModulesController($climate);
if($main === "2")
	new \Controller\PHPModulesController($climate);
if($main === "3")
	new \Controller\AppsController($climate);
