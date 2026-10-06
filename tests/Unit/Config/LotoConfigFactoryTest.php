<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Config;

use LotoSorter\Config\InvalidConfigException;
use LotoSorter\Config\LotoConfigFactory;
use LotoSorter\Domain\Audience;
use LotoSorter\Domain\LotType;
use PHPUnit\Framework\TestCase;

final class LotoConfigFactoryTest extends TestCase
{
    private const ADULT = [
        'round' => '10',
        'quine' => '100',
        'double_quine' => '200',
        'carton' => '350',
        'gros_lot' => '770',
        'pas_de_bol' => '0',
    ];

    private LotoConfigFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new LotoConfigFactory();
    }

    public function testBuildsTheGamesInTheirOrder(): void
    {
        $config = $this->factory->fromArray(['kid' => ['round' => 4] + self::ADULT, 'adult' => self::ADULT]);

        self::assertSame([Audience::Kid, Audience::Adult], [$config->games[0]->audience, $config->games[1]->audience]);
        self::assertSame(4, $config->games[0]->rounds);
        self::assertSame(350, $config->games[1]->target(LotType::Carton));
        self::assertSame([LotType::GrosLot], $config->games[1]->extraLotTypes());
    }

    public function testUsesTheDefaultSortingPolicy(): void
    {
        $policy = $this->factory->fromArray(['adult' => self::ADULT])->policy;

        self::assertSame(5.0, $policy->minAmount);
        self::assertSame(300, $policy->maxAttempts);
        self::assertSame([], $policy->allowedDonors);
    }

    public function testReadsTheSortingSection(): void
    {
        $policy = $this->factory->fromArray([
            'adult' => self::ADULT,
            'sorting' => ['min_amount' => '2,5', 'max_attempts' => '50', 'allowed_donors' => "APE\n\n Tabac \nAPE"],
        ])->policy;

        self::assertSame(2.5, $policy->minAmount);
        self::assertSame(50, $policy->maxAttempts);
        self::assertSame(['APE', 'Tabac'], $policy->allowedDonors);
    }

    public function testSkipsAnAudienceThatPlaysNothing(): void
    {
        $config = $this->factory->fromArray(['adult' => self::ADULT, 'kid' => ['round' => '0']]);

        self::assertCount(1, $config->games);
    }

    public function testReportsEveryError(): void
    {
        try {
            $this->factory->fromArray([
                'adult' => ['round' => 'dix', 'quine' => '-5'] + self::ADULT,
                'kid' => ['round' => '2', 'quine' => '0', 'double_quine' => '50', 'carton' => '80'],
                'sorting' => ['quine_max_share' => '2'],
                'bonus' => [],
            ]);
            self::fail('An invalid configuration must be refused.');
        } catch (InvalidConfigException $exception) {
            self::assertSame(
                ['adult.round', 'adult.quine', 'kid.quine', 'bonus', 'sorting.quine_max_share'],
                array_keys($exception->errors),
            );
        }
    }

    public function testRequiresAGame(): void
    {
        $this->expectException(InvalidConfigException::class);

        $this->factory->fromArray(['sorting' => []]);
    }

    public function testToArrayGivesBackTheSameConfig(): void
    {
        $config = $this->factory->fromArray([
            'adult' => self::ADULT,
            'sorting' => ['allowed_donors' => ['APE de Valleiry']],
        ]);

        self::assertEquals($config, $this->factory->fromArray($this->factory->toArray($config)));
    }
}
