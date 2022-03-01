<?php

namespace Controller;

use Mervick\CurlHelper;

class AppsController {
	private $climate;

	public function __construct(\League\CLImate\CLImate $CLImate) {
		$this->climate = $CLImate;
		$this->climate->clear();

		$this->climate->out("!!! To add or remove an app please use Laragon GUI !!!");
		$this->climate->br();

		$this->climate->out("If the app is in <blue>blue</blue> it managed by Laragon and use default config such as php version");
		$this->climate->out("Choose an app:");

		$apps = array_filter(glob("../../../etc/apache2/sites-enabled/*.conf", GLOB_BRACE), function($v) { return basename($v) !== "00-default.conf";});
		$display = $apps;
		foreach($display as &$app) {
			$app = substr(basename($app), 0, -5);
			if(strpos($app, 'auto.') === 0) $app = "<blue>$app</blue>";
		}

		$chosen_app = $apps[menu($display)];

		$this->climate->clear();

		if(strpos(basename($chosen_app), 'auto.') === 0) {
			$this->climate->yellow("Attention this will convert this app to be used with LMAP, the change in laragon will no longer take place for the app");
			if(menu(['No', 'Yes']) === "0") exit;

			if(!rename($chosen_app,  dirname($chosen_app).'/'.substr(basename($chosen_app), 5))) {
				$this->climate->error("Can't rename file");
				exit;
			}

			$chosen_app = dirname($chosen_app).'/'.substr(basename($chosen_app), 5);
		}

		$this->climate->clear();
		$this->climate->out("All this settings are <red>PER</red> app, so the settings will not affect other app");
		//TODO: Change ROOT
		$actions = [
			"Change PHP version",
			"Change PHP ini settings",
			"Revert back to default"
		];

		$action = menu($actions);
		$conf_file = file_get_contents($chosen_app);
		$lmpa_present = strpos($conf_file, "# --- LMPA_START_") !== false;

		$parts = [];
		if($lmpa_present) {
			preg_match_all('/# --- LMPA_START_(?<type>DIRECTORY|FCGI|VARIABLE) --- #(?<lines>.*)# --- LMPA_END_(?:DIRECTORY|FCGI|VARIABLE) --- #/Us', $conf_file, $matches_config);
			$partsTMP = [];
			foreach($matches_config["lines"] as $k => $line) {
				$partsTMP[$matches_config["type"][$k]] = array_filter(array_map(function($v) {return explode(' ', trim($v), 2);}, explode("\n", $line)), function($v) { return !empty($v[0]);});
			}

			foreach($partsTMP as $type => $options) {
				foreach($options as $option) {
					if($type === "VARIABLE") $option = explode(" ", $option[1], 2);
					$parts[$type][$option[0]] = $option[1];
				}
			}
		}

		$this->climate->clear();

		if($action == '0') {
			$this->climate->out("Choose the version you want to set for the app, if version not present restart the helper and install the required version");
			$this->climate->br();

			$current_version = "Default";
			if($lmpa_present && isset($parts['VARIABLE']['LMPA_PHPENV'])) {
				preg_match("/(?:php-)?(?<version>[5-9].[0-9].[0-9]+).*/", $parts['VARIABLE']['LMPA_PHPENV'], $matches);
				$current_version = $matches["version"]??'Default';
			}

			$this->climate->out("Current version is: <bold>$current_version</bold>");
			$this->climate->br();

			$php_binary = glob("../../../bin/php/*-nts/php.exe");
			$versions   = array_map(function($path) { return "v." . exeparser_fileversion($path);}, $php_binary);

			$this->climate->out("PHP versions currently installed:");

			if(empty($versions)) {
				$this->climate->yellow("No other versions...");
				exit;
			}

			foreach($versions as $n => $version) {
				$this->climate->lightCyan()->out($n . ') ' . $version);
			}

			$choice = $this->climate->lightGreen()->input("Choose an option:")->accept(array_keys($versions))->prompt();

			$php_version = $versions[$choice];
			$this->climate->clear();

			//Check current php post_max_size
			$this->climate->out("Checking current post_max_size...");
			$parse_ini = PHPIniController::parse_ini('../../../bin/php/'.substr($php_version, 2).'-nts/php.ini');
			$post_max_size = to_bytes($parse_ini["post_max_size"]);


			$this->climate->out("Bulding config...");
			$parts["VARIABLE"]["LMPA_OPTIONS"] = "+ExecCGI";
			$parts["VARIABLE"]["LMPA_PHPENV"]  = realpath('../../../bin/php/' . substr($php_version, 2) . '-nts/') . DIRECTORY_SEPARATOR;
			$parts["VARIABLE"]["LMPA_PHPENV"]  = str_replace(DIRECTORY_SEPARATOR, "/", $parts["VARIABLE"]["LMPA_PHPENV"]);
			$parts["VARIABLE"]["LMPA_PHPMEM"]  = $post_max_size;

			$parts["DIRECTORY"] = [
				"Options" => '${LMPA_OPTIONS}'
			];
			$parts["FCGI"] = [
				'AddHandler' => 'fcgid-script .php',
				'FcgidInitialEnv' => 'PHPRC "${LMPA_PHPENV}"',
				'FcgidMaxRequestInMem' => '${LMPA_PHPMEM}',
				'FcgidMaxRequestLen' => '${LMPA_PHPMEM}',
				'FcgidWrapper' => '"${LMPA_PHPENV}php-cgi.exe" .php'
			];


			$parts_converted = [];
			foreach($parts as $type => $options) {
				$parts_converted[$type] = "# --- LMPA_START_$type --- #\n";
				foreach($options as $key => $value) {
					if($type === "VARIABLE")
						$parts_converted[$type] .= "define $key \"$value\"\n";
					else
						$parts_converted[$type] .= "\t$key $value\n";
				}
				$parts_converted[$type] .= ($type !== "VARIABLE" ? "\t": "")."# --- LMPA_END_$type --- #\n";
			}

			if($lmpa_present) {
				foreach($matches_config[0] as $k => $d) {
					$conf_file = str_replace($d, $parts_converted[$matches_config["type"][$k]], $conf_file);
				}
			} else {
				foreach(['VARIABLE' => '<VirtualHost *:80>', 'DIRECTORY' => '<Directory "${ROOT}">', 'FCGI' => '</VirtualHost>'] as $k => $d) {
					if($k === 'VARIABLE' || $k === "FCGI")
						$conf_file = str_replace($d, ($k !== "VARIABLE" ? "\t":"").$parts_converted[$k].PHP_EOL.$d, $conf_file);
					else
						$conf_file = str_replace($d, $d.PHP_EOL."\t".$parts_converted[$k], $conf_file);
				}
			}

			if(file_put_contents($chosen_app, $conf_file)) {
				$this->climate->green("Config write!");
				$this->climate->br();
				$this->climate->blink("Please reload/restart apache in Laragon!");
				$this->climate->br();
			} else {
				$this->climate->error("Error when writing config");
			}
		}

		if($action === '1') {
			if(!$lmpa_present || !isset($parts['VARIABLE']['LMPA_PHPENV'])) {
				$this->climate->error("Sorry you need to select a php version first for this app");
				exit;
			}

			$this->climate->out("Fetching parameters...");
			//https://www.php.net/manual/en/ini.list.php
			$curl = CurlHelper::factory("https://www.php.net/manual/en/ini.list.php")->setOptions([
								  CURLOPT_SSL_VERIFYHOST => false,
								  CURLOPT_SSL_VERIFYPEER => false
							  ])->setUserAgent("Laragon MultiPHP per App v.".LMPA_VERSION);
			$data = array_map(function($v) { return array_values(array_filter(array_map(function($v) { return trim($v," \t\n\r\0\x0B\xc2\xa0");}, explode("\n", $v))));}, $curl->xpath(['//*[@id="ini.list"]/table/tbody/tr'])->exec()['xpath'][0]);
			$params = [];

			foreach($data as $d) {
				if(count($d) === 2) {
					$d[2] = $d[1];
					$d[1] = "None";
				}
				if($d[2] === "PHP_INI_USER" || $d[2] === "PHP_INI_PERDIR") {
					$params[] = $d[0] . (isset($d[3]) ? " Changelog: <yellow>".$d[3]."</yellow>" : "");
				}
			}

			sort($params);

			$params["end"] = "end";


			$this->climate->out("Check for current .user.ini in the app");
			$userIni = "../../../www/".substr(basename($chosen_app), 0, -5)."/.user.ini";
			$hasIni = file_exists("../../../www/".substr(basename($chosen_app), 0, -5)."/.user.ini");

			if($hasIni) $ini = PHPIniController::parse_ini($userIni);
			else $ini = [];

			$parse_ini = PHPIniController::parse_ini(substr($parts['VARIABLE']['LMPA_PHPENV'], 1, -1).'/php.ini');

			$table = [["Php key", "Global value", "Current value in .user.ini", "New value"]];

			do {
				$this->climate->clear();

				$this->climate->out("What ini settings do you need to change?");
				$this->climate->out("This is all the settings allowed to be change in .user.ini");
				$choice = $params[menu($params)];
				if($choice !== "end") {
					$this->climate->clear();
					$newValue = $this->climate->lightGreen()
												  ->input("New value for $choice:")
												  ->prompt();
					$table[] = [$choice, $parse_ini[$choice]??"<red>No Value</red>", $ini[$choice]??"<red>No value</red>", $newValue];
					$ini[$choice] = $newValue;
				}
			} while($choice !== "end");

			$this->climate->clear();
			$this->climate->out("Please review the change");
			$this->climate->ctable($table);

			$this->climate->br();
			$this->climate->out("Do you want to write change to: ".realpath($userIni)."?");
			if(menu(["No", "Yes"]) == "0") {
				exit;
			}

			$userIniTxt = "";
			foreach($ini as $k => $v) {
				$userIniTxt .= ini_encodeing($k, $v);
			}

			file_put_contents($userIni, $userIniTxt);

		}

		if($action == "2") {
			if(!$lmpa_present) {
				$this->climate->error("Sorry you need to select a php version first for this app");
				exit;
			}

			$this->climate->out("Are your sure you want to restore to the defalut laragon php config?");
			if(menu(["No", "Yes"]) == "0") {
				exit;
			}

			$this->climate->out("Check for current .user.ini in the app");
			$userIni = "../../../www/".substr(basename($chosen_app), 0, -5)."/.user.ini";
			$hasIni = file_exists("../../../www/".substr(basename($chosen_app), 0, -5)."/.user.ini");
			if($hasIni) {
				unlink($userIni);
			}

			foreach($matches_config[0] as $k => $d) {
				$conf_file = str_replace($d, '', $conf_file);
			}

			if(file_put_contents($chosen_app, $conf_file)) {
				$this->climate->green("Config removed!");
				$this->climate->br();
				$this->climate->blink("Please reload/restart apache in Laragon!");
				$this->climate->br();
			} else {
				$this->climate->error("Error when writing config");
			}
		}
	}
}