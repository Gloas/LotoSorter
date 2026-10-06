<?php

declare(strict_types=1);

namespace LotoSorter\Web;

final class WebSettings
{
    public function __construct(
        public readonly string $configPath,
        public readonly string $exampleConfigPath,
        public readonly string $exampleCsvPath,
        public readonly int $maxFiles = 20,
        public readonly int $maxFileBytes = 2_000_000,
        public readonly bool $debug = false,
    ) {
    }
}
