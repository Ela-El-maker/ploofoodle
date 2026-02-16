<?php

declare(strict_types=1);

namespace Ploofoodle\Repositories;

use PDO;

final class ConfigBundleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByStatus(string $platform, string $channel, string $status): ?array
    {
        $sql = 'SELECT * FROM admin_config_bundle WHERE platform = ? AND channel = ? AND bundle_key = ? AND status = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$platform, $channel, 'mobile_bootstrap', $status]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findLatestPublished(): ?array
    {
        $sql = "SELECT * FROM admin_config_bundle WHERE status = 'published' ORDER BY updated_at DESC, id DESC LIMIT 1";
        $stmt = $this->pdo->query($sql);
        $row = $stmt !== false ? $stmt->fetch() : false;
        return is_array($row) ? $row : null;
    }

    public function save(
        string $platform,
        string $channel,
        string $status,
        int $schemaVersion,
        array $payload,
        int $cacheTtl,
        string $etag,
        ?int $updatedBy,
        ?string $publishedAt
    ): void {
        $sql = 'INSERT INTO admin_config_bundle (platform, channel, bundle_key, status, schema_version, payload, cache_ttl_seconds, etag, updated_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE schema_version = VALUES(schema_version), payload = VALUES(payload), cache_ttl_seconds = VALUES(cache_ttl_seconds), etag = VALUES(etag), updated_by = VALUES(updated_by), published_at = VALUES(published_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $platform,
            $channel,
            'mobile_bootstrap',
            $status,
            $schemaVersion,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            $cacheTtl,
            $etag,
            $updatedBy,
            $publishedAt,
        ]);
    }

    public function deleteByStatus(string $platform, string $channel, string $status): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_config_bundle WHERE platform = ? AND channel = ? AND bundle_key = ? AND status = ?');
        $stmt->execute([$platform, $channel, 'mobile_bootstrap', $status]);
    }
}
