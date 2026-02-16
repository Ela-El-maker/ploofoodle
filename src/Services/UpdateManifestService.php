<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

use InvalidArgumentException;
use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\UpdateManifestRepository;

final class UpdateManifestService
{
    public function __construct(
        private UpdateManifestRepository $repository,
        private EtagService $etagService,
        private SemverService $semverService,
        private AuditService $auditService,
    ) {
    }

    public function saveOrPublish(array $input, array $actor, string $ipAddress, string $userAgent): array
    {
        $platform = $this->normalizePlatform($input['platform'] ?? null);
        $channel = $this->normalizeChannel($input['channel'] ?? null);
        $schemaVersion = max(1, (int)($input['schema_version'] ?? 1));
        $action = ($input['action'] ?? 'save_draft') === 'publish' ? 'publish' : 'save_draft';

        $latestVersion = $this->semverService->assertValid((string)($input['latest_version'] ?? ''));
        $minSupported = $this->semverService->assertValid((string)($input['min_supported_version'] ?? ''));
        $updateMode = strtolower((string)($input['update_mode'] ?? 'soft'));
        $updateMode = in_array($updateMode, ['soft', 'hard'], true) ? $updateMode : 'soft';

        $downloadUrl = trim((string)($input['download_url'] ?? ''));
        if ($downloadUrl === '') {
            throw new InvalidArgumentException('download_url is required.');
        }

        $releaseNotesUrl = trim((string)($input['release_notes_url'] ?? ''));
        $sha256 = trim((string)($input['sha256'] ?? ''));
        $rolloutPercent = (int)($input['rollout_percent'] ?? 100);
        $rolloutPercent = max(0, min(100, $rolloutPercent));
        $cacheTtl = $this->safeTtl((int)($input['cache_ttl_seconds'] ?? 3600));

        $payloadForEtag = [
            'platform' => $platform,
            'channel' => $channel,
            'latest_version' => $latestVersion,
            'min_supported_version' => $minSupported,
            'update_mode' => $updateMode,
            'download_url' => $downloadUrl,
            'release_notes_url' => $releaseNotesUrl,
            'sha256' => $sha256,
            'rollout_percent' => $rolloutPercent,
            'cache_ttl_seconds' => $cacheTtl,
        ];

        $etag = $this->etagService->build($payloadForEtag, gmdate('c'), $schemaVersion);

        $row = [
            'platform' => $platform,
            'channel' => $channel,
            'schema_version' => $schemaVersion,
            'latest_version' => $latestVersion,
            'min_supported_version' => $minSupported,
            'update_mode' => $updateMode,
            'download_url' => $downloadUrl,
            'release_notes_url' => $releaseNotesUrl !== '' ? $releaseNotesUrl : null,
            'sha256' => $sha256 !== '' ? $sha256 : null,
            'rollout_percent' => $rolloutPercent,
            'cache_ttl_seconds' => $cacheTtl,
            'etag' => $etag,
            'updated_by' => (int)($actor['id'] ?? 0) ?: null,
            'published_at' => null,
        ];

        $pdo = Db::pdo();
        $beforeDraft = $this->repository->findByStatus($platform, $channel, 'draft');
        $beforePublished = $this->repository->findByStatus($platform, $channel, 'published');

        if ($action === 'publish') {
            $pdo->beginTransaction();
            try {
                $this->repository->save('draft', $row);

                $row['published_at'] = gmdate('Y-m-d H:i:s');
                $this->repository->save('published', $row);

                $this->auditService->log([
                    'actor_user_id' => $actor['id'] ?? null,
                    'actor_username' => $actor['username'] ?? 'unknown',
                    'actor_role' => $actor['role'] ?? 'viewer',
                    'action' => 'publish',
                    'entity_type' => 'update_manifest',
                    'platform' => $platform,
                    'channel' => $channel,
                    'before' => ['draft' => $beforeDraft, 'published' => $beforePublished],
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

            return ['message' => 'Update manifest published successfully.'];
        }

        $this->repository->save('draft', $row);

        $this->auditService->log([
            'actor_user_id' => $actor['id'] ?? null,
            'actor_username' => $actor['username'] ?? 'unknown',
            'actor_role' => $actor['role'] ?? 'viewer',
            'action' => 'save_draft',
            'entity_type' => 'update_manifest',
            'platform' => $platform,
            'channel' => $channel,
            'before' => ['draft' => $beforeDraft],
            'after' => ['status' => 'draft', 'etag' => $etag],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return ['message' => 'Update manifest draft saved.'];
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

        return [
            'schema_version' => (int)$row['schema_version'],
            'platform' => (string)$row['platform'],
            'channel' => (string)$row['channel'],
            'updated_at' => (string)$row['updated_at'],
            'etag' => (string)$row['etag'],
            'manifest' => [
                'latest_version' => (string)$row['latest_version'],
                'min_supported_version' => (string)$row['min_supported_version'],
                'update_mode' => (string)$row['update_mode'],
                'download_url' => (string)$row['download_url'],
                'release_notes_url' => $row['release_notes_url'],
                'sha256' => $row['sha256'],
                'rollout_percent' => (int)$row['rollout_percent'],
                'cache_ttl_seconds' => (int)$row['cache_ttl_seconds'],
            ],
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
}
