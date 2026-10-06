<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

final class MinimumAmountRule implements DonationRule
{
    public function __construct(private readonly float $minAmount)
    {
    }

    public function allows(Donation $donation, Lot $lot): bool
    {
        return $donation->amount >= $this->minAmount;
    }
}
