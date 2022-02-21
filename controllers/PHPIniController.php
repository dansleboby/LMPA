<?php

namespace Controller;

class PHPIniController {
	private \League\CLImate\CLImate $climate;

	public function __construct(\League\CLImate\CLImate $CLImate) {
		$this->climate = $CLImate;
	}

	private function _ini_key_checks(string $test_key, string $test_version) {
		//Source: https://www.php.net/manual/en/ini.list.php

		$check = [
			">=" => [
				"8.1.0" => [
					"mysqli.local_infile_directory"
				],
				"8.0.0" => [
					"com.dotnet_version",
					"opcache.jit",
					"opcache.jit_buffer_size",
					"opcache.jit_debug",
					"opcache.jit_bisect_limit",
					"opcache.jit_prof_threshold",
					"opcache.jit_max_root_traces",
					"opcache.jit_max_side_traces",
					"opcache.jit_max_exit_counters",
					"opcache.jit_hot_loop",
					"opcache.jit_hot_func",
					"opcache.jit_hot_return",
					"opcache.jit_hot_side_exit",
					"opcache.jit_blacklist_root_trace",
					"opcache.jit_blacklist_side_trace",
					"opcache.jit_max_loop_unrolls",
					"opcache.jit_max_recursive_calls",
					"opcache.jit_max_recursive_returns",
					"opcache.jit_max_polymorphic_calls"
				],
				"7.4.0" => [
					"opcache.preload",
					"opcache.preload_user",
					"opcache.cache_id"
				],
				"7.3.0" => [
					"session.cookie_samesite",
					"syslog.facility",
					"syslog.filter",
					"syslog.ident"
				],
				"7.1.17" => [
					"sqlite3.defensive"
				],
				"7.1.0" => [
					"hard_timeout",
					"opcache.opt_debug_level",
					"session.trans_sid_tags",
					"session.trans_sid_hosts",
					"session.sid_length",
					"session.sid_bits_per_character"
				],
				"7.0.14" => [
					"opcache.validate_permission",
				]
			],
			"<" => [
				"8.0.0" => [
					"mbstring.func_overload",
					"track_errors"
				],
				"7.3.0" => [
					"opcache.inherited_hack"
				],
				"7.2.0" => [
					"opcache.fast_shutdown"
				],
				"7.1.0" => [
					"session.hash_function",
					"session.hash_bits_per_character",
					"session.entropy_file",
					"session.entropy_length"
				]
			]
		];

		$haveToBeCheck = [];

		foreach($check as $operator => $versions) {
			foreach($versions as $version => $keys) {
				if(in_array($test_key, $keys) && version_compare($test_version, $version, $operator))
					return true;

				$haveToBeCheck = array_merge($haveToBeCheck, $keys);
			}
		}

		return !in_array($test_key, $haveToBeCheck);
	}

	public static function parse_ini($file) {
		$arr    = [];
		$handle = fopen($file, "r");
		if($handle) {
			while(($line = fgets($handle)) !== false) {
				$parsed = parse_ini_string($line, false, INI_SCANNER_RAW);
				if(empty($parsed)) {
					continue;
				}
				$key = key($parsed);
				if(isset($arr[$key])) {
					if(!is_array($arr[$key])) {
						$tmp       = $arr[$key];
						$arr[$key] = [$tmp];
					}
					$arr[$key][] = $parsed[$key];
				} else {
					if($key === "extension") {
						$arr[$key][] = $parsed[$key];
					} else {
						$arr[$key] = $parsed[$key];
					}
				}
			}
			fclose($handle);
			return $arr;
		} else {
			return false;
		}
	}

	public static function write_ini(string $file, array $data):bool {
		$currentIni = self::parse_ini($file);
		$extensionRemoved = array_diff($currentIni['extension']??[], $data['extension']??[]);
		$lines = file($file);
		$checks = array_combine(array_keys($data), array_fill(0, count($data), false));
		$checks["extension"] = array_combine($data["extension"]??[], array_fill(0, count($data["extension"]??[]), false));

		foreach($lines as &$line) {

			if(!empty($extensionRemoved)) {
				foreach($extensionRemoved as $extRemove) {
					if(strpos($line, "extension=$extRemove") === 0) {
						$line = ";extension=$extRemove".PHP_EOL;
					}
				}
			}

			foreach(array_keys($data) as $key) {
				if($key === "extension" && is_array($data[$key])) {
					foreach($data[$key] as $ext) {
						if(strpos($line, "$key=$ext") === 0 || strpos($line, ";$key=$ext") === 0) {
							$checks[$key][$ext] = true;
							$line = "extension=$ext".PHP_EOL;
						}
					}
				} else {
					if((strpos($line, $key."=") === 0
					    || strpos($line, $key." =") === 0
						|| strpos($line, ";$key=") === 0
						|| strpos($line, ";$key =") === 0
					) && $checks[$key] === false) {
						$checks[$key] = true;
						$line         = ini_encodeing($key, $data[$key]);
					}
				}
			}
		}

		//Add config not present in default ini
		$missing  = array_filter(array_except($checks, ["extension"]), function($v) {return !$v;});
		if(!empty($missing)) $lines = array_merge($lines, [PHP_EOL,PHP_EOL,PHP_EOL,";CUSTOM",PHP_EOL]);

		foreach($missing as $key => $value) {
			$lines[] = ini_encodeing($key, $data[$key]);
		}

		$missingExtension = array_keys(array_filter($checks["extension"], function($v) {return !$v;}));
		foreach($missingExtension as $ext) {
			$lines[] = "extension=$ext".PHP_EOL;
		}

		return file_put_contents($file, implode('', $lines));
	}

