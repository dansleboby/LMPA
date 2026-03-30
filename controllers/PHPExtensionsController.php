<?php

namespace Controller;

use Lib\Config;
use Lib\PhpExt;

class PHPExtensionsController
{
    private \League\CLImate\CLImate $climate;

    public function __construct(\League\CLImate\CLImate $CLImate)
    {
        $this->climate = $CLImate;
        $this->climate->clear();

        $action = menu([
            "Install PHP extensions",
            "Remove PHP extensions"
        ]);

        $this->climate->clear();

        // Both actions need a PHP version selection
        $php_binary = glob("../../../bin/php/*-nts/php.exe");
        $versions = array_map(function ($path) {
            return "v." . exeparser_fileversion($path);
        }, $php_binary);

        $this->climate->out("Choose which PHP version:");
        $this->climate->out("PHP versions currently installed:");

        if (empty($versions)) {
            $this->climate->yellow("No versions...");
            exit;
        }

        foreach ($versions as $n => $version) {
            $this->climate->lightCyan()->out($n . ') ' . $version);
        }

        $choice = $this->climate->lightGreen()->input("Choose an option:")->accept(array_keys($versions))->prompt();
        $php_version = $versions[$choice];

        $iniFile = '../../../bin/php/' . substr($php_version, 2) . '-nts/php.ini';

        if ($action == "0") {
            $this->install($php_version, $iniFile);
        }

        if ($action == "1") {
            $this->remove($php_version, $iniFile);
        }
    }

    private function install(string $php_version, string $iniFile): void
    {
        $config = new Config();
        $token = $config->get('phpext_api_token');

        if (empty($token)) {
            $this->climate->error("API token not configured. Please run Initial Setup to set your phpext.phptools.online API token.");
            $this->climate->error("Get your token at: https://phpext.phptools.online/account/api-token");
            exit;
        }

        $phpExt = new PhpExt($token);

        // Extract major.minor version (e.g. "v.8.1.10" -> "8.1")
        $versionParts = explode('.', substr($php_version, 2));
        $phpMajorMinor = $versionParts[0] . '.' . $versionParts[1];

        if (version_compare($phpMajorMinor, '7.0', '<')) {
            $this->climate->error("PHP $phpMajorMinor is too old. The extension repository only supports PHP 7.0+");
            exit;
        }

        $this->climate->out("Search for a PHP extension to install (for PHP $phpMajorMinor):");
        $query = $this->climate->lightGreen()->input("Extension name (e.g. redis, imagick, xdebug):")->prompt();

        $this->climate->out("Searching...");
        $results = $phpExt->search($query, $phpMajorMinor);

        if (isset($results['error'])) {
            $this->climate->error("API request failed (HTTP " . $results['status'] . ")");
            if (!empty($results['api_error'])) {
                $this->climate->error("API: " . $results['api_error']);
            }
            if (!empty($results['curl_error'])) {
                $this->climate->error("cURL: " . $results['curl_error']);
            }
            exit;
        }

        if (empty($results['result'])) {
            $this->climate->yellow("No extensions found matching '$query' for PHP $phpMajorMinor");
            exit;
        }

        // Build menu from results
        $menuItems = [];
        $resultList = $results['result'];
        foreach ($resultList as $k => $r) {
            $menuItems[$k] = $r['extName'] . ' ' . $r['extVersion'];
        }

        $this->climate->clear();
        $this->climate->out("Available extensions:");
        $choice = menu($menuItems);
        $selected = $resultList[$choice];

        $this->climate->clear();
        $this->climate->info("Start download\n");

        $full_url = $phpExt->downloadUrl($selected['extName'], $selected['extVersion'], $phpMajorMinor);
        $curl = new \Lib\HttpClient($full_url);
        $curl->setOptions([
            CURLOPT_PROGRESSFUNCTION => 'curl_progress_bar',
            CURLOPT_SSL_VERIFYHOST   => false,
            CURLOPT_SSL_VERIFYPEER   => false,
            CURLOPT_NOPROGRESS       => 0,
            CURLOPT_HTTPHEADER       => $phpExt->authHeader(),
        ]);
        $curl->setUserAgent("Laragon MultiPHP per App v." . LMPA_VERSION);
        $file = $curl->setTimeout(0)->exec();

        if (empty($file['content'])) {
            $this->climate->error("Download failed");
            if (!empty($file['error'])) {
                $this->climate->error("Error: " . $file['error']);
            }
            exit;
        }

        $zipName = "php_{$selected['extName']}-{$selected['extVersion']}.zip";

        $this->climate->info("Put file in temp...");
        file_put_contents($zipName, $file['content']);

        $this->climate->info("Extraction of download file");
        $zipArchive = new \ZipArchive();
        $result = $zipArchive->open($zipName);
        if ($result === true) {
            $phpFolder = '../../../bin/php/' . substr($php_version, 2) . '-nts/';
            for ($i = 0; $i < $zipArchive->numFiles; $i++) {
                $filename = $zipArchive->getNameIndex($i);

                $packageFilename = $selected['extName'];
                if ($packageFilename === "pecl_http") {
                    $packageFilename = "http";
                }

                if ($filename === "php_" . $packageFilename . ".dll") {
                    $dest = $phpFolder . 'ext/' . "php_" . $packageFilename . ".dll";
                    $this->climate->out("Extract: $filename -> ext/");
                    if (@file_put_contents($dest, $zipArchive->getFromIndex($i)) === false) {
                        $this->climate->yellow("Warning: Could not write $filename (file may be in use, restart Apache first)");
                    }
                } elseif (basename($filename) === $filename && pathinfo($filename, PATHINFO_EXTENSION) === "dll") {
                    $dest = $phpFolder . $filename;
                    $this->climate->out("Extract: $filename");
                    if (@file_put_contents($dest, $zipArchive->getFromIndex($i)) === false) {
                        $this->climate->yellow("Warning: Could not write $filename (file may be in use, restart Apache first)");
                    }
                }
            }

            $zipArchive->close();
            $this->climate->lightGreen("File extracted");
            $this->climate->info("Delete temp file");
            unlink($zipName);
        } else {
            $this->climate->error("Error when extracting the archive");
            exit;
        }

        $this->climate->out("Add extensions to ini");
        file_put_contents($iniFile, file_get_contents($iniFile) . PHP_EOL . "extension=$packageFilename");

        $this->climate->lightGreen()->blink("Done!");
    }

    private function remove(string $php_version, string $iniFile): void
    {
        $this->climate->clear();

        $currentIni = PHPIniController::parse_ini($iniFile);

        if (!isset($currentIni["extension"]) || empty($currentIni["extension"])) {
            $this->climate->yellow("No extensions installed");
            exit;
        }

        $currentExtensions = $currentIni["extension"];

        $this->climate->out("Which extension do you want to remove?");
        $menu = is_array($currentExtensions) ? array_values($currentExtensions) : [$currentExtensions];
        asort($menu);
        $choice = menu($menu);
        $myPackage = $menu[$choice];

        $this->climate->out("Remove extensions from ini");
        file_put_contents($iniFile, str_replace("extension=$myPackage", '', file_get_contents($iniFile)));

        $this->climate->out("Remove DLL");
        $dllPath = '../../../bin/php/' . substr($php_version, 2) . '-nts/ext/' . "php_" . $myPackage . ".dll";
        if (file_exists($dllPath) && !unlink($dllPath)) {
            $this->climate->red("Can't remove DLL");
            exit;
        }

        $this->climate->lightGreen()->blink("Done!");
    }
}
