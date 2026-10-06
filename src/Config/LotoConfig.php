<?php

declare(strict_types=1);

namespace LotoSorter\Config;

final class LotoConfig
{
    /**
     * @param list<GameRules> $games in the order they are sorted
     */
    public function __construct(
        public readonly array $games,
        public readonly SortingPolicy $policy = new SortingPolicy(),
    ) {
    }
}
