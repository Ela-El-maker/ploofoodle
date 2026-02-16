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
        $response->redirect(\ploo_route_url('/auth/login'));
        return null;
    }
}
