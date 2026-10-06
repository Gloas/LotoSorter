<?php

declare(strict_types=1);

namespace LotoSorter\Domain;

final class Donation
{
    /**
     * @param list<string> $row the original spreadsheet row, written back as-is in the results
     */
    public function __construct(
        public readonly string $donor,
        public readonly float $amount,
        public readonly string $label,
        public readonly ?Audience $audience,
        public readonly array $row,
    ) {
    }

    public function isVoucher(): bool
    {
        return 1 === preg_match('/\bbons?\b/iu', $this->label);
    }
}
