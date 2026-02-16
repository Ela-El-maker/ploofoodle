<?php

declare(strict_types=1);

namespace Ploofoodle\Repositories;

use PDO;

final class AuditLogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function append(array $entry): void
    {
        $sql = 'INSERT INTO admin_audit_log (actor_user_id, actor_username, actor_role, action, entity_type, entity_id, platform, channel, before_json, after_json, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $entry['actor_user_id'] ?? null,
            $entry['actor_username'] ?? 'unknown',
            $entry['actor_role'] ?? 'viewer',
            $entry['action'] ?? 'unknown',
            $entry['entity_type'] ?? 'unknown',
            $entry['entity_id'] ?? null,
            $entry['platform'] ?? null,
            $entry['channel'] ?? null,
            isset($entry['before']) ? json_encode($entry['before'], JSON_UNESCAPED_SLASHES) : null,
            isset($entry['after']) ? json_encode($entry['after'], JSON_UNESCAPED_SLASHES) : null,
            $entry['ip_address'] ?? null,
            $entry['user_agent'] ?? null,
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function latest(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->query('SELECT * FROM admin_audit_log ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        $rows = $stmt !== false ? $stmt->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int,array<string,mixed>> */
    public function search(array $filters): array
    {
        $sql = 'SELECT * FROM admin_audit_log WHERE 1=1';
        $params = [];

        if (!empty($filters['actor'])) {
            $sql .= ' AND actor_username LIKE ?';
            $params[] = '%' . $filters['actor'] . '%';
        }
        if (!empty($filters['entity_type'])) {
            $sql .= ' AND entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(created_at) >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(created_at) <= ?';
            $params[] = $filters['to'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_audit_log WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}
