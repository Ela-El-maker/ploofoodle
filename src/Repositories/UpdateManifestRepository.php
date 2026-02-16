<?php

declare(strict_types=1);

namespace Ploofoodle\Repositories;

use PDO;

final class UpdateManifestRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByStatus(string $platform, string $channel, string $status): ?array
    {
        $sql = 'SELECT * FROM admin_update_manifest WHERE platform = ? AND channel = ? AND status = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$platform, $channel, $status]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_update_manifest WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function list(array $filters = []): array
    {
        $sql = 'SELECT * FROM admin_update_manifest WHERE 1=1';
        $params = [];

        if (!empty($filters['platform'])) {
            $sql .= ' AND platform = ?';
            $params[] = $filters['platform'];
        }
        if (!empty($filters['channel'])) {
            $sql .= ' AND channel = ?';
            $params[] = $filters['channel'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (latest_version LIKE ? OR min_supported_version LIKE ?)';
            $q = '%' . $filters['q'] . '%';
            $params[] = $q;
            $params[] = $q;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    public function save(string $status, array $data): void
    {
        $sql = 'INSERT INTO admin_update_manifest (platform, channel, status, schema_version, latest_version, min_supported_version, update_mode, download_url, release_notes_url, sha256, rollout_percent, cache_ttl_seconds, etag, updated_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE schema_version = VALUES(schema_version), latest_version = VALUES(latest_version), min_supported_version = VALUES(min_supported_version), update_mode = VALUES(update_mode), download_url = VALUES(download_url), release_notes_url = VALUES(release_notes_url), sha256 = VALUES(sha256), rollout_percent = VALUES(rollout_percent), cache_ttl_seconds = VALUES(cache_ttl_seconds), etag = VALUES(etag), updated_by = VALUES(updated_by), published_at = VALUES(published_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['platform'],
            $data['channel'],
            $status,
            $data['schema_version'],
            $data['latest_version'],
            $data['min_supported_version'],
            $data['update_mode'],
            $data['download_url'],
            $data['release_notes_url'],
            $data['sha256'],
            $data['rollout_percent'],
            $data['cache_ttl_seconds'],
            $data['etag'],
            $data['updated_by'],
            $data['published_at'],
        ]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_update_manifest WHERE id = ?');
        $stmt->execute([$id]);
    }
}
