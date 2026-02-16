<?php

declare(strict_types=1);

namespace Ploofoodle\Repositories;

use PDO;

/**
 * Optional read-only adapter for alignment checks with existing Plonkadoodle data.
 */
final class PlonkadoodleReadRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function fetchPlanCatalogPreview(): array
    {
        // Intentionally read-only and optional; avoid coupling hard failures.
        return [];
    }
}
