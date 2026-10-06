<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Config\SortingPolicy;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

/**
 * A lot may exceed its target by the upper tolerance; a single-gift lot needs one gift worth its target.
 */
final class FitsTargetRule implements DonationRule
{
    public function __construct(private readonly SortingPolicy $policy)
    {
    }

    public function allows(Donation $donation, Lot $lot): bool
    {
        $total = $lot->total() + $donation->amount;

        return $lot->type->isSingleGift()
            ? $total >= $lot->target * (1 - $this->policy->toleranceBelow)
            : $total < $lot->target * (1 + $this->policy->toleranceAbove);
    }
}
