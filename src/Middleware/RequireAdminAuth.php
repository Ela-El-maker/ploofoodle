<?php

declare(strict_types=1);

namespace Ploofoodle\Middleware;

use Ploofoodle\Core\Response;

final class RequireAdminAuth
{
    public function handle(): ?array
    {
        $user = \ploo_current_user();
        if (is_array($user) && !empty($user['username'])) {
            return $user;
        }

        $response = new Response();
        $response->redirect('/Pandipoodle/Ploofoodle/public/index.php?_route=/auth/login');
        return null;
    }
}
