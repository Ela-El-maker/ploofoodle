<?php

declare(strict_types=1);

return [
    'seed_admin_username' => getenv('PLOOFOODLE_ADMIN_USER') ?: 'admin',
    'seed_admin_password' => getenv('PLOOFOODLE_ADMIN_PASS') ?: 'change-me-now',
    'allow_seed_login' => (getenv('PLOOFOODLE_ALLOW_SEED_LOGIN') ?: 'false') === 'true',
    'roles' => ['admin', 'viewer'],
];
