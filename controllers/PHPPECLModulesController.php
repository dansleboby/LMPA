<?php

namespace Controller;

use Lib\PECL;

class PHPPECLModulesController {
	private \League\CLImate\CLImate $climate;

	public function __construct(\League\CLImate\CLImate $CLImate) {
		$this->climate = $CLImate;
		$this->climate->clear();

		$action = menu([
						   "Build PECL database",
						   "Install PECL modules",
						   "Remove PECL modules"
					   ]);

		$this->climate->clear();

		if($action == "0") {
			(new \Lib\PECL())->buildDatabase();
		}

		if($action == "1" || $action == "2") {
			$pecl = PECL::getDatabase();
			if(empty($pecl)) {
				$this->climate->error("Database is empty, please build database");
				exit;
			}

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

			$possiblePackages = [];
			$php_version_formated = explode(".", $php_version);
			$php_version_formated = $php_version_formated[1] . '.' . $php_version_formated[2];
			foreach($pecl['packages'] as $package => $versions) {
				foreach($versions as $version => $zips) {
					foreach($zips as $zip) {
						preg_match('/php_.*-.*-(.*)-nts.*-x64/', $zip, $matches);
						if(!empty($matches) && $matches[1] === $php_version_formated) {
							$possiblePackages[$package][$version] = $zip;
						}
					}
				}
				if(isset($possiblePackages[$package])) {
					natsort($possiblePackages[$package]);
					$possiblePackages[$package] = array_reverse($possiblePackages[$package]);
				}
			}

			$iniFile = '../../../bin/php/' . substr($php_version, 2) . '-nts/php.ini';
		}

		if($action == "1") {
			$this->climate->out("Witch module do you want to install?");
			$menu = array_keys($possiblePackages);
			asort($menu);
			$choice = menu($menu);
			$myPackage = $menu[$choice];

			$this->climate->clear();

			$this->climate->out("Witch version of $myPackage?");
			$menu = array_keys($possiblePackages[$myPackage]);
			$choice = menu($menu, 0);
			$myVersion = $menu[$choice];

			$this->climate->clear();

			$this->climate->info("Start download\n");
			$full_url = "https://windows.php.net/" . $possiblePackages[$myPackage][$myVersion];
			$curl = new \Mervick\CurlHelper($full_url);
			$curl->setOptions([
								  CURLOPT_PROGRESSFUNCTION => 'curl_progress_bar',
								  CURLOPT_SSL_VERIFYHOST => false,
								  CURLOPT_SSL_VERIFYPEER => false,
								  CURLOPT_NOPROGRESS => 0
							  ]);
			$curl->setUserAgent("Laragon MultiPHP per App v.".LMPA_VERSION);
			$file = $curl->setTimeout(0)->exec();

			$this->climate->info("Put file in temp...");
			file_put_contents(basename($possiblePackages[$myPackage][$myVersion]), $file['content']);

			$this->climate->info("Extraction of download file");
			$zipArchive = new \ZipArchive();
			$result = $zipArchive->open(basename($possiblePackages[$myPackage][$myVersion]));
			if ($result === TRUE) {
				$phpFolder = '../../../bin/php/' . substr($php_version, 2) . '-nts/';
				for ($i = 0; $i < $zipArchive->numFiles; $i++) {
					$filename = $zipArchive->getNameIndex($i);

					//Fix DDL not match the name
					$packageFilename = $myPackage;
					if($packageFilename === "pecl_http") $packageFilename = "http";

					if($filename === "php_".$packageFilename.".dll") {
						file_put_contents($phpFolder.'ext/'."php_".$packageFilename.".dll", $zipArchive->getFromIndex($i));
					}

					if(basename($filename) === $filename && pathinfo($filename, PATHINFO_EXTENSION) === "dll") {
						$this->climate->out("Extract: $filename");
						file_put_contents($phpFolder.$filename, $zipArchive->getFromIndex($i));
					}
				}

				$zipArchive->close();
				$this->climate->lightGreen("File extracted");
				$this->climate->info("Delete temp file");
				unlink(basename($possiblePackages[$myPackage][$myVersion]));
			} else {
				$this->climate->error("Error when extarting the archive");
				exit;
			}

			$this->climate->out("Add extensions to ini");
			file_put_contents($iniFile, file_get_contents($iniFile).PHP_EOL."extension=$packageFilename");

			$this->climate->lightGreen()->blink("Done!");
		}

		if($action == "2") {
			$this->climate->clear();

			$currentIni = PHPIniController::parse_ini($iniFile);
			$currrentPECL = array_values(array_intersect($currentIni["extension"], array_keys($possiblePackages)));

			$this->climate->out("Witch module do you want to remove?");
			$menu = $currrentPECL;
			asort($menu);
			$choice = menu($menu);
			$myPackage = $menu[$choice];

			$this->climate->out("Remove extensions from ini");
			file_put_contents($iniFile, str_replace("extension=$myPackage", '', file_get_contents($iniFile)));

			$this->climate->out("Remove DLL");
			if(!unlink('../../../bin/php/' . substr($php_version, 2) . '-nts/ext/'."php_".$myPackage.".dll")) {
				$this->climate->red("Can't remove DLL");
				exit;
			}

			$this->climate->lightGreen()->blink("Done!");
		}
	}
}