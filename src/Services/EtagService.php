<?php

declare(strict_types=1);

namespace Ploofoodle\Services;

final class EtagService
{
    public function build(array $payload, string $updatedAt, int $schemaVersion): string
    {
        $stable = json_encode($this->sortRecursive($payload), JSON_UNESCAPED_SLASHES);
        return hash('sha256', $stable . '|' . $updatedAt . '|' . $schemaVersion);
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[$k] = $this->sortRecursive($v);
            }
        }

        if (array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }

        return $value;
    }
}
