<?php

namespace Controller;

class PHPVersionsController {
	private $climate;

	public function __construct(\League\CLImate\CLImate $CLImate) {
		$this->climate = $CLImate;

		$this->climate->clear();
		$main = menu([
						 'Remove a php version',
						 'Add a php version'
					 ]);
		if($main === "0") {
			$this->remove();
		}

		if($main === "1") {
			$this->add();
		}
	}

	public function choosePHPVersion() {
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

		return $versions[$choice];
	}

	private function remove() {
		$this->climate->clear();
		//Find all current php version
		$apache2_mod_php = file_get_contents("../../../etc/apache2/mod_php.conf");
		preg_match('#LoadModule php[5-9]?_module "(.*)"#Ums', $apache2_mod_php, $matches);
		$php_default_binary = dirname($matches[1]).DIRECTORY_SEPARATOR."php.exe";

		$this->climate->lightYellow("Default php version: v". exeparser_fileversion($php_default_binary));
		$this->climate->br();

		$choice = $this->choosePHPVersion();

		//TODO: Check in vhosts for current usage
		$directory = realpath("../../../bin/php/".substr($choice, 2)."-nts/");
		removeDirectory($directory);
		if(file_exists($directory)) {
			$this->climate->error("Cannot delete the php versions");
			exit;
		} else {
			$this->climate->lightGreen("Base binary removed");
		}

		//Remove alias
		$_aliases = '..\..\..\bin\php\_aliases';
		$file = $_aliases.'\php'.implode('', array_slice(explode('.', substr($choice, 2)), 0, 2)).'.bat';
		if(!unlink($file)) {
			$this->climate->error("Can't remove alias, $file");
			exit;
		} else {
			$this->climate->lightGreen("Alias removed!");
		}

		$file = $_aliases.'\composer'.implode('', array_slice(explode('.', substr($choice, 2)), 0, 2)).'.bat';
		if(!unlink($file)) {
			$this->climate->error("Can't remove alias, $file");
			exit;
		} else {
			$this->climate->lightGreen("Alias removed!");
		}

		$this->climate->info("PHP " . $choice . " has been deleted");
	}

