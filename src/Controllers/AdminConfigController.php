<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Middleware\CsrfGuard;
use Ploofoodle\Middleware\RequireAdminAuth;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\ConfigBundleRepository;
use Ploofoodle\Services\AuditService;
use Ploofoodle\Services\BootstrapConfigService;
use Ploofoodle\Services\EtagService;

final class AdminConfigController extends BaseController
{
    public function index(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $platform = $this->request->query('platform', ploo_config('app')['default_platform'] ?? 'android') ?? 'android';
        $channel = $this->request->query('channel', ploo_config('app')['default_channel'] ?? 'stable') ?? 'stable';

        $service = $this->service();
        $draft = null;
        $published = null;
        try {
            $draft = $service->getDraft($platform, $channel);
            $published = $service->getPublished($platform, $channel);
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Config tables not available yet. Apply SQL migrations first.');
        }

        $this->render('admin/config/editor.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'platform' => $platform,
            'channel' => $channel,
            'draft' => $draft,
            'published' => $published,
            'allowedPlatforms' => ploo_config('app')['allowed_platforms'] ?? ['android'],
            'allowedChannels' => ploo_config('app')['allowed_channels'] ?? ['stable'],
        ]);
    }

    public function saveOrPublish(): void
    {
        $this->handleWrite((string)($this->request->post('action') ?? 'save_draft'));
    }

    public function saveDraft(): void
    {
        $this->handleWrite('save_draft');
    }

    public function publish(): void
    {
        $this->handleWrite('publish');
    }

    public function resetDraft(): void
    {
        $this->handleWrite('reset_draft');
    }

    public function deleteDraft(): void
    {
        $this->handleWrite('delete_draft');
    }

    private function handleWrite(string $forcedAction): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        if (($user['role'] ?? 'viewer') !== 'admin') {
            \ploo_flash('error', 'Only admin users can modify configuration.');
            $this->response->redirect('/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/config');
            return;
        }

        if (!(new CsrfGuard())->validate($this->request->post('csrf'))) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect('/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/config');
            return;
        }

        $_POST['action'] = $forcedAction;

        try {
            $message = $this->service()->saveOrPublish($_POST, $user, $this->request->ipAddress(), $this->request->userAgent());
            \ploo_flash('success', $message['message'] ?? 'Saved.');
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Failed to save config: ' . $e->getMessage());
        }

        $platform = urlencode((string)($this->request->post('platform') ?? 'android'));
        $channel = urlencode((string)($this->request->post('channel') ?? 'stable'));
        $this->response->redirect('/Pandipoodle/Ploofoodle/public/index.php?_route=/admin/config&platform=' . $platform . '&channel=' . $channel);
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
