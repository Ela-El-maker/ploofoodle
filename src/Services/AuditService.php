<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

use Ploofoodle\Repositories\AuditLogRepository;

final class AuditService
{
    public function __construct(private AuditLogRepository $repository)
    {
    }

    public function log(array $entry): void
    {
        $this->repository->append($entry);
    }
}
