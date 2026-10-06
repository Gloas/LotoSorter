<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

final class UniqueDonorRule implements DonationRule
{
    public function allows(Donation $donation, Lot $lot): bool
    {
        return !$lot->hasDonor($donation->donor);
    }
}
