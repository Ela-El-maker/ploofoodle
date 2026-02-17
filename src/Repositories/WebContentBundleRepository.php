<?php

declare(strict_types=1);

namespace Ploofoodle\Repositories;

use PDO;

final class WebContentBundleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByStatus(string $pageKey, string $platform, string $channel, string $status): ?array
    {
        $sql = 'SELECT * FROM admin_web_content_bundle WHERE page_key = ? AND platform = ? AND channel = ? AND status = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$pageKey, $platform, $channel, $status]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findPublishedWithFallback(string $pageKey, string $platform, string $channel): ?array
    {
        $candidates = [
            [$pageKey, $platform, $channel, 'published'],
            [$pageKey, 'all', $channel, 'published'],
            [$pageKey, 'all', 'stable', 'published'],
        ];

        $sql = 'SELECT * FROM admin_web_content_bundle WHERE page_key = ? AND platform = ? AND channel = ? AND status = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        foreach ($candidates as $candidate) {
            $stmt->execute($candidate);
            $row = $stmt->fetch();
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    /** @return array<int,array<string,mixed>> */
    public function listPublishedByPage(string $pageKey): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_web_content_bundle WHERE page_key = ? AND status = ? ORDER BY updated_at DESC, id DESC');
        $stmt->execute([$pageKey, 'published']);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function save(
        string $pageKey,
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
        $sql = 'INSERT INTO admin_web_content_bundle (page_key, platform, channel, status, schema_version, payload, cache_ttl_seconds, etag, updated_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE schema_version = VALUES(schema_version), payload = VALUES(payload), cache_ttl_seconds = VALUES(cache_ttl_seconds), etag = VALUES(etag), updated_by = VALUES(updated_by), published_at = VALUES(published_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $pageKey,
            $platform,
            $channel,
            $status,
            $schemaVersion,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            $cacheTtl,
            $etag,
            $updatedBy,
            $publishedAt,
        ]);
    }

    public function deleteByStatus(string $pageKey, string $platform, string $channel, string $status): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_web_content_bundle WHERE page_key = ? AND platform = ? AND channel = ? AND status = ?');
        $stmt->execute([$pageKey, $platform, $channel, $status]);
    }
}
