<?php

namespace Lib;

class HttpClient
{
    /**
     * @var string|null
     */
    private ?string $url = null;

    /**
     * @var array<int, mixed>
     */
    private array $options = [];

    /**
     * @var string
     */
    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * @var int
     */
    private int $timeout = 30;

    /**
     * @var array<string>|null
     */
    private ?array $xpathQueries = null;

    /**
     * @param string|null $url
     */
    public function __construct(?string $url = null)
    {
        $this->url = $url;
    }

    /**
     * Static factory for fluent chaining.
     *
     * @param string $url
     * @return self
     */
    public static function factory(string $url): self
    {
        return new self($url);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Merge curl options. New options take precedence over previously set ones.
     *
     * @param array<int, mixed> $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = $options + $this->options;
        return $this;
    }

    /**
     * @param string $ua
     * @return $this
     */
    public function setUserAgent(string $ua): self
    {
        $this->userAgent = $ua;
        return $this;
    }

    /**
     * Set timeout in seconds. 0 means no timeout.
     *
     * @param int $seconds
     * @return $this
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set XPath queries to evaluate against the response HTML.
     *
     * @param array<string> $queries
     * @return $this
     */
    public function xpath(array $queries): self
    {
        $this->xpathQueries = $queries;
        return $this;
    }

    /**
     * Execute the HTTP request.
     *
     * @return array{status: int, content: string, data: mixed, xpath?: array}
     */
    public function exec(): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Apply user-provided options (they override defaults above)
        foreach ($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $content = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);

        // If curl_exec failed, content is false — normalize to empty string
        if ($content === false) {
            $content = '';
        }

        // Decode JSON if the response body starts with { or [
        $data = null;
        $trimmed = ltrim($content);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $data = json_decode($content, true);
        }

        $result = [
            'status'  => $status,
            'content' => $content,
            'data'    => $data,
            'error'   => $error,
            'errno'   => $errno,
        ];

        // Evaluate XPath queries if they were set
        if ($this->xpathQueries !== null) {
            $result['xpath'] = $this->evaluateXpath($content);
        }

        return $result;
    }

    /**
     * Evaluate XPath queries against HTML content.
     *
     * @param string $html
     * @return array<array<string>>
     */
    private function evaluateXpath(string $html): array
    {
        $results = [];

        if (empty($html) || empty($this->xpathQueries)) {
            return array_fill(0, count($this->xpathQueries ?? []), []);
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        foreach ($this->xpathQueries as $query) {
            $nodes = $xpath->query($query);
            $values = [];
            if ($nodes instanceof \DOMNodeList) {
                foreach ($nodes as $node) {
                    $values[] = $node->nodeValue;
                }
            }
            $results[] = $values;
        }

        return $results;
    }
}
