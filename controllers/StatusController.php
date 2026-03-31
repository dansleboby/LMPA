<?php

namespace Controller;

class StatusController {
	private $climate;

	public function __construct(\League\CLImate\CLImate $CLImate) {
		$this->climate = $CLImate;
		$this->climate->clear();
		$this->show();
	}

	private function show() {
		// Get default PHP version from mod_php.conf
		$apache2_mod_php = file_get_contents("../../../etc/apache2/mod_php.conf");
		if (!$apache2_mod_php || !preg_match('#LoadModule php[5-9]?_module "(.*)"#Ums', $apache2_mod_php, $matches)) {
			$this->climate->error("Could not determine default PHP version from mod_php.conf");
			return;
		}
		$default_binary = dirname($matches[1]) . DIRECTORY_SEPARATOR . "php.exe";
		$default_version = exeparser_fileversion($default_binary);
		$default_path = str_replace(DIRECTORY_SEPARATOR, '/', rtrim(dirname($matches[1]), DIRECTORY_SEPARATOR));

		// Get all installed PHP versions
		$php_binaries = glob("../../../bin/php/*-nts/php.exe");
		$installed = [];
		foreach ($php_binaries as $bin) {
			$version = exeparser_fileversion($bin);
			$path = str_replace(DIRECTORY_SEPARATOR, '/', rtrim(dirname(realpath($bin)), DIRECTORY_SEPARATOR));
			$installed[$path] = $version;
		}

		// Get app-to-PHP mapping
		$scan = scan_app_php_versions();

		$this->climate->out("<bold>LMPA Status Overview</bold>");
		$this->climate->border();

		// Group by PHP version
		foreach ($installed as $path => $version) {
			$is_default = ($path === $default_path);
			$apps = $scan['managed'][$path] ?? [];

			if (empty($apps) && !$is_default) {
				$this->climate->yellow("PHP $version  ⚠ unused");
			} else {
				$label = "PHP $version" . ($is_default ? " ★ default" : "");
				$this->climate->lightGreen($label);

				foreach ($apps as $app) {
					$this->climate->out("  ├── $app");
				}
			}
		}

		// Unmanaged apps
		if (!empty($scan['unmanaged'])) {
			$this->climate->br();
			$this->climate->out("<bold>Unmanaged apps (using Laragon default):</bold>");
			$names = array_map(function($n) {
				return strpos($n, 'auto.') === 0 ? substr($n, 5) : $n;
			}, $scan['unmanaged']);
			sort($names);

			if (count($names) <= 5) {
				$this->climate->out("  " . implode(', ', $names));
			} else {
				$shown = array_slice($names, 0, 5);
				$rest = count($names) - 5;
				$this->climate->out("  " . implode(', ', $shown) . " (+$rest more)");
			}
		}

		$this->climate->br();
	}
}
