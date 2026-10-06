<?php

declare(strict_types=1);

namespace LotoSorter\Report;

use LotoSorter\Config\LotoConfig;
use LotoSorter\Domain\SortResult;

final class SortReport
{
    /**
     * @param list<string> $summary
     */
    public function __construct(
        public readonly SortResult $result,
        public readonly LotoConfig $config,
        public readonly array $summary,
        public readonly string $fullCsv,
        public readonly string $gridsCsv,
        public readonly string $unsortedCsv,
    ) {
    }
}
