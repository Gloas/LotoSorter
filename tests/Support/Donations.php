<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Support;

use LotoSorter\Config\GameRules;
use LotoSorter\Domain\Audience;
use LotoSorter\Domain\Donation;
use LotoSorter\Domain\LotType;

final class Donations
{
    public static function make(
        string $donor,
        float $amount,
        string $label,
        Audience $audience = Audience::Kid,
    ): Donation {
        $row = [$donor, 'Bénévole', number_format($amount, 2, ',', '') . ' €', $label, $audience->label()];

        return new Donation($donor, $amount, $label, $audience, $row);
    }

    /**
     * One round of kid lots: quine 40 €, double-quine 80 €, carton 120 €.
     *
     * @param array<value-of<LotType>, int> $targets
     */
    public static function kidGame(array $targets = [], int $rounds = 1): GameRules
    {
        return new GameRules(Audience::Kid, $rounds, $targets + ['quine' => 40, 'double_quine' => 80, 'carton' => 120]);
    }

    /**
     * Gifts that fill exactly one round of kidGame().
     *
     * @return list<Donation>
     */
    public static function oneRound(): array
    {
        return [
            self::make('Quine A', 10, 'Livre'),
            self::make('Quine B', 10, 'Puzzle'),
            self::make('Quine C', 10, 'Jeu de cartes'),
            self::make('Quine D', 10, 'Coloriage'),
            self::make('Double A', 20, 'Peluche'),
            self::make('Double B', 20, 'Ballon'),
            self::make('Double C', 20, 'Cinéma'),
            self::make('Double D', 20, 'Bowling'),
            self::make('Carton A', 60, 'Trottinette'),
            self::make('Carton B', 60, 'Lego'),
        ];
    }
}
