<?php

declare(strict_types=1);

namespace LotoSorter\Sorting;

interface Shuffler
{
    /**
     * @template T
     *
     * @param list<T> $items
     *
     * @return list<T>
     */
    public function shuffle(array $items): array;
}
