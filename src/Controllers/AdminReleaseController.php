<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Middleware\CsrfGuard;
use Ploofoodle\Middleware\RequireAdminAuth;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\UpdateManifestRepository;
use Ploofoodle\Services\AuditService;
use Ploofoodle\Services\EtagService;
use Ploofoodle\Services\SemverService;
use Ploofoodle\Services\UpdateManifestService;

final class AdminReleaseController extends BaseController
{
    public function index(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $filters = [
            'platform' => (string)($this->request->query('platform', '') ?? ''),
            'channel' => (string)($this->request->query('channel', '') ?? ''),
            'status' => (string)($this->request->query('status', '') ?? ''),
            'q' => (string)($this->request->query('q', '') ?? ''),
        ];

        $rows = [];
        try {
            $rows = (new UpdateManifestRepository(Db::pdo()))->list($filters);
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Release manifest table not available yet.');
        }

        $this->render('admin/releases/index.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'rows' => $rows,
            'filters' => $filters,
            'allowedPlatforms' => ploo_config('app')['allowed_platforms'] ?? ['android'],
            'allowedChannels' => ploo_config('app')['allowed_channels'] ?? ['stable'],
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', null);
    }

    public function edit(): void
    {
        $id = (int)($this->request->query('id', '0') ?? '0');
        $row = $id > 0 ? (new UpdateManifestRepository(Db::pdo()))->findById($id) : null;
        $this->renderForm('edit', $row);
    }

    public function view(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $id = (int)($this->request->query('id', '0') ?? '0');
        $row = $id > 0 ? (new UpdateManifestRepository(Db::pdo()))->findById($id) : null;

        $this->render('admin/releases/view.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'row' => $row,
        ]);
    }

    public function saveOrPublish(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        if (($user['role'] ?? 'viewer') !== 'admin') {
            \ploo_flash('error', 'Only admin users can modify release metadata.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }

        if (!(new CsrfGuard())->validate($this->request->post('csrf'))) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }

        try {
            $result = $this->service()->saveOrPublish($_POST, $user, $this->request->ipAddress(), $this->request->userAgent());
            \ploo_flash('success', $result['message'] ?? 'Saved.');
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Failed to save release metadata: ' . $e->getMessage());
        }

        $this->response->redirect(ploo_route_url('/admin/releases'));
    }

    public function publish(): void
    {
        $this->applyRowAction('publish');
    }

    public function unpublish(): void
    {
        $this->applyRowAction('save_draft');
    }

    public function delete(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }
        if (($user['role'] ?? 'viewer') !== 'admin') {
            \ploo_flash('error', 'Only admin users can delete releases.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }
        if (!(new CsrfGuard())->validate($this->request->post('csrf'))) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }

        $id = (int)($this->request->post('id', '0') ?? '0');
        if ($id <= 0) {
            \ploo_flash('error', 'Invalid release id.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }

        try {
            (new UpdateManifestRepository(Db::pdo()))->deleteById($id);
            \ploo_flash('success', 'Release row deleted.');
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Delete failed: ' . $e->getMessage());
        }

        $this->response->redirect(ploo_route_url('/admin/releases'));
    }

    private function applyRowAction(string $action): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }
        if (($user['role'] ?? 'viewer') !== 'admin') {
            \ploo_flash('error', 'Only admin users can modify release metadata.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }
        if (!(new CsrfGuard())->validate($this->request->post('csrf'))) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }

        $id = (int)($this->request->post('id', '0') ?? '0');
        $row = $id > 0 ? (new UpdateManifestRepository(Db::pdo()))->findById($id) : null;
        if (!is_array($row)) {
            \ploo_flash('error', 'Release row not found.');
            $this->response->redirect(ploo_route_url('/admin/releases'));
            return;
        }

        $payload = [
            'platform' => $row['platform'] ?? 'android',
            'channel' => $row['channel'] ?? 'stable',
            'schema_version' => $row['schema_version'] ?? 1,
            'latest_version' => $row['latest_version'] ?? '1.0.0',
            'min_supported_version' => $row['min_supported_version'] ?? '1.0.0',
            'update_mode' => $row['update_mode'] ?? 'soft',
            'download_url' => $row['download_url'] ?? '',
            'release_notes_url' => $row['release_notes_url'] ?? '',
            'sha256' => $row['sha256'] ?? '',
            'rollout_percent' => $row['rollout_percent'] ?? 100,
            'cache_ttl_seconds' => $row['cache_ttl_seconds'] ?? 3600,
            'action' => $action,
        ];

        try {
            $result = $this->service()->saveOrPublish($payload, $user, $this->request->ipAddress(), $this->request->userAgent());
            \ploo_flash('success', $result['message'] ?? 'Action applied.');
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Action failed: ' . $e->getMessage());
        }

        $this->response->redirect(ploo_route_url('/admin/releases'));
    }

    private function renderForm(string $mode, ?array $row): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $this->render('admin/releases/form.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'mode' => $mode,
            'row' => $row ?? [],
            'allowedPlatforms' => ploo_config('app')['allowed_platforms'] ?? ['android'],
            'allowedChannels' => ploo_config('app')['allowed_channels'] ?? ['stable'],
        ]);
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
