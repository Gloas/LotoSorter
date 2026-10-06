<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

/**
 * Decides whether a donation may be added to a lot.
 */
interface DonationRule
{
    public function allows(Donation $donation, Lot $lot): bool;
}
