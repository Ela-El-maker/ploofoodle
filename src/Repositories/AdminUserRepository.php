<?php

declare(strict_types=1);

namespace Ploofoodle\Repositories;

use PDO;

final class AdminUserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findActiveByUsername(string $username): ?array
    {
        $sql = 'SELECT id, username, password_hash, role, is_active FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
