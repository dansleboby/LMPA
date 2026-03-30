<?php

namespace Lib;

class Config
{
    private const FILE = 'lmpa.json';
    private array $data = [];

    public function __construct()
    {
        $file = path(self::FILE);
        if (file_exists($file)) {
            $this->data = json_decode(file_get_contents($file), true) ?? [];
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function save(): bool
    {
        return (bool) file_put_contents(
            path(self::FILE),
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
