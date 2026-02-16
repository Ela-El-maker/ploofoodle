<?php

declare(strict_types=1);

namespace Ploofoodle\Controllers;

use Ploofoodle\Core\Db;
use Ploofoodle\Middleware\RequireAdminAuth;
use Ploofoodle\Repositories\AuditLogRepository;

final class AdminAuditController extends BaseController
{
    public function index(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $filters = [
            'actor' => (string)($this->request->query('actor', '') ?? ''),
            'entity_type' => (string)($this->request->query('entity_type', '') ?? ''),
            'from' => (string)($this->request->query('from', '') ?? ''),
            'to' => (string)($this->request->query('to', '') ?? ''),
        ];

        $rows = [];
        try {
            $rows = (new AuditLogRepository(Db::pdo()))->search($filters);
        } catch (\Throwable $e) {
            \ploo_flash('error', 'Audit table not available yet.');
        }

        $this->render('admin/audit/index.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function view(): void
    {
        $user = (new RequireAdminAuth())->handle();
        if ($user === null) {
            return;
        }

        $id = (int)($this->request->query('id', '0') ?? '0');
        $row = null;
        if ($id > 0) {
            try {
                $row = (new AuditLogRepository(Db::pdo()))->findById($id);
            } catch (\Throwable $e) {
                $row = null;
            }
        }

        $this->render('admin/audit/view.php', [
            'user' => $user,
            'flash' => \ploo_take_flash(),
            'csrfToken' => \ploo_csrf_token(),
            'row' => $row,
        ]);
    }
}
