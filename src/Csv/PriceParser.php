<?php

declare(strict_types=1);

namespace LotoSorter\Csv;

/**
 * Reads a spreadsheet amount: "37€", "25,00 €", "12.50", "1 234,50 €"...
 * A "," or "." followed by one or two digits is the decimal separator, any other one groups thousands.
 */
final class PriceParser
{
    public function parse(string $price): float
    {
        $price = (string) preg_replace('/[^\d,.]/', '', $price);

        if (1 === preg_match('/^(.*)[,.](\d{1,2})$/', $price, $matches)) {
            return (float) ($this->digits($matches[1]) . '.' . $matches[2]);
        }

        return (float) $this->digits($price);
    }

    private function digits(string $value): string
    {
        return (string) preg_replace('/\D/', '', $value);
    }
}
