<?php

declare(strict_types=1);

namespace Ploofoodle\Core;

use PDO;

final class Db
{
    private static array $config = [];
    private static ?PDO $pdo = null;

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = self::$config['host'] ?? '127.0.0.1';
        $port = (int)(self::$config['port'] ?? 3306);
        $name = self::$config['name'] ?? 'up-skill';
        $user = self::$config['user'] ?? 'root';
        $pass = self::$config['pass'] ?? '';
        $charset = self::$config['charset'] ?? 'utf8mb4';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
