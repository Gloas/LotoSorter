<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

enum LotType: string
{
    case Quine = 'quine';
    case DoubleQuine = 'double_quine';
    case Carton = 'carton';
    case GrosLot = 'gros_lot';
    case PasDeBol = 'pas_de_bol';

    /**
     * @return list<self>
     */
    public static function roundTypes(): array
    {
        return [self::Quine, self::DoubleQuine, self::Carton];
    }

    /**
     * @return list<self>
     */
    public static function extraTypes(): array
    {
        return [self::GrosLot, self::PasDeBol];
    }

    public function label(): string
    {
        return match ($this) {
            self::Quine => 'Quine',
            self::DoubleQuine => 'Double-quine',
            self::Carton => 'Carton',
            self::GrosLot => 'Gros lot',
            self::PasDeBol => 'Pas de bol',
        };
    }

    public function isSingleGift(): bool
    {
        return $this === self::GrosLot;
    }
}
