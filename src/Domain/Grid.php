<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

/**
 * The rounds of one audience, plus its optional "Gros lot" and "Pas de bol".
 */
final class Grid
{
    /**
     * @param list<Round> $rounds
     * @param list<Lot> $extraLots
     */
    public function __construct(
        public readonly Audience $audience,
        public readonly array $rounds,
        public readonly array $extraLots,
    ) {
    }

    /**
     * @return list<Lot>
     */
    public function lots(): array
    {
        $lots = [];
        foreach ($this->rounds as $round) {
            array_push($lots, ...$round->lots);
        }

        return [...$lots, ...$this->extraLots];
    }

    public function donationCount(): int
    {
        return array_sum(array_map(static fn(Lot $lot): int => $lot->count(), $this->lots()));
    }
}
