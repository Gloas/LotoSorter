<?php

declare(strict_types=1);

namespace LotoSorter\Sorting\Exception;

use RuntimeException;

final class SortFailedException extends RuntimeException
{
    public static function after(int $attempts, ?EmptyLotException $lastFailure): self
    {
        return new self(
            sprintf(
                'Trop de boucles… Aucune répartition trouvée en %d essais (dernier blocage : %s) '
                . 'Baissez les montants ou le nombre de parties, ou ajoutez des dons.',
                $attempts,
                $lastFailure?->getMessage() ?? 'inconnu',
            ),
            0,
            $lastFailure,
        );
    }
}
