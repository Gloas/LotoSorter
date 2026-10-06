<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

enum Audience: string
{
    case Adult = 'adult';
    case Kid = 'kid';
    case Mix = 'mix';

    public static function fromLabel(string $label): ?self
    {
        return match (mb_strtolower(trim($label))) {
            'adulte' => self::Adult,
            'enfant' => self::Kid,
            'mix' => self::Mix,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Adult => 'Adulte',
            self::Kid => 'Enfant',
            self::Mix => 'Mix',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Adult => 'ADULTES',
            self::Kid => 'ENFANTS',
            self::Mix => 'MIX',
        };
    }

    public function canReceive(Donation $donation): bool
    {
        return $donation->audience === $this || $donation->audience === self::Mix;
    }
}
