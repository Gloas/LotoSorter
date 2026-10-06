<?php

declare(strict_types=1);

namespace LotoSorter\Csv;

use RuntimeException;

final class CsvEncoder
{
    /**
     * @param list<list<string>> $rows
     */
    public function encode(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new RuntimeException('Impossible d\'écrire le CSV.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }
        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
