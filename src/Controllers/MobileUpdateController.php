<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\UpdateManifestRepository;
use Ploofoodle\Services\AuditService;
use Ploofoodle\Services\EtagService;
use Ploofoodle\Services\SemverService;
use Ploofoodle\Services\UpdateManifestService;

final class MobileUpdateController extends BaseController
{
    public function show(): void
    {
        $platform = $this->request->query('platform', ploo_config('app')['default_platform'] ?? 'android') ?? 'android';
        $channel = $this->request->query('channel', ploo_config('app')['default_channel'] ?? 'stable') ?? 'stable';

        $data = null;
        try {
            $data = $this->service()->getPublished($platform, $channel);
        } catch (\Throwable) {
            $data = null;
        }
        if ($data === null) {
            $data = [
                'schema_version' => 1,
                'platform' => $platform,
                'channel' => $channel,
                'updated_at' => gmdate('c'),
                'etag' => hash('sha256', 'update-default|' . $platform . '|' . $channel),
                'manifest' => [
                    'latest_version' => '0.0.0',
                    'min_supported_version' => '0.0.0',
                    'update_mode' => 'soft',
                    'update_source' => 'web',
                    'download_url' => '',
                    'distribution_url' => null,
                    'release_notes_url' => null,
                    'sha256' => null,
                    'rollout_percent' => 100,
                    'cache_ttl_seconds' => 3600,
                ],
            ];
        }

        $etag = '"' . $data['etag'] . '"';
        $ifNoneMatch = $this->request->header('If-None-Match');
        $cacheControl = sprintf(
            'public, max-age=%d, stale-while-revalidate=%d',
            max(60, min(86400, (int)($data['manifest']['cache_ttl_seconds'] ?? 3600))),
            (int)(ploo_config('app')['public_cache_swr'] ?? 86400)
        );

        $headers = [
            'ETag' => $etag,
            'Cache-Control' => $cacheControl,
        ];

        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etag) {
            $this->response->notModified($headers);
            return;
        }

        $this->response->json([
            'success' => true,
            'schema_version' => $data['schema_version'],
            'platform' => $data['platform'],
            'channel' => $data['channel'],
            'updated_at' => $data['updated_at'],
            'manifest' => $data['manifest'],
        ], 200, $headers);
    }

    private function service(): UpdateManifestService
    {
        return new UpdateManifestService(
            new UpdateManifestRepository(Db::pdo()),
            new EtagService(),
            new SemverService(),
            new AuditService(new AuditLogRepository(Db::pdo())),
        );
    }
}
