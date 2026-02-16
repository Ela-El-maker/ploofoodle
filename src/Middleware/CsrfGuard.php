<?php

declare(strict_types=1);

namespace Ploofoodle\Middleware;

final class CsrfGuard
{
    public function validate(?string $token): bool
    {
        return \ploo_validate_csrf($token);
    }
}
