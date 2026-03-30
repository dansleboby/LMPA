<?php

namespace Lib;

class Config
{
    private const FILE = 'lmpa.json';
    private array $data = [];

    private function filePath(): string
    {
        return APP_DIRECTORY . DIRECTORY_SEPARATOR . self::FILE;
    }

    public function __construct()
    {
        $file = $this->filePath();
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
            $this->filePath(),
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
