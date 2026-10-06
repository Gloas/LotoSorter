<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

/**
 * Lets the gifts of some donors bypass the decorated rule.
 */
final class ExemptDonorsRule implements DonationRule
{
    /**
     * @param list<string> $donors
     */
    public function __construct(
        private readonly array $donors,
        private readonly DonationRule $rule,
    ) {
    }

    public function allows(Donation $donation, Lot $lot): bool
    {
        return in_array($donation->donor, $this->donors, true) || $this->rule->allows($donation, $lot);
    }
}
