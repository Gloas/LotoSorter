<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

final class UniqueLabelRule implements DonationRule
{
    public function allows(Donation $donation, Lot $lot): bool
    {
        return !$lot->hasLabel($donation->label);
    }
}
