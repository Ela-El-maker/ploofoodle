<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

use InvalidArgumentException;
use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\WebContentBundleRepository;

final class WebContentService
{
    private const PAGE_ALLOWLIST = [
        'app_landing' => [
            'hero_title',
            'hero_subtitle',
            'primary_cta_label',
            'secondary_cta_label',
            'store_labels',
            'support_links',
            'faq_items',
            'show_apk_fallback',
        ],
        'app_get_started' => [
            'steps_android',
            'steps_ios',
            'steps_generic',
            'troubleshooting',
        ],
        'app_support' => [
            'contact_email',
            'contact_phone',
            'status_url',
            'terms_url',
            'privacy_url',
            'faq_items',
        ],
        'app_releases_meta' => [
            'hero_title',
            'hero_subtitle',
            'notes',
        ],
    ];

    public function __construct(
        private WebContentBundleRepository $repository,
        private EtagService $etagService,
        private AuditService $auditService,
    ) {
    }

    public function saveOrPublish(array $input, array $actor, string $ipAddress, string $userAgent): array
    {
        $pageKey = $this->normalizePageKey($input['page_key'] ?? null);
        $platform = $this->normalizePlatform($input['platform'] ?? null, allowAll: true);
        $channel = $this->normalizeChannel($input['channel'] ?? null);
        $schemaVersion = max(1, (int)($input['schema_version'] ?? 1));
        $actionRaw = strtolower((string)($input['action'] ?? 'save_draft'));
        $action = in_array($actionRaw, ['publish', 'save_draft', 'reset_draft', 'delete_draft'], true)
            ? $actionRaw
            : 'save_draft';

        if ($action === 'delete_draft') {
            return $this->deleteDraft($pageKey, $platform, $channel, $actor, $ipAddress, $userAgent);
        }
        if ($action === 'reset_draft') {
            return $this->resetDraft($pageKey, $platform, $channel, $actor, $ipAddress, $userAgent);
        }

        $payload = $this->extractPayload($input['payload_json'] ?? null);
        $payload = $this->validatePayload($pageKey, $payload);

        $ttl = $this->safeTtl((int)($input['cache_ttl_seconds'] ?? 3600));
        $pdo = Db::pdo();

        $beforeDraft = $this->repository->findByStatus($pageKey, $platform, $channel, 'draft');
        $beforePublished = $this->repository->findByStatus($pageKey, $platform, $channel, 'published');

        $etag = $this->etagService->build($payload, gmdate('c'), $schemaVersion);

        if ($action === 'publish') {
            $pdo->beginTransaction();
            try {
                $this->repository->save(
                    $pageKey,
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
                    $pageKey,
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
                    'entity_type' => 'web_content_bundle',
                    'entity_id' => null,
                    'platform' => $platform,
                    'channel' => $channel,
                    'before' => [
                        'draft' => $beforeDraft,
                        'published' => $beforePublished,
                    ],
                    'after' => ['status' => 'published', 'page_key' => $pageKey, 'etag' => $etag],
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

            return ['message' => 'Front page content published successfully.'];
        }

        $this->repository->save(
            $pageKey,
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
            'entity_type' => 'web_content_bundle',
            'entity_id' => null,
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'draft', 'page_key' => $pageKey, 'etag' => $etag],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Front page draft saved.'];
    }

    public function getPublishedForPublic(string $pageKey, string $platform, string $channel): ?array
    {
        $pageKey = $this->normalizePageKey($pageKey);
        $platform = $this->normalizePlatform($platform, allowAll: true);
        $channel = $this->normalizeChannel($channel);

        $row = $this->repository->findPublishedWithFallback($pageKey, $platform, $channel);
        if (!$row) {
            return null;
        }

        $payload = json_decode((string)$row['payload'], true);
        $payload = is_array($payload) ? $this->sanitizeOutput($pageKey, $payload) : [];

        return [
            'page_key' => (string)$row['page_key'],
            'platform' => (string)$row['platform'],
            'channel' => (string)$row['channel'],
            'schema_version' => (int)$row['schema_version'],
            'updated_at' => (string)$row['updated_at'],
            'etag' => (string)$row['etag'],
            'cache_ttl_seconds' => (int)$row['cache_ttl_seconds'],
            'payload' => $payload,
        ];
    }

    public function getDraft(string $pageKey, string $platform, string $channel): ?array
    {
        return $this->repository->findByStatus(
            $this->normalizePageKey($pageKey),
            $this->normalizePlatform($platform, allowAll: true),
            $this->normalizeChannel($channel),
            'draft'
        );
    }

    public function getPublished(string $pageKey, string $platform, string $channel): ?array
    {
        return $this->repository->findByStatus(
            $this->normalizePageKey($pageKey),
            $this->normalizePlatform($platform, allowAll: true),
            $this->normalizeChannel($channel),
            'published'
        );
    }

    public function allowedKeys(string $pageKey): array
    {
        return self::PAGE_ALLOWLIST[$this->normalizePageKey($pageKey)] ?? [];
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

    private function validatePayload(string $pageKey, array $payload): array
    {
        $allowed = $this->allowedKeys($pageKey);
        $unknown = array_diff(array_keys($payload), $allowed);
        if (!empty($unknown)) {
            throw new InvalidArgumentException('Unknown keys are not allowed: ' . implode(', ', $unknown));
        }

        return $payload;
    }

    private function sanitizeOutput(string $pageKey, array $payload): array
    {
        $safe = [];
        foreach ($this->allowedKeys($pageKey) as $key) {
            if (array_key_exists($key, $payload)) {
                $safe[$key] = $payload[$key];
            }
        }
        return $safe;
    }

    private function normalizePageKey(?string $pageKey): string
    {
        $candidate = strtolower(trim((string)($pageKey ?? 'app_landing')));
        if (!array_key_exists($candidate, self::PAGE_ALLOWLIST)) {
            throw new InvalidArgumentException('Invalid page_key.');
        }
        return $candidate;
    }

    private function normalizePlatform(?string $platform, bool $allowAll = false): string
    {
        $allowed = ploo_config('app')['allowed_platforms'] ?? ['android'];
        $candidate = strtolower(trim((string)($platform ?: ($allowAll ? 'all' : (ploo_config('app')['default_platform'] ?? 'android')))));
        if ($allowAll && $candidate === 'all') {
            return 'all';
        }
        return in_array($candidate, $allowed, true) ? $candidate : ($allowAll ? 'all' : 'android');
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

    private function deleteDraft(string $pageKey, string $platform, string $channel, array $actor, string $ipAddress, string $userAgent): array
    {
        $beforeDraft = $this->repository->findByStatus($pageKey, $platform, $channel, 'draft');
        if ($beforeDraft === null) {
            return ['message' => 'No draft to delete.'];
        }
        $this->repository->deleteByStatus($pageKey, $platform, $channel, 'draft');

        $this->auditService->log([
            'actor_user_id' => $actor['id'] ?? null,
            'actor_username' => $actor['username'] ?? 'unknown',
            'actor_role' => $actor['role'] ?? 'viewer',
            'action' => 'delete_draft',
            'entity_type' => 'web_content_bundle',
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'deleted', 'page_key' => $pageKey],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Draft deleted.'];
    }

    private function resetDraft(string $pageKey, string $platform, string $channel, array $actor, string $ipAddress, string $userAgent): array
    {
        $published = $this->repository->findByStatus($pageKey, $platform, $channel, 'published');
        if ($published === null) {
            throw new InvalidArgumentException('Cannot reset draft. No published content exists for this page/platform/channel.');
        }

        $payload = json_decode((string)$published['payload'], true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $schemaVersion = max(1, (int)($published['schema_version'] ?? 1));
        $ttl = $this->safeTtl((int)($published['cache_ttl_seconds'] ?? 3600));
        $etag = $this->etagService->build($payload, gmdate('c'), $schemaVersion);

        $beforeDraft = $this->repository->findByStatus($pageKey, $platform, $channel, 'draft');
        $this->repository->save(
            $pageKey,
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
            'entity_type' => 'web_content_bundle',
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'draft', 'page_key' => $pageKey, 'etag' => $etag],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Draft reset from published content.'];
    }
}
