<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Middleware\RequireAdminAuth;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\ConfigBundleRepository;
use Ploofoodle\Repositories\UpdateManifestRepository;

final class AdminDashboardController extends BaseController
{
    public function index(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $configRepo = new ConfigBundleRepository(Db::pdo());
        $releaseRepo = new UpdateManifestRepository(Db::pdo());
        $auditRepo = new AuditLogRepository(Db::pdo());

        $publishedConfig = null;
        $publishedManifests = [];
        $recentAudit = [];

        try {
            $publishedConfig = $configRepo->findLatestPublished();
            $publishedManifests = $releaseRepo->list(['status' => 'published']);
            $recentAudit = $auditRepo->latest(10);
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Dashboard data unavailable. Run SQL migrations and seed data.');
        }

        $this->render('admin/dashboard.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'publishedConfig' => $publishedConfig,
            'publishedManifests' => $publishedManifests,
            'recentAudit' => $recentAudit,
        ]);
    }
}
