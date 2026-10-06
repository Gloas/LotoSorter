<?php

declare(strict_types=1);

namespace LotoSorter\Sorting;

use LotoSorter\Config\SortingPolicy;
use LotoSorter\Sorting\Rule\AllRules;
use LotoSorter\Sorting\Rule\AudienceRule;
use LotoSorter\Sorting\Rule\DonationRule;
use LotoSorter\Sorting\Rule\ExemptDonorsRule;
use LotoSorter\Sorting\Rule\FitsTargetRule;
use LotoSorter\Sorting\Rule\LotShareRule;
use LotoSorter\Sorting\Rule\MinimumAmountRule;
use LotoSorter\Sorting\Rule\UniqueDonorRule;
use LotoSorter\Sorting\Rule\UniqueLabelRule;
use LotoSorter\Sorting\Rule\VoucherLimitRule;

final class RuleSetFactory
{
    public function create(SortingPolicy $policy): DonationRule
    {
        return new AllRules(
            new AudienceRule(),
            new MinimumAmountRule($policy->minAmount),
            new LotShareRule($policy),
            new ExemptDonorsRule($policy->allowedDonors, new AllRules(
                new UniqueDonorRule(),
                new UniqueLabelRule(),
                new VoucherLimitRule($policy->maxVouchersPerLot),
            )),
            new FitsTargetRule($policy),
        );
    }
}
