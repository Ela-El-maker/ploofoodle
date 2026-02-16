<?php

declare(strict_types=1);

namespace Ploofoodle\Core;

final class Response
{
    public function json(array $payload, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    public function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
    }

    public function redirect(string $location): void
    {
        header('Location: ' . $location);
    }

    public function notModified(array $headers = []): void
    {
        http_response_code(304);
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
    }
}
