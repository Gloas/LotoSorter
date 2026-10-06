<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Support;

use LotoSorter\Sorting\Shuffler;

final class KeepOrderShuffler implements Shuffler
{
    public function shuffle(array $items): array
    {
        return $items;
    }
}
