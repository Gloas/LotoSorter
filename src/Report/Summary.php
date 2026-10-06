<?php

declare(strict_types=1);

namespace LotoSorter\Report;

use DateTimeImmutable;
use LotoSorter\Config\GameRules;
use LotoSorter\Config\LotoConfig;
use LotoSorter\Domain\Audience;
use LotoSorter\Domain\SortResult;

final class Summary
{
    /**
     * @return list<string>
     */
    public function lines(SortResult $result, LotoConfig $config, DateTimeImmutable $date): array
    {
        $lines = [
            'OUTIL DE TRI DES DONS',
            'Date du : ' . $date->format('d/m/Y H:i:s'),
            'Nombre de boucle(s) : ' . $result->attempts,
            'Nombre de lots à trier : ' . $result->donationCount(),
            'Nombre de lots triés : ' . $result->sortedCount(),
            'Nombre de lots non triés : ' . $result->unsortedCount(),
            'Nombre de lots MIX non triés : ' . $result->unsortedCountFor(Audience::Mix),
        ];

        foreach ($config->games as $game) {
            $lines[] = sprintf(
                'Nombre de lots %s non triés : %d avec %s.',
                $game->audience->pluralLabel(),
                $result->unsortedCountFor($game->audience),
                $this->describe($game),
            );
        }

        $unknown = $result->unsortedCountFor(null);
        if ($unknown > 0) {
            $lines[] = sprintf('Nombre de lots sans public Adulte / Enfant / Mix : %d', $unknown);
        }

        return $lines;
    }

    private function describe(GameRules $game): string
    {
        $parts = [sprintf('%d parties', $game->rounds)];
        foreach ($game->lotTypes() as $type) {
            $parts[] = sprintf('%s à %d€', mb_strtolower($type->label()), $game->target($type));
        }
        $last = (string) array_pop($parts);

        return [] === $parts ? $last : implode(', ', $parts) . ' et ' . $last;
    }
}
