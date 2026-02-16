<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Pandipoodle/Ploofoodle/public/index.php?_route=/admin/config');
    exit;
}
$_GET['_route'] = '/admin/config';
require __DIR__ . '/public/index.php';
