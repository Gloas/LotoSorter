<?php

class SortDonationsTest extends LotoTestCase
{
    public function testFillsEveryLotOfARound(): void
    {
        $sort = new SortDonationsForKid(static::rules(), static::oneRoundOfGifts());

        $this->assertTrue($sort->isValid());
        $lots = static::lots();
        $this->assertSame(40.0, static::sum($lots['Quine']));
        $this->assertSame(80.0, static::sum($lots['Double-quine']));
        $this->assertSame(120.0, static::sum($lots['Carton']));
        $this->assertSame([], $sort->getDonationsList());
    }


    public function testAddsATotalFormulaAfterEachLot(): void
    {
        new SortDonationsForKid(static::rules(), static::oneRoundOfGifts());

        $totals = array_values(array_filter(SortedDonationsList::getInstance()->asArray(),
                                            fn($row) => 'Mise de' == $row[0]));
        $this->assertCount(3, $totals);
        $this->assertStringContainsString('ROW()-4', $totals[0][2]);
        $this->assertSame('/ 40€ pour cette manche', $totals[0][3]);
    }


    public function testQuineSkipsGiftsAboveAQuarterOfItsTarget(): void
    {
        $gifts = static::oneRoundOfGifts();
        array_unshift($gifts, static::donation('Trop cher', '11,00 €', 'Montre'));

        new SortDonationsForKid(static::rules(), $gifts);

        $this->assertNotContains('Trop cher', array_column(static::lots()['Quine'], 0));
    }


    public function testSameDonorIsNeverTwiceInALot(): void
    {
        $gifts = static::oneRoundOfGifts();
        array_unshift($gifts,
                      static::donation('Même commerce', '10,00 €', 'Bon pour une glace'),
                      static::donation('Même commerce', '10,00 €', 'Bon pour une crêpe'));

        new SortDonationsForKid(static::rules(), $gifts);

        foreach (static::lots() as $name => $donations) {
            $donors = array_column($donations, 0);
            $this->assertSame(array_unique($donors), $donors, $name);
        }
    }


    public function testSameLabelIsNeverTwiceInALot(): void
    {
        $gifts = static::oneRoundOfGifts();
        array_unshift($gifts,
                      static::donation('Commerce A', '10,00 €', 'Place de cinéma'),
                      static::donation('Commerce B', '10,00 €', 'Place de cinéma'));

        new SortDonationsForKid(static::rules(), $gifts);

        foreach (static::lots() as $name => $donations) {
            $labels = array_column($donations, 3);
            $this->assertSame(array_unique($labels), $labels, $name);
        }
    }


    public function testAllowedDonorCanBeTwiceInALot(): void
    {
        $gifts = static::oneRoundOfGifts();
        array_unshift($gifts,
                      static::donation('APE de Valleiry', '10,00 €', 'Achat 1'),
                      static::donation('APE de Valleiry', '10,00 €', 'Achat 2'));

        new SortDonationsForKid(static::rules(), $gifts);

        $this->assertSame(['APE de Valleiry', 'APE de Valleiry'],
                          array_slice(array_column(static::lots()['Quine'], 0), 0, 2));
    }


    public function testIgnoresGiftsUnderFiveEuros(): void
    {
        $gifts = static::oneRoundOfGifts();
        $cheap = static::donation('Tabac', '4,90 €', 'Crayon');
        array_unshift($gifts, $cheap);

        $sort = new SortDonationsForKid(static::rules(), $gifts);

        $this->assertTrue($sort->isValid());
        $this->assertSame([$cheap], array_values($sort->getDonationsList()));
    }


    public function testKidRoundsUseKidAndMixGiftsOnly(): void
    {
        $gifts = static::oneRoundOfGifts();
        $gifts[0][4] = 'Mix';
        $adult = static::donation('Cave', '10,00 €', 'Vin', 'Adulte');
        array_unshift($gifts, $adult);

        $sort = new SortDonationsForKid(static::rules(), $gifts);

        $this->assertTrue($sort->isValid());
        $this->assertContains('Quine A', array_column(static::lots()['Quine'], 0));
        $this->assertSame([$adult], array_values($sort->getDonationsList()));
    }


    public function testAdultRoundsIgnoreKidGifts(): void
    {
        $sort = new SortDonationsForAdult(static::rules(), static::oneRoundOfGifts());

        $this->assertFalse($sort->isValid());
        $this->assertCount(10, $sort->getDonationsList());
    }


    public function testKeepsALotThatIsOnlyPartlyFilled(): void
    {
        $gifts = static::oneRoundOfGifts();
        unset($gifts[0]);

        $sort = new SortDonationsForKid(static::rules(), $gifts);

        $this->assertTrue($sort->isValid());
        $this->assertSame(30.0, static::sum(static::lots()['Quine']));
    }


    public function testIsInvalidWhenALotGetsNoGiftAtAll(): void
    {
        $sort = new SortDonationsForKid(static::rules(),
                                        array_slice(static::oneRoundOfGifts(), 0, 4));

        $this->assertFalse($sort->isValid());
        $this->assertContains(['Partie Enfant n°1, double quine sans lot'],
                              SortedDonationsList::getInstance()->asArray());
    }


    public function testGrosLotIsASingleGiftWorthItsTarget(): void
    {
        $gifts = static::oneRoundOfGifts();
        $gifts[] = static::donation('Petit cadeau', '50,00 €', 'Jeu vidéo');
        $gifts[] = static::donation('APE de Valleiry', '299,99 €', 'Console');

        $sort = new SortDonationsForKid(static::rules(['gros_lot' => 300]), $gifts);

        $this->assertTrue($sort->isValid());
        $this->assertSame(['APE de Valleiry'], array_column(static::lots()['Gros lot'], 0));
    }
}