	public function merge(string $source, string $target, array $excluded = ["extension", "zend_extension", "extension_dir"]) {
		$sourceIni  = self::parse_ini($source);
		$targetIni  = self::parse_ini($target);

		$matches = [];
		preg_match("#(?:php-)?([5-9].[0-9].[0-9]+)+.*#", basename(dirname($target)), $matches);
		$targetVersion = $matches[1];

		$iniDiff = array_diff_assoc(array_except($sourceIni, $excluded), array_except($targetIni, $excluded));
		ksort($iniDiff);

		$this->climate->br();
		$this->climate->out("Key in <red>RED</red> having compatibility issue with the target version, they will be ignored");
		$this->climate->br();

		$table = [["Php key", "Current value", "New value from default ini"]];
		foreach($iniDiff as $key => $value) {
			$table[] = [
				$this->_ini_key_checks($key, $targetVersion) ? $key : "<red>$key</red>",
				empty($targetIni[$key])?'<red>Empty</red>':$targetIni[$key],
				$this->_ini_key_checks($key, $targetVersion) ? $value : "<red>N/A</red>"
			];

			# Remove unsupported ini from default
			if(!$this->_ini_key_checks($key, $targetVersion)) {
				unset($iniDiff[$key]);
				unset($sourceIni[$key]);
			}
		}

		$this->climate->ctable($table);

		$choice = menu([
						   "Take all values from default",
						   "Keep all current values",
						   "Choose one by one"
					   ], 0);

		if($choice == 0) {
			foreach($iniDiff as $key => $value)
				$targetIni[$key] = $sourceIni[$key];
		}

		if($choice == 2) {
			$total = count($iniDiff);
			$counter = 0;
			foreach($iniDiff as $key => $value) {
				$this->climate->clear();
				$this->climate->magenta(++$counter."/$total\n");
				$this->climate->ctable([
										["Php key", "Current value", "New value from default ini"],
										[$key, empty($targetIni[$key])?'<red>Empty</red>':$targetIni[$key], $value]
									   ]);
				$choice = menu([
								   "Take values from default",
								   "Keep current values",
								   "Manual value"
							   ]);
				if($choice == 0)
					$targetIni[$key] = $sourceIni[$key];
				if($choice == 2) {
					$value           = $this->climate->lightGreen()
													 ->input("Enter a value for $key:")
													 ->prompt();
					$targetIni[$key] = $value;
				}
			}
		}

		$targetIni["extension_dir"] = "ext";

		$this->climate->clear();


		$table = [["Php key", "New target value"]];
		foreach($iniDiff as $key => $value) $table[] = [$key, empty($targetIni[$key])?'<red>Empty</red>':$targetIni[$key]];

		$this->climate->ctable($table);
		$this->climate->info("All settings are good?");
		$choice = menu(["No", "Yes"], 1);

		if($choice == 0) {
			exit;
		}

		$this->climate->clear();
		$this->climate->info("Extensions:\n<green>✔ enabled</green>\n<yellow>~ not enable, but availible</yellow>\n<red>❌ missing</red>");
		$this->climate->br();

		$extsDiff = array_diff_assoc($sourceIni["extension"]??[], $targetIni["extension"]??[]);
		ksort($extsDiff);

		$targetExtensions = array_map(function($v) { return substr(basename($v), 4, -4); }, glob(dirname($target).DIRECTORY_SEPARATOR."ext/*.dll"));

		$table = [["Default", "New"]];
		$fatalMiss = [];
		foreach($sourceIni["extension"]??[] as $k => $ext) {
			$table[$k+1][0] = $ext;
			if(in_array($ext, $targetIni["extension"]??[])) {
				$table[$k + 1][1] = "<green>✔ $ext</green>";
				unset($targetIni['extension'][array_search($ext, $targetIni['extension']??[])]);
			} elseif(in_array($ext, $targetExtensions)) {
				$table[$k + 1][1] = "<yellow>~ $ext</yellow>";
				$targetIni["extension"][] = $ext;
			} else {
				$table[$k + 1][1] = "<red>❌ $ext</red>";
				$fatalMiss[] = $ext;
			}
		}

		$this->climate->ctable($table);

		if(!empty($fatalMiss)) {
			$this->climate->out("Continue with <red>MISSING</red> extension, so: <bold>" . implode(", ", $fatalMiss) . "</bold> will <red>NOT</red> be enable in the version");
		} else {
			$this->climate->out("Continue?");
		}

		$choice = menu(["No", "Yes"], 1);
		if($choice == 0)
			exit;

		$this->climate->clear();

		$this->climate->info("Save ini...");
		if(!self::write_ini($target, $targetIni)) {
			$this->climate->error("Error when saving ini file");
		}

		return true;
	}
}