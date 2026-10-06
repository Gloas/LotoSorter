<?php

declare(strict_types=1);

namespace LotoSorter\Report;

use DateTimeImmutable;
use LotoSorter\Config\LotoConfig;
use LotoSorter\Csv\CsvEncoder;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\SortResult;

final class ReportBuilder
{
    public function __construct(
        private readonly Summary $summary,
        private readonly GridSheet $gridSheet,
        private readonly CsvEncoder $csv,
    ) {
    }

    public function build(SortResult $result, LotoConfig $config, DateTimeImmutable $date): SortReport
    {
        $summary = $this->summary->lines($result, $config, $date);
        $grids = $this->gridSheet->rows($result);
        $unsorted = array_map(static fn(Donation $donation): array => $donation->row, $result->unsorted);

        return new SortReport(
            result: $result,
            config: $config,
            summary: $summary,
            fullCsv: $this->csv->encode([
                ...array_map(static fn(string $line): array => [$line], $summary),
                [''],
                ...$unsorted,
                [''],
                ...$grids,
            ]),
            gridsCsv: $this->csv->encode($grids),
            unsortedCsv: $this->csv->encode([['RESTE DES DONS NON TRIÉS'], ...$unsorted]),
        );
    }
}
