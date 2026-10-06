<?php

declare(strict_types=1);

namespace LotoSorter\Sorting;

use LotoSorter\Config\SortingPolicy;
use LotoSorter\Domain\DonationPool;
use LotoSorter\Domain\Lot;
use LotoSorter\Sorting\Rule\DonationRule;

/**
 * Fills a lot greedily: walks the pool in order and takes each allowed donation until the lot is complete.
 */
final class LotFiller
{
    public function __construct(
        private readonly DonationRule $rule,
        private readonly SortingPolicy $policy,
    ) {
    }

    public function fill(Lot $lot, DonationPool $pool): void
    {
        foreach ($pool->all() as $key => $donation) {
            if (!$this->rule->allows($donation, $lot)) {
                continue;
            }

            $lot->add($pool->take($key));
            if ($this->isComplete($lot)) {
                return;
            }
        }
    }

    private function isComplete(Lot $lot): bool
    {
        return $lot->type->isSingleGift() || $lot->reachesTarget($this->policy->toleranceBelow);
    }
}
