<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

final class SortResult
{
    /**
     * @param list<Grid> $grids
     * @param list<Donation> $unsorted
     */
    public function __construct(
        public readonly array $grids,
        public readonly array $unsorted,
        public readonly int $attempts,
    ) {
    }

    public function sortedCount(): int
    {
        return array_sum(array_map(static fn(Grid $grid): int => $grid->donationCount(), $this->grids));
    }

    public function unsortedCount(): int
    {
        return count($this->unsorted);
    }

    public function donationCount(): int
    {
        return $this->sortedCount() + $this->unsortedCount();
    }

    public function unsortedCountFor(?Audience $audience): int
    {
        return count(array_filter(
            $this->unsorted,
            static fn(Donation $donation): bool => $donation->audience === $audience,
        ));
    }
}
