<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\UpdateManifestRepository;

final class PublicAppReleasesController extends PublicAppBaseController
{
    public function show(): void
    {
        $platform = strtolower(trim((string)($this->request->query('platform', '') ?? '')));
        $channel = $this->normalizedChannel();
        $filters = [
            'status' => 'published',
            'channel' => $channel,
            'platform' => in_array($platform, ['android', 'ios', 'web'], true) ? $platform : '',
            'q' => (string)($this->request->query('q', '') ?? ''),
        ];

        $rows = [];
        try {
            $rows = (new UpdateManifestRepository(Db::pdo()))->list($filters);
        } catch (\Throwable) {
            $rows = [];
        }

        $latest = $rows[0] ?? null;
        $history = count($rows) > 1 ? array_slice($rows, 1) : [];

        $etag = hash('sha256', json_encode([$filters, $rows], JSON_UNESCAPED_SLASHES));
        $etagHeader = '"' . $etag . '"';
        $ifNoneMatch = $this->request->header('If-None-Match');

        $headers = $this->publicCacheHeaders(3600);
        $headers['ETag'] = $etagHeader;

        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etagHeader) {
            $this->response->notModified($headers);
            return;
        }

        $this->renderPublic('public/app/releases.php', [
            'pageTitle' => 'Release Notes',
            'rows' => $rows,
            'latest' => $latest,
            'history' => $history,
            'platform' => $filters['platform'],
            'channel' => $channel,
            'search' => $filters['q'],
        ], 200, $headers);
    }

    public function notes(): void
    {
        $id = (int)($this->request->query('id', '0') ?? '0');
        $repo = new UpdateManifestRepository(Db::pdo());

        $row = null;
        try {
            $row = $id > 0 ? $repo->findById($id) : null;
        } catch (\Throwable) {
            $row = null;
        }

        if (!is_array($row) || (string)($row['status'] ?? '') !== 'published') {
            $headers = $this->publicCacheHeaders(300);
            $this->renderPublic('public/app/release_not_found.php', [
                'pageTitle' => 'Release Not Found',
            ], 404, $headers);
            return;
        }

        $etag = hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES));
        $etagHeader = '"' . $etag . '"';
        $ifNoneMatch = $this->request->header('If-None-Match');
        $headers = $this->publicCacheHeaders(3600);
        $headers['ETag'] = $etagHeader;
        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etagHeader) {
            $this->response->notModified($headers);
            return;
        }

        $this->renderPublic('public/app/release_notes.php', [
            'pageTitle' => 'Release Notes',
            'row' => $row,
        ], 200, $headers);
    }
}
