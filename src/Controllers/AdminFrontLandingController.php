<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Middleware\CsrfGuard;
use Ploofoodle\Middleware\RequireAdminAuth;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Repositories\WebContentBundleRepository;
use Ploofoodle\Services\AuditService;
use Ploofoodle\Services\EtagService;
use Ploofoodle\Services\WebContentService;

final class AdminFrontLandingController extends BaseController
{
    public function landing(): void
    {
        $this->renderEditor('app_landing', 'Front Landing Page', 'Manage public /app landing content and store CTAs.');
    }

    public function getStarted(): void
    {
        $this->renderEditor('app_get_started', 'Get Started Page', 'Manage install and onboarding guidance at /app/get-started.');
    }

    public function support(): void
    {
        $this->renderEditor('app_support', 'Support Page', 'Manage support links and FAQs shown at /app/support.');
    }

    public function saveOrPublish(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        if (($user['role'] ?? 'viewer') !== 'admin') {
            \ploo_flash('error', 'Only admin users can modify front landing content.');
            $this->response->redirect(ploo_route_url('/admin/front-landing'));
            return;
        }

        if (!(new CsrfGuard())->validate($this->request->post('csrf'))) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect(ploo_route_url('/admin/front-landing'));
            return;
        }

        $route = $this->routeForPageKey((string)($this->request->post('page_key') ?? 'app_landing'));

        try {
            $result = $this->service()->saveOrPublish($_POST, $user, $this->request->ipAddress(), $this->request->userAgent());
            \ploo_flash('success', $result['message'] ?? 'Saved.');
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Failed to save front page content: ' . $e->getMessage());
        }

        $this->response->redirect(\ploo_route_url($route, [
            'platform' => (string)($this->request->post('platform') ?? 'all'),
            'channel' => (string)($this->request->post('channel') ?? 'stable'),
        ]));
    }

    private function renderEditor(string $pageKey, string $title, string $description): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $platform = $this->request->query('platform', 'all') ?? 'all';
        $channel = $this->request->query('channel', ploo_config('app')['default_channel'] ?? 'stable') ?? 'stable';

        $draft = null;
        $published = null;
        try {
            $draft = $this->service()->getDraft($pageKey, $platform, $channel);
            $published = $this->service()->getPublished($pageKey, $platform, $channel);
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Front landing content table not available yet. Apply SQL migrations first.');
        }

        $this->render('admin/front_landing/editor.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'platform' => $platform,
            'channel' => $channel,
            'pageKey' => $pageKey,
            'title' => $title,
            'description' => $description,
            'draft' => $draft,
            'published' => $published,
            'allowedPlatforms' => array_merge(['all'], ploo_config('app')['allowed_platforms'] ?? ['android']),
            'allowedChannels' => ploo_config('app')['allowed_channels'] ?? ['stable'],
            'allowedKeys' => $this->service()->allowedKeys($pageKey),
        ]);
    }

    private function routeForPageKey(string $pageKey): string
    {
        return match ($pageKey) {
            'app_get_started' => '/admin/front-landing/get-started',
            'app_support' => '/admin/front-landing/support',
            default => '/admin/front-landing',
        };
    }

    private function service(): WebContentService
    {
        return new WebContentService(
            new WebContentBundleRepository(Db::pdo()),
            new EtagService(),
            new AuditService(new AuditLogRepository(Db::pdo())),
        );
    }
}
