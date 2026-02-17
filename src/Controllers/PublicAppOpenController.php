<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\UpdateManifestRepository;

final class PublicAppOpenController extends PublicAppBaseController
{
    public function show(): void
    {
        $platform = $this->platformFromQueryOrUa();
        $channel = $this->normalizedChannel();

        $manifest = null;
        try {
            $repo = new UpdateManifestRepository(Db::pdo());
            $manifest = $repo->findByStatus($platform === 'web' ? 'android' : $platform, $channel, 'published');
        } catch (\Throwable) {
            $manifest = null;
        }

        $target = $this->resolveTarget($manifest);
        $target = $this->appendUtmParams($target);

        error_log(sprintf(
            '[ploo_app_open] platform=%s channel=%s target=%s ip=%s',
            $platform,
            $channel,
            $target,
            $this->request->ipAddress()
        ));

        $this->response->redirect($target);
    }

    private function resolveTarget(?array $manifest): string
    {
        if (!is_array($manifest)) {
            return ploo_route_url('/app');
        }

        $source = strtolower((string)($manifest['update_source'] ?? 'web'));
        $distribution = trim((string)($manifest['distribution_url'] ?? ''));
        $download = trim((string)($manifest['download_url'] ?? ''));

        if (in_array($source, ['play', 'appstore', 'web'], true) && $distribution !== '') {
            return $distribution;
        }
        if ($download !== '') {
            return $download;
        }

        return ploo_route_url('/app');
    }

    private function appendUtmParams(string $target): string
    {
        $allowed = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'ref'];
        $params = [];
        foreach ($allowed as $key) {
            $value = $this->request->query($key, null);
            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }

        if ($params === []) {
            return $target;
        }

        $parts = parse_url($target);
        if ($parts === false) {
            return $target;
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string)$parts['query'], $query);
        }
        $query = array_merge($query, $params);

        $rebuilt = '';
        if (!empty($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }
        if (!empty($parts['user'])) {
            $rebuilt .= $parts['user'];
            if (!empty($parts['pass'])) {
                $rebuilt .= ':' . $parts['pass'];
            }
            $rebuilt .= '@';
        }
        if (!empty($parts['host'])) {
            $rebuilt .= $parts['host'];
        }
        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '';

        $queryString = http_build_query($query);
        if ($queryString !== '') {
            $rebuilt .= '?' . $queryString;
        }
        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt !== '' ? $rebuilt : $target;
    }
}
