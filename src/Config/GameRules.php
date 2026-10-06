<?php

declare(strict_types=1);

namespace LotoSorter\Config;

use LotoSorter\Domain\Audience;
use LotoSorter\Domain\LotType;

/**
 * How many rounds an audience plays, and the target value of each kind of lot.
 */
final class GameRules
{
    /**
     * @param array<value-of<LotType>, int> $targets target value in euros, 0 = lot disabled
     */
    public function __construct(
        public readonly Audience $audience,
        public readonly int $rounds,
        private readonly array $targets,
    ) {
    }

    public function target(LotType $type): int
    {
        return $this->targets[$type->value] ?? 0;
    }

    /**
     * @return list<LotType> the lot types this audience plays: the round lots, then the enabled extra lots
     */
    public function lotTypes(): array
    {
        return [...($this->rounds > 0 ? LotType::roundTypes() : []), ...$this->extraLotTypes()];
    }

    /**
     * @return list<LotType>
     */
    public function extraLotTypes(): array
    {
        return array_values(array_filter(
            LotType::extraTypes(),
            fn(LotType $type): bool => $this->target($type) > 0,
        ));
    }
}
