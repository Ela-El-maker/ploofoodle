<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\ConfigBundleRepository;
use Ploofoodle\Services\AuditService;
use Ploofoodle\Services\BootstrapConfigService;
use Ploofoodle\Services\EtagService;

final class MobileBootstrapController extends BaseController
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
                'etag' => hash('sha256', 'bootstrap-default|' . $platform . '|' . $channel),
                'cache_ttl_seconds' => 3600,
                'config' => [
                    'feature_flags' => new \stdClass(),
                    'tuning' => new \stdClass(),
                    'welcome_slides' => [],
                    'support_links' => new \stdClass(),
                    'env_label' => 'prod',
                    'cache_ttl_seconds' => 3600,
                ],
            ];
        }

        $etag = '"' . $data['etag'] . '"';
        $ifNoneMatch = $this->request->header('If-None-Match');
        $cacheControl = sprintf(
            'public, max-age=%d, stale-while-revalidate=%d',
            max(60, min(86400, (int)$data['cache_ttl_seconds'])),
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
            'config' => $data['config'],
        ], 200, $headers);
    }

    private function service(): BootstrapConfigService
    {
        return new BootstrapConfigService(
            new ConfigBundleRepository(Db::pdo()),
            new EtagService(),
            new AuditService(new AuditLogRepository(Db::pdo())),
        );
    }
}
