<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use Ploofoodle\Core\Router;
use Ploofoodle\Controllers\AuthController;
use Ploofoodle\Controllers\AdminDashboardController;
use Ploofoodle\Controllers\AdminAuditController;
use Ploofoodle\Controllers\AdminConfigController;
use Ploofoodle\Controllers\AdminReleaseController;
use Ploofoodle\Controllers\AdminFrontLandingController;
use Ploofoodle\Controllers\MobileBootstrapController;
use Ploofoodle\Controllers\MobileUpdateController;
use Ploofoodle\Controllers\HealthController;
use Ploofoodle\Controllers\PublicAppLandingController;
use Ploofoodle\Controllers\PublicAppOpenController;
use Ploofoodle\Controllers\PublicAppReleasesController;
use Ploofoodle\Controllers\PublicAppGetStartedController;
use Ploofoodle\Controllers\PublicAppSupportController;

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

$router->get('/admin/front-landing', [AdminFrontLandingController::class, 'landing']);
$router->get('/admin/front-landing/get-started', [AdminFrontLandingController::class, 'getStarted']);
$router->get('/admin/front-landing/support', [AdminFrontLandingController::class, 'support']);
$router->post('/admin/front-landing/save', [AdminFrontLandingController::class, 'saveOrPublish']);

$router->get('/app', [PublicAppLandingController::class, 'show']);
$router->get('/app/open', [PublicAppOpenController::class, 'show']);
$router->get('/app/releases', [PublicAppReleasesController::class, 'show']);
$router->get('/app/releases/notes', [PublicAppReleasesController::class, 'notes']);
$router->get('/app/get-started', [PublicAppGetStartedController::class, 'show']);
$router->get('/app/support', [PublicAppSupportController::class, 'show']);

$router->get('/mobile/bootstrap', [MobileBootstrapController::class, 'show']);
$router->get('/mobile/update', [MobileUpdateController::class, 'show']);
$router->get('/health', [HealthController::class, 'show']);

$router->dispatch();
