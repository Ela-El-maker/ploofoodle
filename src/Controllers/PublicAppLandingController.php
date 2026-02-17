<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\UpdateManifestRepository;

final class PublicAppLandingController extends PublicAppBaseController
{
    public function show(): void
    {
        $platform = $this->platformFromQueryOrUa();
        $channel = $this->normalizedChannel();
        $content = null;
        $payload = [];
        $android = null;
        $ios = null;
        try {
            $content = $this->webContentService()->getPublishedForPublic('app_landing', $platform, $channel);
            $payload = is_array($content['payload'] ?? null) ? $content['payload'] : [];

            $manifestRepo = new UpdateManifestRepository(Db::pdo());
            $android = $manifestRepo->findByStatus('android', $channel, 'published');
            $ios = $manifestRepo->findByStatus('ios', $channel, 'published');
        } catch (\Throwable) {
            $content = null;
            $payload = [];
            $android = null;
            $ios = null;
        }

        $etagPayload = [
            'content' => $payload,
            'android' => $android,
            'ios' => $ios,
        ];

        $etag = hash('sha256', json_encode($etagPayload, JSON_UNESCAPED_SLASHES));
        $etagHeader = '"' . $etag . '"';
        $ifNoneMatch = $this->request->header('If-None-Match');

        $headers = $this->publicCacheHeaders((int)($content['cache_ttl_seconds'] ?? 3600));
        $headers['ETag'] = $etagHeader;

        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etagHeader) {
            $this->response->notModified($headers);
            return;
        }

        $this->renderPublic('public/app/landing.php', [
            'pageTitle' => 'Get the App',
            'platform' => $platform,
            'channel' => $channel,
            'payload' => $payload,
            'androidManifest' => $android,
            'iosManifest' => $ios,
        ], 200, $headers);
    }
}
