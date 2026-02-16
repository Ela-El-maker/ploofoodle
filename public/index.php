<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use Ploofoodle\Core\Router;
use Ploofoodle\Controllers\AuthController;
use Ploofoodle\Controllers\AdminDashboardController;
use Ploofoodle\Controllers\AdminAuditController;
use Ploofoodle\Controllers\AdminConfigController;
use Ploofoodle\Controllers\AdminReleaseController;
use Ploofoodle\Controllers\MobileBootstrapController;
use Ploofoodle\Controllers\MobileUpdateController;

$router = new Router();

$router->get('/', [AdminDashboardController::class, 'index']);
$router->get('/admin', [AdminDashboardController::class, 'index']);

$router->get('/auth/login', [AuthController::class, 'showLogin']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

$router->get('/admin/config', [AdminConfigController::class, 'index']);
$router->post('/admin/config', [AdminConfigController::class, 'saveOrPublish']);
$router->post('/admin/config/save-draft', [AdminConfigController::class, 'saveDraft']);
$router->post('/admin/config/publish', [AdminConfigController::class, 'publish']);
$router->post('/admin/config/reset-draft', [AdminConfigController::class, 'resetDraft']);
$router->post('/admin/config/delete-draft', [AdminConfigController::class, 'deleteDraft']);

$router->get('/admin/releases', [AdminReleaseController::class, 'index']);
$router->post('/admin/releases', [AdminReleaseController::class, 'saveOrPublish']);
$router->get('/admin/releases/create', [AdminReleaseController::class, 'create']);
$router->get('/admin/releases/view', [AdminReleaseController::class, 'view']);
$router->get('/admin/releases/edit', [AdminReleaseController::class, 'edit']);
$router->post('/admin/releases/publish', [AdminReleaseController::class, 'publish']);
$router->post('/admin/releases/unpublish', [AdminReleaseController::class, 'unpublish']);
$router->post('/admin/releases/delete', [AdminReleaseController::class, 'delete']);

$router->get('/admin/audit', [AdminAuditController::class, 'index']);
$router->get('/admin/audit/view', [AdminAuditController::class, 'view']);

$router->get('/mobile/bootstrap', [MobileBootstrapController::class, 'show']);
$router->get('/mobile/update', [MobileUpdateController::class, 'show']);

$router->dispatch();
