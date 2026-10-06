<?php

declare(strict_types=1);

namespace LotoSorter\Csv;

use LotoSorter\Domain\Audience;
use LotoSorter\Domain\Donation;
use RuntimeException;

/**
 * Reads the donations of a Google Sheet CSV export: columns A (donor), C (amount), D (label), E (audience).
 * Rows without an amount (titles, prospects, refusals) are skipped.
 */
final class DonationCsvReader
{
    private const DONOR = 0;
    private const AMOUNT = 2;
    private const LABEL = 3;
    private const AUDIENCE = 4;
    private const UTF8_BOM = "\u{FEFF}";

    public function __construct(private readonly PriceParser $prices)
    {
    }

    /**
     * @return list<Donation>
     */
    public function read(string $csv): array
    {
        $stream = $this->stream($csv);
        $donations = [];

        while (false !== ($cells = fgetcsv($stream, null, ',', '"', ''))) {
            $donation = $this->donation(array_map(static fn(?string $cell): string => (string) $cell, $cells));
            if (null !== $donation) {
                $donations[] = $donation;
            }
        }
        fclose($stream);

        return $donations;
    }

    /**
     * @param list<string> $row
     */
    private function donation(array $row): ?Donation
    {
        $amount = $this->prices->parse($row[self::AMOUNT] ?? '');
        if ($amount <= 0) {
            return null;
        }

        return new Donation(
            donor: trim($row[self::DONOR] ?? ''),
            amount: $amount,
            label: trim($row[self::LABEL] ?? ''),
            audience: Audience::fromLabel($row[self::AUDIENCE] ?? ''),
            row: $row,
        );
    }

    /**
     * @return resource
     */
    private function stream(string $csv)
    {
        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new RuntimeException('Impossible de lire le CSV.');
        }

        $csv = str_starts_with($csv, self::UTF8_BOM) ? substr($csv, strlen(self::UTF8_BOM)) : $csv;
        fwrite($stream, $csv);
        rewind($stream);

        return $stream;
    }
}
