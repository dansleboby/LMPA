<?php

namespace Controller;

class PHPModulesController {
	public function __construct(\League\CLImate\CLImate $CLImate) {
		$this->climate = $CLImate;
		$this->climate->clear();

		$action = menu([
						   "Enable modules",
						   "Disable modules"
					   ]);

		$this->climate->clear();

		if($action == "0" || $action == "1") {
			$php_binary = glob("../../../bin/php/*-nts/php.exe");
			$versions   = array_map(function($path) { return "v." . exeparser_fileversion($path);}, $php_binary);

			$this->climate->out("Choose witch PHP version you would like to install a module");
			$this->climate->out("PHP versions currently installed:");

			if(empty($versions)) {
				$this->climate->yellow("No versions...");
				exit;
			}

			foreach($versions as $n => $version) {
				$this->climate->lightCyan()->out($n . ') ' . $version);
			}

			$choice = $this->climate->lightGreen()->input("Choose an option:")->accept(array_keys($versions))->prompt();

			$php_version = $versions[$choice];
			$iniFile = '../../../bin/php/' . substr($php_version, 2) . '-nts/php.ini';
			$currentIni = PHPIniController::parse_ini($iniFile);
		}

		if($action == "0") {
			$extensions = array_map(function($v) { return substr(basename($v), 4, -4);}, glob('../../../bin/php/' . substr($php_version, 2) . '-nts/ext/*.dll'));
			$extensionsAvailible = array_values(array_diff($extensions, $currentIni['extension']));
			$choice = menu($extensionsAvailible);
			$myExtension = $extensionsAvailible[$choice];

			$currentIni['extension'][] = $myExtension;
			PHPIniController::write_ini($iniFile, $currentIni);

			$this->climate->lightGreen("Done!");

			$this->climate->br();
			$this->climate->blink("Please reload/restart apache in Laragon!");
			$this->climate->br();
		}

		if($action == "1") {
			$choice = menu($currentIni['extension']);
			$myExtension = $currentIni['extension'][$choice];
			$currentIni['extension'] = array_diff($currentIni['extension'], [$myExtension]);
			PHPIniController::write_ini($iniFile, $currentIni);
			$this->climate->lightGreen("Done!");
			$this->climate->br();
			$this->climate->blink("Please reload/restart apache in Laragon!");
			$this->climate->br();
		}
	}
}