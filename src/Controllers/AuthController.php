<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Repositories\AdminUserRepository;
use Ploofoodle\Repositories\AuditLogRepository;
use Ploofoodle\Services\AuditService;

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        $flash = \ploo_take_flash();
        $this->render('auth/login.php', [
            'flash' => $flash,
            'csrfToken' => \ploo_csrf_token(),
        ]);
    }

    public function login(): void
    {
        $csrf = $this->request->post('csrf');
        if (!\ploo_validate_csrf($csrf)) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect(\ploo_route_url('/auth/login'));
            return;
        }

        $username = (string)$this->request->post('username', '');
        $password = (string)$this->request->post('password', '');

        $user = $this->attemptDbLogin($username, $password);
        if ($user === null && $this->allowSeedLogin()) {
            $fallbackUser = ploo_config('auth')['seed_admin_username'] ?? 'admin';
            $fallbackPass = ploo_config('auth')['seed_admin_password'] ?? 'change-me-now';

            if (hash_equals((string)$fallbackUser, $username) && hash_equals((string)$fallbackPass, $password)) {
                $user = ['id' => null, 'username' => $username, 'role' => 'admin'];
            }
        }

        if ($user === null) {
            \ploo_flash('error', 'Invalid credentials.');
            $this->response->redirect(\ploo_route_url('/auth/login'));
            return;
        }

        \ploo_set_current_user([
            'id' => $user['id'] ?? null,
            'username' => $user['username'],
            'role' => $user['role'] ?? 'viewer',
        ]);

        $this->audit()->log([
            'actor_user_id' => $user['id'] ?? null,
            'actor_username' => $user['username'],
            'actor_role' => $user['role'] ?? 'viewer',
            'action' => 'login',
            'entity_type' => 'auth',
            'entity_id' => null,
            'ip_address' => $this->request->ipAddress(),
            'user_agent' => $this->request->userAgent(),
        ]);

        \ploo_flash('success', 'Signed in.');
        $this->response->redirect(\ploo_route_url('/admin'));
    }

    public function logout(): void
    {
        if (!\ploo_validate_csrf($this->request->post('csrf'))) {
            \ploo_flash('error', 'Invalid CSRF token.');
            $this->response->redirect(\ploo_route_url('/admin'));
            return;
        }

        $user = \ploo_current_user();
        if ($user !== null) {
            $this->audit()->log([
                'actor_user_id' => $user['id'] ?? null,
                'actor_username' => $user['username'] ?? 'unknown',
                'actor_role' => $user['role'] ?? 'viewer',
                'action' => 'logout',
                'entity_type' => 'auth',
                'entity_id' => null,
                'ip_address' => $this->request->ipAddress(),
                'user_agent' => $this->request->userAgent(),
            ]);
        }

        \ploo_logout_user();
        \ploo_flash('success', 'Signed out.');
        $this->response->redirect(\ploo_route_url('/auth/login'));
    }

    private function attemptDbLogin(string $username, string $password): ?array
    {
        try {
            $repo = new AdminUserRepository(Db::pdo());
            $user = $repo->findActiveByUsername($username);
            if (!is_array($user)) {
                return null;
            }
            if (!password_verify($password, (string)$user['password_hash'])) {
                return null;
            }
            if (is_int($user['id']) || ctype_digit((string)$user['id'])) {
                $repo->touchLastLogin((int)$user['id']);
            }
            return [
                'id' => isset($user['id']) ? (int)$user['id'] : null,
                'username' => (string)$user['username'],
                'role' => (string)($user['role'] ?? 'viewer'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function audit(): AuditService
    {
        return new AuditService(new AuditLogRepository(Db::pdo()));
    }

    private function allowSeedLogin(): bool
    {
        $allowSeed = (bool)(ploo_config('auth')['allow_seed_login'] ?? false);
        if (!$allowSeed) {
            return false;
        }

        $appEnv = strtolower((string)(getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? 'prod'));
        return in_array($appEnv, ['dev', 'test', 'local'], true);
    }
}
