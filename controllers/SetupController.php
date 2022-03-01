<?php

namespace Controller;

use League\CLImate\CLImate;

class SetupController {
	private CLImate $climate;

	public function __construct(CLImate $CLImate) {
		$this->climate = $CLImate;
		$this->climate->clear();
		$this->init();
	}

	public function init() {
		$this->climate->out("This will allow you the set the inital settings required to have the best experiance with LMPA!");
		$this->climate->br();

		$this->climate->lightGreen("Do you want to install phpMyAdmin?");
		$choice = menu(["no", "yes"]);
		if($choice === "1") {
			$target_dir = '..\..\..\etc\apps\phpMyAdmin';
			$this->climate->info("Check if $target_dir exists...");
			if(file_exists($target_dir)) {
				$this->climate->error("Error $target_dir is present\n");
				$this->climate->error("Do you want to delete it, and reset with the last version?");

				$choice = menu([
								   "no",
								   "yes"
							   ]);
				if($choice === "0") {
					exit;
				} else {
					removeDirectory($target_dir);
					if(!file_exists($target_dir)) {
						$this->climate->lightGreen("$target_dir was erased");
					} else {
						$this->climate->error("Can't remove $target_dir, please try to remove it manualy and restart utils");
						exit;
					}
				}
			}

			$url = "https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip";
			$sha256 = explode(" ", file_get_contents("https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip.sha256"))[0];

			$this->climate->info("Start download\n");
			$curl = new \Mervick\CurlHelper($url);
			$curl->setOptions([
								  CURLOPT_PROGRESSFUNCTION => 'curl_progress_bar',
								  CURLOPT_SSL_VERIFYHOST   => false,
								  CURLOPT_SSL_VERIFYPEER   => false,
								  CURLOPT_FOLLOWLOCATION   => true,
								  CURLOPT_NOPROGRESS       => 0
							  ]);
			$curl->setUserAgent("Laragon MultiPHP per App v." . LMPA_VERSION);
			$file = $curl->setTimeout(0)
						 ->exec();

			$this->climate->info("Checking download...");
			$this->climate->info(hash("sha256", $file['content']));
			$this->climate->info($sha256);

			if(hash("sha256", $file['content']) === $sha256) {
				$this->climate->lightGreen("Download is OK");
			} else {
				$this->climate->error("Download have fail");
				exit;
			}

			$this->climate->info("Put file in temp...");
			file_put_contents(basename($url), $file['content']);

			$this->climate->info("Extraction of download file");
			$zipArchive = new \ZipArchive();
			$result     = $zipArchive->open(basename($url), \ZipArchive::RDONLY);
			if($result === true) {
				$zipArchive->extractTo(dirname($target_dir));
				$zipArchive->close();

				$this->climate->lightGreen("File extracted");

				$this->climate->info("Delete temp file");
				unlink(basename($url));
			} else {
				$this->climate->error("Error when extarting the archive");
				exit;
			}

			$currentPMADirectory = glob('..\..\..\etc\apps\phpMyAdmin-*', GLOB_ONLYDIR);
			if(count($currentPMADirectory) !== 1) {
				$this->climate->error("Can't find the tmp folder of PMA...");
				exit;
			}
			$currentPMADirectory = $currentPMADirectory[0];
			
			$this->climate->out("Rename directory " . $currentPMADirectory . " to " . $target_dir);
			if(!rename($currentPMADirectory, $target_dir)) {
				$this->climate->error("Can't rename " . $currentPMADirectory . " to " . $target_dir);
				exit;
			}

			//Copy ini
			$this->climate->info("Copy config file " . $target_dir . "/config.sample.inc.php to " . $target_dir . "/config.sample.inc.php");
			if(!copy($target_dir . "/config.sample.inc.php", $target_dir . "/config.inc.php")) {
				$this->climate->error("Can't copy config file!");
				exit;
			}

			$this->climate->lightGreen("PHP My Admin is now availible at http://127.0.0.1/phpMyAdmin");
		}

		//Check if SSL is enabled
		$laragonIni = parse_ini_file("..\..\..\usr\laragon.ini", true, INI_SCANNER_RAW);

		if(!array_key_exists('SSLEnabled', $laragonIni['apache']) || $laragonIni['apache']['SSLEnabled'] === "0") {
			$this->climate->error("Please enabled ssl NOW in apache => ssl => enabled");
			$this->climate->error("Restrat the setup after");
			exit;
		} else {
			$this->climate->lightGreen("Apache SSL is enabled :)");
		}

		$this->climate->out("Setup FCGI");
		//TODO: get from internet

		$apacheDirectory = glob('..\..\..\bin\apache\httpd*', GLOB_ONLYDIR);
		//TODO: Handle multiple httpd versions
		if(count($apacheDirectory) !== 1) {
			$this->climate->error("Can't find the apache folder");
			exit;
		}
		$apacheDirectory = $apacheDirectory[0];

		$this->climate->out("Copy: ".APP_DIRECTORY.DIRECTORY_SEPARATOR."mod_fcgid.so into " . $apacheDirectory.DIRECTORY_SEPARATOR."modules");
		if(!copy(APP_DIRECTORY.DIRECTORY_SEPARATOR."mod_fcgid.so", $apacheDirectory.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."mod_fcgid.so")) {
			$this->climate->error("Impossible to copy the file");
			exit;
		}

		$this->climate->out("Adding module to $apacheDirectory/conf/httpd.conf");
		$apacheConfFile = $apacheDirectory.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'httpd.conf';
		if(!file_put_contents($apacheConfFile, file_get_contents($apacheConfFile).PHP_EOL.'LoadModule fcgid_module modules/mod_fcgid.so')) {
			$this->climate->error("Can't write the change to the config file");
			exit;
		}

		$this->climate->br(3);
		$this->climate->lightGreen("Please restart apache to complete");

	}
}