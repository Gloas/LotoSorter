<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Sorting;

use LotoSorter\Config\IniConfigLoader;
use LotoSorter\Config\LotoConfig;
use LotoSorter\Config\LotoConfigFactory;
use LotoSorter\Config\SortingPolicy;
use LotoSorter\Csv\DonationCsvReader;
use LotoSorter\Csv\PriceParser;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\Lot;
use LotoSorter\Sorting\DonationSorter;
use LotoSorter\Sorting\Exception\SortFailedException;
use LotoSorter\Sorting\RandomShuffler;
use LotoSorter\Sorting\RuleSetFactory;
use LotoSorter\Tests\Support\Donations;
use LotoSorter\Tests\Support\KeepOrderShuffler;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class DonationSorterTest extends TestCase
{
    private const EXAMPLES = __DIR__ . '/../../../examples/';

    public function testSortsAtTheFirstAttemptWhenTheGiftsFit(): void
    {
        $sorter = new DonationSorter(new KeepOrderShuffler(), new RuleSetFactory());

        $result = $sorter->sort(Donations::oneRound(), new LotoConfig([Donations::kidGame()]));

        self::assertSame(1, $result->attempts);
        self::assertSame(10, $result->sortedCount());
        self::assertSame(0, $result->unsortedCount());
    }

    public function testGivesUpAfterTheMaximumAttempts(): void
    {
        $sorter = new DonationSorter(new KeepOrderShuffler(), new RuleSetFactory());

        $this->expectException(SortFailedException::class);
        $this->expectExceptionMessage('Aucune répartition trouvée en 3 essais');

        $sorter->sort(
            array_slice(Donations::oneRound(), 0, 4),
            new LotoConfig([Donations::kidGame()], new SortingPolicy(maxAttempts: 3)),
        );
    }

    public function testPlacesEveryGiftOfTheExampleAtMostOnce(): void
    {
        $donations = (new DonationCsvReader(new PriceParser()))->read(
            (string) file_get_contents(self::EXAMPLES . 'lots_loto_exemple.csv'),
        );
        $config = (new IniConfigLoader(new LotoConfigFactory()))->load(self::EXAMPLES . 'loto_config.exemple.ini');
        $sorter = new DonationSorter(new RandomShuffler(new Randomizer(new Mt19937(42))), new RuleSetFactory());

        $result = $sorter->sort($donations, $config);

        $placed = [];
        foreach ($result->grids as $grid) {
            foreach ($grid->lots() as $lot) {
                self::assertFalse($lot->isEmpty());
                array_push($placed, ...$lot->donations());
            }
        }
        self::assertEqualsCanonicalizing(
            array_map(spl_object_id(...), $donations),
            array_map(spl_object_id(...), [...$placed, ...$result->unsorted]),
        );
        self::assertSame(count($donations), $result->donationCount());
    }
}
