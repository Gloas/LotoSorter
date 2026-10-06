<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Config\SortingPolicy;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;
use LotoSorter\Domain\LotType;

/**
 * Keeps small gifts for the quines and big gifts for the cartons:
 * a quine or double-quine gift is capped at a share of the target,
 * any other lot starts with a gift worth at least a share of the target.
 */
final class LotShareRule implements DonationRule
{
    public function __construct(private readonly SortingPolicy $policy)
    {
    }

    public function allows(Donation $donation, Lot $lot): bool
    {
        return match ($lot->type) {
            LotType::Quine => $donation->amount <= $lot->target * $this->policy->quineMaxShare,
            LotType::DoubleQuine => $donation->amount <= $lot->target * $this->policy->doubleQuineMaxShare,
            default => !$lot->isEmpty() || $donation->amount >= $lot->target * $this->policy->cartonMinShare,
        };
    }
}
