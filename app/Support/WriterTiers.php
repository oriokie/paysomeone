<?php

namespace App\Support;

/**
 * The percent values are the ones stored for writer tiers on the current site.
 * The labels here are the professional names used on the new site.
 */
class WriterTiers
{
    public const TIERS = [
        2 => ['label' => 'Best available', 'short' => 'Best available', 'percent' => 0, 'detail' => 'We match a writer to the brief.'],
        3 => ['label' => 'ESL advanced', 'short' => 'Senior', 'percent' => 25, 'detail' => 'A high-ranked writer. Adds 25%.'],
        4 => ['label' => 'ENL (English as a native language)', 'short' => 'Native', 'percent' => 30, 'detail' => 'A writer from the US, UK, Canada, or Australia. Adds 30%.'],
    ];

    public static function percent(int $id): int
    {
        return self::TIERS[$id]['percent'] ?? 0;
    }
}
