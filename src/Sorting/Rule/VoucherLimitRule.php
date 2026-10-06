<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

final class VoucherLimitRule implements DonationRule
{
    public function __construct(private readonly int $maxVouchers)
    {
    }

    public function allows(Donation $donation, Lot $lot): bool
    {
        return !$donation->isVoucher() || $lot->voucherCount() < $this->maxVouchers;
    }
}
