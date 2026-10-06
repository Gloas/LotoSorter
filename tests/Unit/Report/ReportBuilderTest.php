<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Report;

use DateTimeImmutable;
use LotoSorter\Config\LotoConfig;
use LotoSorter\Csv\CsvEncoder;
use LotoSorter\Domain\Audience;
use LotoSorter\Report\GridSheet;
use LotoSorter\Report\ReportBuilder;
use LotoSorter\Report\SortReport;
use LotoSorter\Report\Summary;
use LotoSorter\Sorting\DonationSorter;
use LotoSorter\Sorting\RuleSetFactory;
use LotoSorter\Tests\Support\Donations;
use LotoSorter\Tests\Support\KeepOrderShuffler;
use PHPUnit\Framework\TestCase;

final class ReportBuilderTest extends TestCase
{
    private SortReport $report;

    protected function setUp(): void
    {
        $config = new LotoConfig([Donations::kidGame()]);
        $donations = [...Donations::oneRound(), Donations::make('Cave', 12, 'Vin', Audience::Adult)];
        $result = (new DonationSorter(new KeepOrderShuffler(), new RuleSetFactory()))->sort($donations, $config);

        $builder = new ReportBuilder(new Summary(), new GridSheet(), new CsvEncoder());
        $this->report = $builder->build($result, $config, new DateTimeImmutable('2026-01-17 20:30:00'));
    }

    public function testSummarisesTheSort(): void
    {
        self::assertSame([
            'OUTIL DE TRI DES DONS',
            'Date du : 17/01/2026 20:30:00',
            'Nombre de boucle(s) : 1',
            'Nombre de lots à trier : 11',
            'Nombre de lots triés : 10',
            'Nombre de lots non triés : 1',
            'Nombre de lots MIX non triés : 0',
            'Nombre de lots ENFANTS non triés : 0 avec 1 parties, quine à 40€, double-quine à 80€ et carton à 120€.',
        ], $this->report->summary);
    }

    public function testLaysOutTheGridsWithASumFormulaPerLot(): void
    {
        $rows = $this->rows($this->report->gridsCsv);

        self::assertSame(['GRILLES AUTOMATIQUES'], $rows[0]);
        self::assertSame(['Parties Enfant'], $rows[2]);
        self::assertSame(['Partie Enfant n°1'], $rows[3]);
        self::assertSame(['Quine'], $rows[4]);
        self::assertSame('Quine A', $rows[5][0]);
        self::assertSame([
            'Mise de',
            '',
            '=SUM(INDIRECT(ADDRESS(ROW()-1;COLUMN())):INDIRECT(ADDRESS(ROW()-4;COLUMN())))',
            '/ 40€ pour cette manche',
        ], $rows[9]);
    }

    public function testListsTheUnsortedGifts(): void
    {
        self::assertSame(
            [['RESTE DES DONS NON TRIÉS'], ['Cave', 'Bénévole', '12,00 €', 'Vin', 'Adulte']],
            $this->rows($this->report->unsortedCsv),
        );
    }

    public function testFullReportHasTheSummaryThenTheUnsortedGiftsThenTheGrids(): void
    {
        $rows = $this->rows($this->report->fullCsv);

        self::assertSame(['OUTIL DE TRI DES DONS'], $rows[0]);
        self::assertSame('Cave', $rows[9][0]);
        self::assertSame(['GRILLES AUTOMATIQUES'], $rows[11]);
    }

    /**
     * @return list<list<string>>
     */
    private function rows(string $csv): array
    {
        $lines = explode("\n", trim($csv));

        return array_map(
            static fn(string $line): array => array_map('strval', str_getcsv($line, ',', '"', '')),
            $lines,
        );
    }
}
