<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

/**
 * Adult lots take "Adulte" and "Mix" gifts, kid lots take "Enfant" and "Mix" gifts.
 */
final class AudienceRule implements DonationRule
{
    public function allows(Donation $donation, Lot $lot): bool
    {
        return $lot->audience->canReceive($donation);
    }
}
