<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;

final class HealthController extends BaseController
{
    public function show(): void
    {
        $db = 'disconnected';
        try {
            Db::pdo()->query('SELECT 1');
            $db = 'connected';
        } catch (\Throwable) {
            $db = 'disconnected';
        }

        $this->response->json([
            'success' => true,
            'service' => 'ploofoodle',
            'status' => $db === 'connected' ? 'healthy' : 'degraded',
            'database' => $db,
            'timestamp' => gmdate('c'),
        ], $db === 'connected' ? 200 : 503);
    }
}
