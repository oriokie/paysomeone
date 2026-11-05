<?php

namespace App\Support;

/**
 * The percent values are the ones stored for writer tiers on the current site.
 * The labels here are the professional names used on the new site.
 */
class WriterTiers
{
    public const TIERS = [
        2 => ['label' => 'Best available', 'percent' => 0, 'detail' => 'Best available'],
        3 => ['label' => 'ESL advanced', 'percent' => 25, 'detail' => 'High-rank professional writer. Adds 25%.'],
        4 => ['label' => 'ENL (English as a native language)', 'percent' => 30, 'detail' => 'Writers from the US, UK, Canada, or Australia. Adds 30%.'],
    ];

    public static function percent(int $id): int
    {
        return self::TIERS[$id]['percent'] ?? 0;
    }
}
