<?php

declare(strict_types=1);

namespace LotoSorter\Sorting;

use LotoSorter\Config\LotoConfig;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\DonationPool;
use LotoSorter\Domain\SortResult;
use LotoSorter\Sorting\Exception\EmptyLotException;
use LotoSorter\Sorting\Exception\SortFailedException;

/**
 * Shuffles the donations and builds every grid; starts over while a lot stays empty.
 */
final class DonationSorter
{
    public function __construct(
        private readonly Shuffler $shuffler,
        private readonly RuleSetFactory $rules,
    ) {
    }

    /**
     * @param list<Donation> $donations
     *
     * @throws SortFailedException
     */
    public function sort(array $donations, LotoConfig $config): SortResult
    {
        $builder = new GridBuilder(new LotFiller($this->rules->create($config->policy), $config->policy));
        $lastFailure = null;

        for ($attempt = 1; $attempt <= $config->policy->maxAttempts; $attempt++) {
            try {
                return $this->attempt($builder, $donations, $config, $attempt);
            } catch (EmptyLotException $failure) {
                $lastFailure = $failure;
            }
        }

        throw SortFailedException::after($config->policy->maxAttempts, $lastFailure);
    }

    /**
     * @param list<Donation> $donations
     *
     * @throws EmptyLotException
     */
    private function attempt(GridBuilder $builder, array $donations, LotoConfig $config, int $attempt): SortResult
    {
        $pool = new DonationPool($this->shuffler->shuffle($donations));
        $grids = [];
        foreach ($config->games as $game) {
            $grids[] = $builder->build($game, $pool);
        }

        return new SortResult($grids, $pool->remaining(), $attempt);
    }
}
