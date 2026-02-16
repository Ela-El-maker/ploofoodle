<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

use InvalidArgumentException;
use Ploofoodle\Repositories\ConfigBundleRepository;
use Ploofoodle\Core\Db;

final class BootstrapConfigService
{
    private const ALLOWED_TOP_LEVEL_KEYS = [
        'feature_flags',
        'tuning',
        'welcome_slides',
        'support_links',
        'env_label',
        'cache_ttl_seconds',
    ];

    public function __construct(
        private ConfigBundleRepository $repository,
        private EtagService $etagService,
        private AuditService $auditService,
    ) {
    }

    public function saveOrPublish(array $input, array $actor, string $ipAddress, string $userAgent): array
    {
        $platform = $this->normalizePlatform($input['platform'] ?? null);
        $channel = $this->normalizeChannel($input['channel'] ?? null);
        $schemaVersion = max(1, (int)($input['schema_version'] ?? 1));
        $actionRaw = strtolower((string)($input['action'] ?? 'save_draft'));
        $action = in_array($actionRaw, ['publish', 'save_draft', 'reset_draft', 'delete_draft'], true)
            ? $actionRaw
            : 'save_draft';

        if ($action === 'delete_draft') {
            return $this->deleteDraft($platform, $channel, $actor, $ipAddress, $userAgent);
        }
        if ($action === 'reset_draft') {
            return $this->resetDraft($platform, $channel, $actor, $ipAddress, $userAgent);
        }

        $payload = $this->extractPayload($input['payload_json'] ?? null);
        $payload = $this->validateForPublish($payload);

        $ttl = $this->safeTtl((int)($input['cache_ttl_seconds'] ?? ($payload['cache_ttl_seconds'] ?? 3600)));
        $payload['cache_ttl_seconds'] = $ttl;

        $pdo = Db::pdo();
        $beforeDraft = $this->repository->findByStatus($platform, $channel, 'draft');
        $beforePublished = $this->repository->findByStatus($platform, $channel, 'published');

        $updatedAt = gmdate('c');
        $etag = $this->etagService->build($payload, $updatedAt, $schemaVersion);

        if ($action === 'publish') {
            $pdo->beginTransaction();
            try {
                $this->repository->save(
                    $platform,
                    $channel,
                    'draft',
                    $schemaVersion,
                    $payload,
                    $ttl,
                    $etag,
                    (int)($actor['id'] ?? 0) ?: null,
                    null,
                );

                $this->repository->save(
                    $platform,
                    $channel,
                    'published',
                    $schemaVersion,
                    $payload,
                    $ttl,
                    $etag,
                    (int)($actor['id'] ?? 0) ?: null,
                    gmdate('Y-m-d H:i:s'),
                );

                $this->auditService->log([
                    'actor_user_id' => $actor['id'] ?? null,
                    'actor_username' => $actor['username'] ?? 'unknown',
                    'actor_role' => $actor['role'] ?? 'viewer',
                    'action' => 'publish',
                    'entity_type' => 'config_bundle',
                    'entity_id' => null,
                    'platform' => $platform,
                    'channel' => $channel,
                    'before' => [
                        'draft' => $beforeDraft,
                        'published' => $beforePublished,
                    ],
                    'after' => ['status' => 'published', 'etag' => $etag],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            return ['message' => 'Bootstrap config published successfully.'];
        }

        $this->repository->save(
            $platform,
            $channel,
            'draft',
            $schemaVersion,
            $payload,
            $ttl,
            $etag,
            (int)($actor['id'] ?? 0) ?: null,
            null,
        );

        $this->auditService->log([
            'actor_user_id' => $actor['id'] ?? null,
            'actor_username' => $actor['username'] ?? 'unknown',
            'actor_role' => $actor['role'] ?? 'viewer',
            'action' => 'save_draft',
            'entity_type' => 'config_bundle',
            'entity_id' => null,
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'draft', 'etag' => $etag],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Bootstrap config draft saved.'];
    }

    public function getPublished(string $platform, string $channel): ?array
    {
        $row = $this->repository->findByStatus(
            $this->normalizePlatform($platform),
            $this->normalizeChannel($channel),
            'published'
        );

        if (!$row) {
            return null;
        }

        $payload = json_decode((string)$row['payload'], true);
        $payload = is_array($payload) ? $this->sanitizeOutput($payload) : [];

        return [
            'schema_version' => (int)$row['schema_version'],
            'platform' => (string)$row['platform'],
            'channel' => (string)$row['channel'],
            'updated_at' => (string)$row['updated_at'],
            'etag' => (string)$row['etag'],
            'cache_ttl_seconds' => (int)$row['cache_ttl_seconds'],
            'config' => $payload,
        ];
    }

    public function getDraft(string $platform, string $channel): ?array
    {
        return $this->repository->findByStatus(
            $this->normalizePlatform($platform),
            $this->normalizeChannel($channel),
            'draft'
        );
    }

    private function extractPayload(mixed $payloadRaw): array
    {
        if (is_array($payloadRaw)) {
            return $payloadRaw;
        }

        if (!is_string($payloadRaw) || trim($payloadRaw) === '') {
            throw new InvalidArgumentException('payload_json is required.');
        }

        $decoded = json_decode($payloadRaw, true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('payload_json must be valid JSON object.');
        }

        return $decoded;
    }

    private function validateForPublish(array $payload): array
    {
        $unknown = array_diff(array_keys($payload), self::ALLOWED_TOP_LEVEL_KEYS);
        if (!empty($unknown)) {
            throw new InvalidArgumentException('Unknown keys are not allowed: ' . implode(', ', $unknown));
        }

        return $payload;
    }

    private function sanitizeOutput(array $payload): array
    {
        $allowed = [];
        foreach (self::ALLOWED_TOP_LEVEL_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $allowed[$key] = $payload[$key];
            }
        }
        return $allowed;
    }

    private function normalizePlatform(?string $platform): string
    {
        $allowed = ploo_config('app')['allowed_platforms'] ?? ['android'];
        $candidate = strtolower(trim((string)($platform ?: ploo_config('app')['default_platform'] ?? 'android')));
        return in_array($candidate, $allowed, true) ? $candidate : 'android';
    }

    private function normalizeChannel(?string $channel): string
    {
        $allowed = ploo_config('app')['allowed_channels'] ?? ['stable'];
        $candidate = strtolower(trim((string)($channel ?: ploo_config('app')['default_channel'] ?? 'stable')));
        return in_array($candidate, $allowed, true) ? $candidate : 'stable';
    }

    private function safeTtl(int $ttl): int
    {
        if ($ttl < 60) {
            return 60;
        }
        if ($ttl > 86400) {
            return 86400;
        }
        return $ttl;
    }

    private function deleteDraft(string $platform, string $channel, array $actor, string $ipAddress, string $userAgent): array
    {
        $beforeDraft = $this->repository->findByStatus($platform, $channel, 'draft');
        if ($beforeDraft === null) {
            return ['message' => 'No draft to delete.'];
        }
        $this->repository->deleteByStatus($platform, $channel, 'draft');

        $this->auditService->log([
            'actor_user_id' => $actor['id'] ?? null,
            'actor_username' => $actor['username'] ?? 'unknown',
            'actor_role' => $actor['role'] ?? 'viewer',
            'action' => 'delete_draft',
            'entity_type' => 'config_bundle',
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'deleted'],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Draft deleted.'];
    }

    private function resetDraft(string $platform, string $channel, array $actor, string $ipAddress, string $userAgent): array
    {
        $published = $this->repository->findByStatus($platform, $channel, 'published');
        if ($published === null) {
            throw new InvalidArgumentException('Cannot reset draft. No published config exists for this platform/channel.');
        }

        $payload = json_decode((string)$published['payload'], true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $schemaVersion = max(1, (int)($published['schema_version'] ?? 1));
        $ttl = $this->safeTtl((int)($published['cache_ttl_seconds'] ?? 3600));
        $payload['cache_ttl_seconds'] = $ttl;
        $etag = $this->etagService->build($payload, gmdate('c'), $schemaVersion);

        $beforeDraft = $this->repository->findByStatus($platform, $channel, 'draft');
        $this->repository->save(
            $platform,
            $channel,
            'draft',
            $schemaVersion,
            $payload,
            $ttl,
            $etag,
            (int)($actor['id'] ?? 0) ?: null,
            null,
        );

        $this->auditService->log([
            'actor_user_id' => $actor['id'] ?? null,
            'actor_username' => $actor['username'] ?? 'unknown',
            'actor_role' => $actor['role'] ?? 'viewer',
            'action' => 'reset_draft',
            'entity_type' => 'config_bundle',
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'draft', 'etag' => $etag],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Draft reset from published config.'];
    }
}
