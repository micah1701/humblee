<?php

declare(strict_types=1);

namespace Humblee\Middleware;

class Package
{
    private static ?self $instance = null;

    private string $method;
    private array $data;

    private function __construct(string $method, array $data)
    {
        $this->method = $method;
        $this->data   = $data;
    }

    public static function build(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $data   = match ($method) {
            'GET'            => $_GET,
            'POST'           => self::resolvePost(),
            'PUT', 'PATCH'   => self::parseInputStream(),
            'DELETE'         => array_merge($_GET, self::parseInputStream()),
            default          => [],
        };

        self::$instance = new self($method, $data);
        return self::$instance;
    }

    public static function current(): self
    {
        return self::$instance ?? self::build();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    private static function resolvePost(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input'), true);
            return array_merge($_POST, is_array($decoded) ? $decoded : []);
        }
        return $_POST;
    }

    private static function parseInputStream(): array
    {
        $body        = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : [];
        }

        $data = [];
        parse_str($body, $data);
        return $data;
    }
}