	private function add() {
		$this->climate->clear();
		$this->climate->out("Please wait, fetching from: windows.php.net");

		$curl = new \Mervick\CurlHelper("https://windows.php.net/downloads/releases/releases.json");
		$curl->setOptions([
							CURLOPT_SSL_VERIFYHOST => false,
							CURLOPT_SSL_VERIFYPEER => false
						  ]);
		$curl->setUserAgent("Laragon MultiPHP per App".LMPA_VERSION);

		$content = $curl->exec();

		if($content["status"] < 200 || $content["status"] >= 300) {
			$this->climate->error("Failed to get windows releases");
			die();
		}
		$last_releases = $content["data"];

		$packages = [];

		$this->climate->clear();

		//Find all current php version
		$apache2_mod_php = file_get_contents("../../../etc/apache2/mod_php.conf");
		preg_match('#LoadModule php[5-9]?_module "(.*)"#Ums', $apache2_mod_php, $matches);
		$php_default_binary = dirname($matches[1]).DIRECTORY_SEPARATOR."php.exe";
		$sourceIni = dirname($matches[1]).DIRECTORY_SEPARATOR."php.ini";

		$this->climate->lightYellow("Default php version: v". exeparser_fileversion($php_default_binary));

		$php_binary = glob("../../../bin/php/*-nts/php.exe");
		$versions   = array_map(function($path) { return "v." . exeparser_fileversion($path);}, $php_binary);

		$this->climate->out("PHP versions currently installed:");
		foreach($versions as $n => $version) {
			$this->climate->lightCyan()->out($version);
		}

		$this->climate->out("\nAvailable active version:");
		foreach($last_releases as $version => $release) {
			$package = array_values(preg_grep('/^nts-.*-x64/', array_keys($release)))[0];
			$packages[] = $release[$package]["zip"];
			$this->climate->lightGreen()->out(count($packages)-1 . ") v".$release["version"]);
		}

		$this->climate->magenta()->out("\nDeprecated php version:");

		//TODO: Dynamic list
		$lists = [
			"5.5.38" => ["path" => "archives/php-5.5.38-nts-Win32-VC11-x64.zip", "sha256" => "ABDF2FEFCD7D1DAF75D689E309E8571879B15D4B61726546E4E064F96167387A"],
			"5.6.40" => ["path" => "archives/php-5.6.40-nts-Win32-VC11-x64.zip", "sha256" => "3D7668280FA4B16F70705539BA1E4EA17EEF344C81E82881CBECA26FB7F181F1"],
			"7.0.33" => ["path" => "archives/php-7.0.33-nts-Win32-VC14-x64.zip", "sha256" => "BBA39BDE5B0BD50EFADC11E2716C7528669945F9D1397D707486E401E67E89FB"],
			"7.1.33" => ["path" => "archives/php-7.1.33-nts-Win32-VC14-x64.zip", "sha256" => "071438BE5BBAEE8B34894DDBA7852B6040991A3C853AB8141EFCB1B6655BBBEF"],
			"7.2.34" => ["path" => "archives/php-7.2.34-nts-Win32-VC15-x64.zip", "sha256" => "3C673EAB656E26FD6BC3AD27FE71169AD888B04E21D63D3C6B3151D5ED216563"],
			"7.3.33" => ["path" => "archives/php-7.3.33-nts-Win32-VC15-x64.zip", "sha256" => "5EAF3CAD80E678623F222A42C99BCEFCC60EEA359D407FB51E805AFDB3B13E5E"]
		];

		foreach($lists as $version => $path) {
			$packages[] = $path;
			$this->climate->magenta()->out(count($packages)-1 . ") v".$version);
		}

		$packages["other"] = "Unlisted version";
		$this->climate->br();
		$this->climate->red("other) Unlisted version");

		$choice = $this->climate->lightGreen()->input("Choose an option:")->accept(array_keys($packages))->prompt();

		$this->climate->clear();

		if($choice == "other") {
			$this->climate->out("Please wait, we are fetching archives release...");
			//All others versions
			$curl = new \Mervick\CurlHelper("https://windows.php.net/downloads/releases/archives/");
			$curl->setOptions([
								  CURLOPT_SSL_VERIFYHOST => false,
								  CURLOPT_SSL_VERIFYPEER => false
							  ]);
			$curl->setUserAgent("Laragon MultiPHP per App v.".LMPA_VERSION);
			$content = $curl->exec();

			if($content["status"] < 200 || $content["status"] >= 300) {
				$this->climate->error("Failed to get windows releases");
				die();
			}

			preg_match_all('/<a href="(?<url>\/downloads\/releases\/archives\/php-(?<version>[0-9.]+)-nts.*)">/Ui', $content['content'], $archives);

			$archives_versions = [];
			foreach($archives['url'] as $k => $archive) {
				[$major, $minor, $patch] = array_map('intval', explode('.', $archives['version'][$k]));
				$archives_versions[$major][$minor][$patch] = $archive;
			}

			$this->climate->out("Choose Major version:");
			ksort($archives_versions);
			$major = menu(array_combine(array_keys($archives_versions), array_map(function($v) {return "PHP <red>$v</red>.XX.XX";}, array_keys($archives_versions))));
			$this->climate->clear();


			$this->climate->out("Choose Minor version:");
			ksort($archives_versions[$major]);
			$minor = menu(array_combine(array_keys($archives_versions[$major]), array_map(function($v) use ($major) { return "PHP $major.<green>$v</green>.XX";} , array_keys($archives_versions[$major]))));
			$this->climate->clear();


			$this->climate->out("Choose Patch version:");
			ksort($archives_versions[$major][$minor]);
			$patch = menu(array_combine(array_keys($archives_versions[$major][$minor]), array_map(function($v) use ($major, $minor) { return "PHP $major.$minor.<blue>$v</blue>"; }, array_keys($archives_versions[$major][$minor]))));

			$packages["other"] = ['path' => substr($archives_versions[$major][$minor][$patch], 20) ];
		}

		$this->climate->info("Start download\n");
		$full_url = "https://windows.php.net/downloads/releases/" . $packages[$choice]["path"];
		$curl = new \Mervick\CurlHelper($full_url);
		$curl->setOptions([
							  CURLOPT_PROGRESSFUNCTION => 'curl_progress_bar',
							  CURLOPT_SSL_VERIFYHOST => false,
							  CURLOPT_SSL_VERIFYPEER => false,
							  CURLOPT_NOPROGRESS => 0
						  ]);
		$curl->setUserAgent("Laragon MultiPHP per App v.".LMPA_VERSION);
		$file = $curl->setTimeout(0)->exec();

		if($choice === "other") {
			$this->climate->yellow("Cannot check the shasum of your version, it not availible");
			sleep(3);
		} else {
			$this->climate->info("Checking download...");

			$this->climate->info(hash("sha256", $file['content']));
			$this->climate->info($packages[$choice]['sha256']);

			if(hash("sha256", $file['content']) === strtolower($packages[$choice]['sha256'])) {
				$this->climate->lightGreen("Download is OK");
			} else {
				$this->climate->error("Download have fail");
				exit;
			}
		}

		$this->climate->info("Put file in temp...");
		file_put_contents(basename($packages[$choice]['path']), $file['content']);

		//Make sure folder not exists
		$matches = [];
		preg_match("#php-([5-9].[0-9].[0-9]+)+.*#", basename($packages[$choice]['path']), $matches);
		$target_dir = "../../../bin/php/".$matches[1]."-nts";
		$this->climate->info("Check if $target_dir exists...");
		if(file_exists($target_dir)) {
			$this->climate->error("Error $target_dir is present\n");
			exit;
		}

		$this->climate->info("Extraction of download file");
		$zipArchive = new \ZipArchive();
		$result = $zipArchive->open(basename($packages[$choice]['path']), \ZipArchive::RDONLY);
		if ($result === TRUE) {
			$zipArchive ->extractTo($target_dir);
			$zipArchive ->close();

			$this->climate->lightGreen("File extracted");

			$this->climate->info("Delete temp file");
			unlink(basename($packages[$choice]['path']));
		} else {
			$this->climate->error("Error when extarting the archive");
			exit;
		}

		//Copy ini
		$this->climate->info("Copy ini file ".realpath($target_dir)."/php.ini-production to ".realpath($target_dir)."/php.ini");
		copy($target_dir."/php.ini-production", $target_dir."/php.ini");

		$this->climate->info("Add batch alias");
		$_aliases = '..\..\..\bin\php\_aliases';
		file_put_contents($_aliases.'\php'.implode('', array_slice(explode('.', $matches[1]), 0, 2)).'.bat', "@echo off\n".realpath($target_dir)."\php.exe %*");
		file_put_contents($_aliases."\composer".implode('', array_slice(explode('.', $matches[1]), 0, 2)).'.bat', str_replace(' %*', ' '.realpath($_aliases).'composer.phar %*', "@echo off\n".realpath($target_dir)."\php.exe %*"));

		$this->climate->clear();

		(new PHPIniController($this->climate))->merge($sourceIni,$target_dir."/php.ini");
	}
}