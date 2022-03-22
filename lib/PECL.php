<?php

namespace Lib;

use League\CLImate\CLImate;
use Mervick\CurlHelper;

class PECL {
	private CLImate $climate;
	const BASE_URL = "https://windows.php.net/downloads/pecl/releases/";

	public function __construct() {
		$this->climate = new CLImate();
	}

	private function curl() {
		return (new CurlHelper())->setOptions([
												  CURLOPT_SSL_VERIFYHOST => false,
												  CURLOPT_SSL_VERIFYPEER => false
											  ])
								 ->setUserAgent("Laragon MultiPHP per App v.".LMPA_VERSION);
	}

	public function buildDatabase() {
		$content = $this->curl()->setUrl(self::BASE_URL)->exec();
		$regex = '/<a href="(?<url>.*)">(?<text>.*)<\/a>/Ui';
		preg_match_all($regex, $content['content'], $matches);
		$progress = $this->climate->progress(count($matches['text']));
		$packages = [];
		foreach($matches['text'] as $k => $package) {
			$progress->current($k+1, "Processing: " . $package);
			if($k === 0) continue;

			$tmp = [];
			$content = $this->curl()->setUrl("https://windows.php.net".$matches["url"][$k])->exec();
			preg_match_all($regex, $content['content'], $matches_ver);

			//$progress2 = $this->climate->progress(count($matches_ver['text']));

			foreach($matches_ver['text'] as $kk => $version) {
				$progress->current($k+1, "Processing: " . $package . " version: ".$version);
				if($kk === 0) continue;
				$content = $this->curl()->setUrl("https://windows.php.net".$matches_ver["url"][$kk])->exec();
				preg_match_all($regex, $content['content'], $matches_zip);
				foreach($matches_zip["url"] as $kkk => $zip) {
					if($kkk === 0) continue;
					if(substr($zip, -4) === ".zip")
						$tmp[$version][] = $zip;
				}
			}

			$packages[$package] = $tmp;
		}

		file_put_contents(path("pecl.json"), json_encode(['version' => time(), 'packages' => $packages]));

		$this->climate->info("Done!");
	}

	public static function getDatabase() {
		if(!file_exists(path("pecl.json"))) return [];
		return json_decode(file_get_contents(path("pecl.json")), true);
	}
}