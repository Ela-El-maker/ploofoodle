<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

final class RolloutService
{
    public function isEligible(string $stableDeviceId, int $rolloutPercent): bool
    {
        $percent = max(0, min(100, $rolloutPercent));
        $bucket = hexdec(substr(hash('sha256', $stableDeviceId), 0, 8)) % 100;
        return $bucket < $percent;
    }
}
