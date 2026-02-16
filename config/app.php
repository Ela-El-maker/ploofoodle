<?php

declare(strict_types=1);

return [
    'app_name' => 'Ploofoodle',
    'session_key' => 'ploofoodle_auth',
    'csrf_key' => 'ploofoodle_csrf',
    'base_path' => '/Pandipoodle/Ploofoodle/public',
    'public_cache_max_age' => 3600,
    'public_cache_swr' => 86400,
    'default_platform' => 'android',
    'default_channel' => 'stable',
    'allowed_platforms' => ['android', 'ios', 'web'],
    'allowed_channels' => ['stable', 'beta', 'internal'],
];
