<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Csv;

use LotoSorter\Csv\DonationCsvReader;
use LotoSorter\Csv\PriceParser;
use LotoSorter\Domain\Audience;
use LotoSorter\Domain\Donation;
use PHPUnit\Framework\TestCase;

final class DonationCsvReaderTest extends TestCase
{
    private DonationCsvReader $reader;

    protected function setUp(): void
    {
        $this->reader = new DonationCsvReader(new PriceParser());
    }

    public function testReadsDonorAmountLabelAndAudience(): void
    {
        $donations = $this->reader->read(
            "COMMERCES,Démarcheurs,Montant du DON,Commentaire don,ENFANT / ADULTE / MIX,Ville\n"
            . "Cave du Salève,Bruno,\"11,50 €\",Bouteille de vin,Adulte,Archamps\n",
        );

        self::assertCount(1, $donations);
        self::assertSame('Cave du Salève', $donations[0]->donor);
        self::assertSame(11.5, $donations[0]->amount);
        self::assertSame('Bouteille de vin', $donations[0]->label);
        self::assertSame(Audience::Adult, $donations[0]->audience);
        self::assertSame(
            ['Cave du Salève', 'Bruno', '11,50 €', 'Bouteille de vin', 'Adulte', 'Archamps'],
            $donations[0]->row,
        );
    }

    public function testSkipsRowsWithoutAmount(): void
    {
        $donations = $this->reader->read(
            "COMMERCES,,Montant du DON,,\n"
            . "Garage Dupont,Bruno,,,\n"
            . "\n"
            . "Glacier,Chloé,6€,Glaces,enfant \n",
        );

        self::assertSame(['Glacier'], array_map(static fn(Donation $donation): string => $donation->donor, $donations));
        self::assertSame(Audience::Kid, $donations[0]->audience);
    }

    public function testKeepsGiftsWithAnUnknownAudience(): void
    {
        $donations = $this->reader->read("Tabac,Alice,8€,Magazine,Tous\n");

        self::assertNull($donations[0]->audience);
    }

    public function testReadsCellsSpanningSeveralLines(): void
    {
        $donations = $this->reader->read("\u{FEFF}Casa,Ines,\"40,00 €\",\"Deux plats\net des outils\",Mix\n");

        self::assertCount(1, $donations);
        self::assertSame('Casa', $donations[0]->donor);
        self::assertSame("Deux plats\net des outils", $donations[0]->label);
    }
}
