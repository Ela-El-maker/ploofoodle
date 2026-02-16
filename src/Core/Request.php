<?php

declare(strict_types=1);

namespace Ploofoodle\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $route = $_GET['_route'] ?? null;
        if (is_string($route) && $route !== '') {
            return '/' . trim($route, '/');
        }

        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($baseDir !== '' && $baseDir !== '/' && str_starts_with($uriPath, $baseDir)) {
            $uriPath = substr($uriPath, strlen($baseDir));
        }

        $uriPath = '/' . ltrim($uriPath, '/');
        return $uriPath === '' ? '/' : $uriPath;
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }
        return trim((string)$value);
    }

    public function post(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }
        return is_string($value) ? trim($value) : $default;
    }

    public function bodyJson(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$key] ?? null;
        return is_string($value) ? trim($value) : null;
    }

    public function ipAddress(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    }
}
