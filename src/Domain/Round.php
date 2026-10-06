<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

final class Round
{
    /**
     * @param list<Lot> $lots
     */
    public function __construct(
        public readonly int $number,
        public readonly array $lots,
    ) {
    }
}
