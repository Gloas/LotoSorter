<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Exception;

use LotoSorter\Domain\Lot;
use RuntimeException;

final class EmptyLotException extends RuntimeException
{
    public static function for(Lot $lot, ?int $round): self
    {
        return new self(null === $round
            ? sprintf('%s %s sans lot.', $lot->type->label(), $lot->audience->label())
            : sprintf('Partie %s n°%d, %s sans lot.', $lot->audience->label(), $round, $lot->type->label()));
    }
}
