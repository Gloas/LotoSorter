<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Rule;

use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;

final class AllRules implements DonationRule
{
    /** @var list<DonationRule> */
    private readonly array $rules;

    public function __construct(DonationRule ...$rules)
    {
        $this->rules = array_values($rules);
    }

    public function allows(Donation $donation, Lot $lot): bool
    {
        foreach ($this->rules as $rule) {
            if (!$rule->allows($donation, $lot)) {
                return false;
            }
        }

        return true;
    }
}
