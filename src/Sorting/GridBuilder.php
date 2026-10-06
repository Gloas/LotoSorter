<?php

declare(strict_types=1);

namespace LotoSorter\Sorting;

use LotoSorter\Config\GameRules;
use LotoSorter\Domain\DonationPool;
use LotoSorter\Domain\Grid;
use LotoSorter\Domain\Lot;
use LotoSorter\Domain\LotType;
use LotoSorter\Domain\Round;
use LotoSorter\Sorting\Exception\EmptyLotException;

final class GridBuilder
{
    public function __construct(private readonly LotFiller $filler)
    {
    }

    /**
     * @throws EmptyLotException when a lot gets no gift at all
     */
    public function build(GameRules $game, DonationPool $pool): Grid
    {
        $rounds = [];
        for ($number = 1; $number <= $game->rounds; $number++) {
            $lots = [];
            foreach (LotType::roundTypes() as $type) {
                $lots[] = $this->fillLot($game, $type, $pool, $number);
            }
            $rounds[] = new Round($number, $lots);
        }

        $extraLots = [];
        foreach ($game->extraLotTypes() as $type) {
            $extraLots[] = $this->fillLot($game, $type, $pool, null);
        }

        return new Grid($game->audience, $rounds, $extraLots);
    }

    private function fillLot(GameRules $game, LotType $type, DonationPool $pool, ?int $round): Lot
    {
        $lot = new Lot($type, $game->target($type), $game->audience);
        $this->filler->fill($lot, $pool);

        if ($lot->isEmpty()) {
            throw EmptyLotException::for($lot, $round);
        }

        return $lot;
    }
}
