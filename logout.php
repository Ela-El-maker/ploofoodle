<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['_route'] = '/auth/logout';
} else {
    header('Location: /Pandipoodle/Ploofoodle/public/index.php?_route=/auth/login');
    exit;
}
require __DIR__ . '/public/index.php';
