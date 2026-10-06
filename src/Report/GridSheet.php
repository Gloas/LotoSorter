<?php

declare(strict_types=1);

namespace LotoSorter\Report;

use LotoSorter\Domain\Grid;
use LotoSorter\Domain\Lot;
use LotoSorter\Domain\SortResult;

/**
 * Lays out the grids as spreadsheet rows, each lot followed by a Google Sheets formula summing its gifts.
 */
final class GridSheet
{
    /**
     * @return list<list<string>>
     */
    public function rows(SortResult $result): array
    {
        $rows = [['GRILLES AUTOMATIQUES'], ['']];
        foreach ($result->grids as $grid) {
            array_push($rows, ...$this->gridRows($grid));
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function gridRows(Grid $grid): array
    {
        $audience = $grid->audience->label();
        $rows = [['Parties ' . $audience]];

        foreach ($grid->rounds as $round) {
            $rows[] = [sprintf('Partie %s n°%d', $audience, $round->number)];
            foreach ($round->lots as $lot) {
                array_push($rows, ...$this->lotRows($lot));
            }
            $rows[] = [''];
        }

        foreach ($grid->extraLots as $lot) {
            array_push($rows, ...$this->lotRows($lot));
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function lotRows(Lot $lot): array
    {
        $rows = [[$lot->type->label()]];
        foreach ($lot->donations() as $donation) {
            $rows[] = $donation->row;
        }

        $rows[] = ['Mise de', '', $this->sumFormula($lot->count()), sprintf('/ %d€ pour cette manche', $lot->target)];
        $rows[] = [''];

        return $rows;
    }

    private function sumFormula(int $rowsAbove): string
    {
        return sprintf(
            '=SUM(INDIRECT(ADDRESS(ROW()-1;COLUMN())):INDIRECT(ADDRESS(ROW()-%d;COLUMN())))',
            $rowsAbove,
        );
    }
}
