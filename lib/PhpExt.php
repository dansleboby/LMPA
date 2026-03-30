<?php

namespace Lib;

class PhpExt
{
    private const BASE_URL = 'https://phpext.phptools.online';
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Search for PHP extensions matching a query, filtered by PHP version.
     *
     * @param string $query Extension name to search (e.g. "redis", "imagick")
     * @param string $phpVersion PHP major.minor version (e.g. "8.1")
     * @return array Search results from the API
     */
    public function search(string $query, string $phpVersion): array
    {
        $url = self::BASE_URL . '/api/release-build/search';

        $body = json_encode([
            'query'      => 'php_' . $query,
            'phpVersion' => $phpVersion,
            'arch'       => 'x64',
            'tsState'    => 'nts',
        ]);

        $curl = new HttpClient($url);
        $curl->setOptions([
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $curl->setUserAgent('Laragon MultiPHP per App v.' . LMPA_VERSION);

        $response = $curl->exec();

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $apiError = '';
            if (!empty($response['data']['message'])) {
                $apiError = $response['data']['message'];
            } elseif (!empty($response['data']['error'])) {
                $apiError = $response['data']['error'];
            } elseif (!empty($response['content'])) {
                $apiError = substr($response['content'], 0, 200);
            }
            return ['error' => true, 'status' => $response['status'], 'curl_error' => $response['error'] ?? '', 'api_error' => $apiError];
        }

        return $response['data'] ?? [];
    }

    /**
     * Build the download URL for a specific extension build.
     *
     * @param string $extName Extension name (e.g. "redis")
     * @param string $extVersion Extension version (e.g. "6.0.2")
     * @param string $phpVersion PHP major.minor version (e.g. "8.1")
     * @return string Full download URL
     */
    public function downloadUrl(string $extName, string $extVersion, string $phpVersion): string
    {
        return self::BASE_URL . "/api/release-build/php_{$extName}-{$extVersion}-{$phpVersion}-x64-nts.zip";
    }

    /**
     * Get the Authorization header array for download requests.
     *
     * @return array
     */
    public function authHeader(): array
    {
        return ['Authorization: Bearer ' . $this->token];
    }
}
