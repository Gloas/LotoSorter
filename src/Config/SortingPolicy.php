<?php

declare(strict_types=1);

namespace LotoSorter\Config;

/**
 * Thresholds used to compose the lots.
 */
final class SortingPolicy
{
    /**
     * @param list<string> $allowedDonors donors whose gifts may share a lot (e.g. the association's own purchases)
     */
    public function __construct(
        public readonly float $minAmount = 5.0,
        public readonly float $quineMaxShare = 0.25,
        public readonly float $doubleQuineMaxShare = 0.30,
        public readonly float $cartonMinShare = 0.17,
        public readonly float $toleranceBelow = 0.01,
        public readonly float $toleranceAbove = 0.10,
        public readonly int $maxVouchersPerLot = 2,
        public readonly int $maxAttempts = 300,
        public readonly array $allowedDonors = [],
    ) {
    }
}
