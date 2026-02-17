<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

final class PublicAppSupportController extends PublicAppBaseController
{
    public function show(): void
    {
        $platform = $this->platformFromQueryOrUa();
        $channel = $this->normalizedChannel();

        $content = null;
        $payload = [];
        try {
            $content = $this->webContentService()->getPublishedForPublic('app_support', $platform, $channel);
            $payload = is_array($content['payload'] ?? null) ? $content['payload'] : [];
        } catch (\Throwable) {
            $content = null;
            $payload = [];
        }

        $etag = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
        $etagHeader = '"' . $etag . '"';
        $ifNoneMatch = $this->request->header('If-None-Match');

        $headers = $this->publicCacheHeaders((int)($content['cache_ttl_seconds'] ?? 3600));
        $headers['ETag'] = $etagHeader;

        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etagHeader) {
            $this->response->notModified($headers);
            return;
        }

        $this->renderPublic('public/app/support.php', [
            'pageTitle' => 'Support',
            'platform' => $platform,
            'channel' => $channel,
            'payload' => $payload,
        ], 200, $headers);
    }
}
