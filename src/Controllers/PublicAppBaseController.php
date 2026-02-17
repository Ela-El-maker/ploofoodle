<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\WebContentBundleRepository;
use Ploofoodle\Services\AuditService;
use Ploofoodle\Services\EtagService;
use Ploofoodle\Services\WebContentService;

abstract class PublicAppBaseController extends BaseController
{
    protected function platformFromQueryOrUa(): string
    {
        $query = strtolower(trim((string)($this->request->query('platform', '') ?? '')));
        if (in_array($query, ['android', 'ios', 'web'], true)) {
            return $query;
        }

        $ua = strtolower($this->request->userAgent());
        if (str_contains($ua, 'android')) {
            return 'android';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios')) {
            return 'ios';
        }
        return 'web';
    }

    protected function normalizedChannel(): string
    {
        $allowed = ploo_config('app')['allowed_channels'] ?? ['stable'];
        $candidate = strtolower(trim((string)($this->request->query('channel', ploo_config('app')['default_channel'] ?? 'stable') ?? 'stable')));
        return in_array($candidate, $allowed, true) ? $candidate : 'stable';
    }

    protected function webContentService(): WebContentService
    {
        return new WebContentService(
            new WebContentBundleRepository(Db::pdo()),
            new EtagService(),
            new AuditService(new AuditLogRepository(Db::pdo())),
        );
    }

    protected function renderPublic(string $viewFile, array $vars = [], int $status = 200, array $headers = []): void
    {
        $viewPath = PLOO_BASE_PATH . '/src/Views/' . $viewFile;
        if (!is_file($viewPath)) {
            $this->response->html('View not found: ' . htmlspecialchars($viewFile, ENT_QUOTES, 'UTF-8'), 500);
            return;
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = (string)ob_get_clean();
        $this->response->html($content, $status, $headers);
    }

    protected function publicCacheHeaders(int $ttlSeconds): array
    {
        $ttl = max(60, min(86400, $ttlSeconds));
        $swr = (int)(ploo_config('app')['public_cache_swr'] ?? 86400);
        return [
            'Cache-Control' => sprintf('public, max-age=%d, stale-while-revalidate=%d', $ttl, $swr),
        ];
    }

    protected function appRouteUrl(string $route, array $query = []): string
    {
        return ploo_route_url($route, $query);
    }
}
