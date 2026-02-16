<?php

declare(strict_types=1);

use Ploofoodle\Core\Db;

const PLOO_BASE_PATH = __DIR__ . '/..';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Ploofoodle\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = PLOO_BASE_PATH . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

function ploo_load_env_file(string $envPath): void
{
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($value !== '' && (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        )) {
            $value = substr($value, 1, -1);
        }

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$defaultEnvPath = realpath(PLOO_BASE_PATH . '/../Plonkadoodle/.env') ?: '';
$explicitEnvPath = getenv('PLOOFOODLE_ENV_FILE') ?: '';
ploo_load_env_file($explicitEnvPath !== '' ? $explicitEnvPath : $defaultEnvPath);

$GLOBALS['ploo_config'] = [
    'app' => require PLOO_BASE_PATH . '/config/app.php',
    'db' => require PLOO_BASE_PATH . '/config/db.php',
    'auth' => require PLOO_BASE_PATH . '/config/auth.php',
];

function ploo_config(string $group): array
{
    return $GLOBALS['ploo_config'][$group] ?? [];
}

function ploo_base_path(): string
{
    $configured = trim((string)(ploo_config('app')['base_path'] ?? ''));
    if ($configured !== '') {
        return '/' . trim($configured, '/');
    }

    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $base === '' ? '' : $base;
}

function ploo_route_url(string $route, array $query = []): string
{
    $query = array_merge(['_route' => $route], $query);
    return ploo_base_path() . '/index.php?' . http_build_query($query);
}

$appConfig = ploo_config('app');
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $secureCookie,
        'use_strict_mode' => true,
    ]);
}

date_default_timezone_set('Africa/Nairobi');

Db::configure(ploo_config('db'));

function ploo_current_user(): ?array
{
    $key = ploo_config('app')['session_key'] ?? 'ploofoodle_auth';
    $user = $_SESSION[$key] ?? null;
    return is_array($user) ? $user : null;
}

function ploo_set_current_user(array $user): void
{
    $key = ploo_config('app')['session_key'] ?? 'ploofoodle_auth';
    $_SESSION[$key] = $user;
}

function ploo_logout_user(): void
{
    $sessionKey = ploo_config('app')['session_key'] ?? 'ploofoodle_auth';
    $csrfKey = ploo_config('app')['csrf_key'] ?? 'ploofoodle_csrf';
    unset($_SESSION[$sessionKey], $_SESSION[$csrfKey]);
}

function ploo_csrf_token(): string
{
    $csrfKey = ploo_config('app')['csrf_key'] ?? 'ploofoodle_csrf';
    if (empty($_SESSION[$csrfKey])) {
        $_SESSION[$csrfKey] = bin2hex(random_bytes(24));
    }
    return (string)$_SESSION[$csrfKey];
}

function ploo_validate_csrf(?string $token): bool
{
    $csrfKey = ploo_config('app')['csrf_key'] ?? 'ploofoodle_csrf';
    $current = (string)($_SESSION[$csrfKey] ?? '');
    return $token !== null && $current !== '' && hash_equals($current, $token);
}

function ploo_flash(string $type, string $message): void
{
    $_SESSION['ploofoodle_flash'] = ['type' => $type, 'message' => $message];
}

function ploo_take_flash(): ?array
{
    $flash = $_SESSION['ploofoodle_flash'] ?? null;
    unset($_SESSION['ploofoodle_flash']);
    return is_array($flash) ? $flash : null;
}
