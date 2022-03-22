<?php

function human_filesize($bytes, $decimals = 2) {
	$sz     = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function exeparser_fileversion($file) {
	$parser_model = [
		'begin' => "F\x00i\x00l\x00e\x00V\x00e\x00r\x00s\x00i\x00o\x00n",
		'end'   => "\x00\x00\x00"
	];
	if(file_exists($file) && is_readable($file)) {
		$version = file_get_contents($file);
		$version = explode($parser_model['begin'], $version);
		$version = explode($parser_model['end'], $version[1]);
		$version = str_replace("\x00", null, $version[1]);
		return ((!empty($version) ? $version : false));
	} else {
		print "\x1b[31m" . (is_dir($file) ? "Specified path points to a directory, not a file." : "The specified path to the file may not exist or is not a file at all.") . "\x1b[0m";
		return false;
	}
}

function menu($options, $default = null) {
	global $climate;

	$input = $climate->lightGreen()
					 ->input('Choose an option?' . (is_null($default) ? null : "[$default]"));

	foreach($options as $k => $option)
		$climate->out("$k) $option");

	$input->accept(array_keys($options));

	if(!is_null($default)) {
		$input->defaultTo((string)$default);
	}

	return $input->prompt();
}

function curl_progress_bar($curl, $dltotal, $dlnow, $ultotal, $ulnow) {
	global $progress;

	if($dltotal > 0) {
		$progress->current(ceil(($dlnow / $dltotal) * 100), curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) . " " . human_filesize($dlnow) . "/" . human_filesize($dltotal) . " (" . human_filesize(curl_getinfo($curl, CURLINFO_SPEED_DOWNLOAD)) . "b/s)");
	}
}

function array_except($array, $keys) {
	return array_diff_key($array, array_flip((array)$keys));
}

function to_bytes($str) {
	if(!preg_match('/^([\d.]+)([BKMGTPE]?)(B)?$/i', trim($str), $m)) {
		return 0;
	}
	return (int)floor($m[1] * ($m[2] ? (1024 ** strpos('BKMGTPE', strtoupper($m[2]))) : 1));
}

function ini_encodeing(string $key, string $value) {
	$allowedWithoutQuote = ["On", "Off", "True", "False", "Yes", "No", "None"];

	if(in_array($value, $allowedWithoutQuote) || is_numeric($value) || ctype_upper(preg_replace('/[^A-Za-z]/', '', $value)))
		return "$key=$value".PHP_EOL;
	else
		return $key.'="'.$value.'"'.PHP_EOL;
}

function removeDirectory($path) {
	if(PHP_OS === 'WINNT') {
		exec(sprintf("rd /s /q %s", escapeshellarg($path)));
	} else {
		exec(sprintf("rm -rf %s", escapeshellarg($path)));
	}
}

function path($path) {
	return strlen(Phar::running()) > 0 ? 'phar://index.phar/'.$path : $path;
}
