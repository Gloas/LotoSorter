<?php

use PHPUnit\Framework\TestCase;

abstract class LotoTestCase extends TestCase
{
    protected function setUp(): void
    {
        SortedDonationsList::getInstance()->reset();
        (new ReflectionProperty(SortDonation::class, '_try_counter'))->setValue(null, 0);
    }


    protected static function donation(string $donor, string $price, string $label, string $target = 'Enfant'): array
    {
        return [$donor, 'Bénévole', $price, $label, $target];
    }


    protected static function rules(array $overrides = []): array
    {
        return array_merge(['round' => 1,
                            'quine' => 40,
                            'double_quine' => 80,
                            'carton' => 120,
                            'gros_lot' => 0,
                            'pas_de_bol' => 0],
                           $overrides);
    }


    /** Gifts that fill exactly one round of self::rules() */
    protected static function oneRoundOfGifts(): array
    {
        return [static::donation('Quine A', '10,00 €', 'Livre'),
                static::donation('Quine B', '10,00 €', 'Puzzle'),
                static::donation('Quine C', '10,00 €', 'Jeu de cartes'),
                static::donation('Quine D', '10,00 €', 'Coloriage'),
                static::donation('Double A', '20,00 €', 'Peluche'),
                static::donation('Double B', '20,00 €', 'Ballon'),
                static::donation('Double C', '20,00 €', 'Cinéma'),
                static::donation('Double D', '20,00 €', 'Bowling'),
                static::donation('Carton A', '60,00 €', 'Trottinette'),
                static::donation('Carton B', '60,00 €', 'Lego')];
    }


    /** Donations of each lot, by lot name ("Quine", "Double-quine", "Carton", "Gros lot"...) */
    protected static function lots(): array
    {
        $lots = [];
        $current = null;
        foreach (SortedDonationsList::getInstance()->asArray() as $row) {
            if (count($row) == 1) {
                // "Gros lot" and "Pas de bol" are followed by a "Carton" header
                if ('Carton' != $row[0] || ! in_array($current, ['Gros lot', 'Pas de bol']))
                    $current = $row[0];
                continue;
            }

            if ('Mise de' == $row[0]) {
                $current = null;
                continue;
            }

            if ($current !== null)
                $lots[$current][] = $row;
        }

        return $lots;
    }


    protected static function sum(array $donations): float
    {
        return array_sum(array_map(fn($donation) => (float) str_replace(',', '.', $donation[2]),
                                   $donations));
    }
}
