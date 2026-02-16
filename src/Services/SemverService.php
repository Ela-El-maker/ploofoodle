<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

final class SemverService
{
    public function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    public function assertValid(string $version): string
    {
        $normalized = $this->normalize($version);
        if (!preg_match('/^\d+\.\d+\.\d+(?:[+-][0-9A-Za-z\-.]+)?$/', $normalized)) {
            throw new \InvalidArgumentException('Invalid semver: ' . $version);
        }
        return $normalized;
    }

    public function compare(string $left, string $right): int
    {
        return version_compare($this->normalize($left), $this->normalize($right));
    }
}
