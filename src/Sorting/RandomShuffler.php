<?php

declare(strict_types=1);

namespace LotoSorter\Sorting;

use Random\Randomizer;

final class RandomShuffler implements Shuffler
{
    public function __construct(private readonly Randomizer $randomizer = new Randomizer())
    {
    }

    public function shuffle(array $items): array
    {
        return $this->randomizer->shuffleArray($items);
    }
}
