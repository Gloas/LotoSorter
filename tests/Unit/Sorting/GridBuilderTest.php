<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Sorting;

use LotoSorter\Config\GameRules;
use LotoSorter\Config\SortingPolicy;
use LotoSorter\Domain\Audience;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\DonationPool;
use LotoSorter\Domain\Grid;
use LotoSorter\Domain\Lot;
use LotoSorter\Sorting\Exception\EmptyLotException;
use LotoSorter\Sorting\GridBuilder;
use LotoSorter\Sorting\LotFiller;
use LotoSorter\Sorting\RuleSetFactory;
use LotoSorter\Tests\Support\Donations;
use PHPUnit\Framework\TestCase;

final class GridBuilderTest extends TestCase
{
    public function testFillsEveryLotOfARound(): void
    {
        $pool = new DonationPool(Donations::oneRound());

        $lots = $this->build(Donations::kidGame(), $pool)->rounds[0]->lots;

        self::assertSame([40.0, 80.0, 120.0], array_map(static fn(Lot $lot): float => $lot->total(), $lots));
        self::assertSame([], $pool->remaining());
    }

    public function testQuineSkipsGiftsAboveAQuarterOfItsTarget(): void
    {
        $grid = $this->build(Donations::kidGame(), $this->pool(Donations::make('Trop cher', 11, 'Montre')));

        self::assertNotContains('Trop cher', $this->donors($grid->rounds[0]->lots[0]));
    }

    public function testCartonStartsWithABigEnoughGift(): void
    {
        $gifts = Donations::oneRound();
        $gifts[] = Donations::make('Petit', 15, 'Stylo');

        $grid = $this->build(Donations::kidGame(), new DonationPool($gifts));

        self::assertSame(['Carton A', 'Carton B'], $this->donors($grid->rounds[0]->lots[2]));
    }

    public function testNeverPutsTheSameDonorTwiceInALot(): void
    {
        $grid = $this->build(Donations::kidGame(), $this->pool(
            Donations::make('Même commerce', 10, 'Bon pour une glace'),
            Donations::make('Même commerce', 10, 'Bon pour une crêpe'),
        ));

        foreach ($grid->lots() as $lot) {
            self::assertSame(array_unique($this->donors($lot)), $this->donors($lot));
        }
    }

    public function testNeverPutsTheSameLabelTwiceInALot(): void
    {
        $grid = $this->build(Donations::kidGame(), $this->pool(
            Donations::make('Commerce A', 10, 'Place de cinéma'),
            Donations::make('Commerce B', 10, 'Place de cinéma'),
        ));

        foreach ($grid->lots() as $lot) {
            $labels = array_map(static fn(Donation $donation): string => $donation->label, $lot->donations());
            self::assertSame(array_unique($labels), $labels);
        }
    }

    public function testAllowedDonorsMayAppearSeveralTimesInALot(): void
    {
        $grid = $this->build(
            Donations::kidGame(),
            $this->pool(Donations::make('APE', 10, 'Achat'), Donations::make('APE', 10, 'Achat')),
            new SortingPolicy(allowedDonors: ['APE']),
        );

        self::assertSame(['APE', 'APE'], array_slice($this->donors($grid->rounds[0]->lots[0]), 0, 2));
    }

    public function testLimitsTheVouchersInALot(): void
    {
        $grid = $this->build(Donations::kidGame(), $this->pool(
            Donations::make('Cinéma', 10, 'Bon pour une séance'),
            Donations::make('Bowling', 10, 'Bon pour une partie'),
            Donations::make('Piscine', 10, 'bon d\'entrée'),
        ));

        self::assertSame(2, $grid->rounds[0]->lots[0]->voucherCount());
    }

    public function testIgnoresGiftsUnderTheMinimumAmount(): void
    {
        $cheap = Donations::make('Tabac', 4.9, 'Crayon');
        $pool = $this->pool($cheap);

        $this->build(Donations::kidGame(), $pool);

        self::assertSame([$cheap], $pool->remaining());
    }

    public function testKidLotsTakeKidAndMixGiftsOnly(): void
    {
        $gifts = Donations::oneRound();
        $gifts[0] = Donations::make('Mix', 10, 'Livre', Audience::Mix);
        $adult = Donations::make('Cave', 10, 'Vin', Audience::Adult);
        $pool = new DonationPool([$adult, ...$gifts]);

        $grid = $this->build(Donations::kidGame(), $pool);

        self::assertContains('Mix', $this->donors($grid->rounds[0]->lots[0]));
        self::assertSame([$adult], $pool->remaining());
    }

    public function testKeepsALotThatIsOnlyPartlyFilled(): void
    {
        $gifts = Donations::oneRound();
        unset($gifts[0]);

        $grid = $this->build(Donations::kidGame(), new DonationPool(array_values($gifts)));

        self::assertSame(30.0, $grid->rounds[0]->lots[0]->total());
        self::assertFalse($grid->rounds[0]->lots[0]->reachesTarget(0.01));
    }

    public function testFailsWhenALotGetsNoGiftAtAll(): void
    {
        $this->expectException(EmptyLotException::class);
        $this->expectExceptionMessage('Partie Enfant n°1, Double-quine sans lot.');

        $this->build(Donations::kidGame(), new DonationPool(array_slice(Donations::oneRound(), 0, 4)));
    }

    public function testGrosLotIsASingleGiftWorthItsTarget(): void
    {
        $grid = $this->build(Donations::kidGame(['gros_lot' => 300]), $this->pool(
            Donations::make('Petit cadeau', 150, 'Jeu vidéo'),
            Donations::make('APE', 299.99, 'Console'),
        ));

        self::assertSame(['APE'], $this->donors($grid->extraLots[0]));
    }

    private function build(GameRules $game, DonationPool $pool, SortingPolicy $policy = new SortingPolicy()): Grid
    {
        $builder = new GridBuilder(new LotFiller((new RuleSetFactory())->create($policy), $policy));

        return $builder->build($game, $pool);
    }

    private function pool(Donation ...$first): DonationPool
    {
        return new DonationPool([...array_values($first), ...Donations::oneRound()]);
    }

    /**
     * @return list<string>
     */
    private function donors(Lot $lot): array
    {
        return array_map(static fn(Donation $donation): string => $donation->donor, $lot->donations());
    }
}
