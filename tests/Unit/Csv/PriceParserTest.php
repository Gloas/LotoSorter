<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Csv;

use LotoSorter\Csv\PriceParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriceParserTest extends TestCase
{
    /**
     * @return array<string, array{string, float}>
     */
    public static function prices(): array
    {
        return [
            'euros with cents' => ['25,00 €', 25.0],
            'euros without cents' => ['37€', 37.0],
            'no currency' => ['37', 37.0],
            'one decimal' => ['6,5 €', 6.5],
            'dot as decimal separator' => ['12.50', 12.5],
            'cents only' => ['0,50 €', 0.5],
            'big amount' => ['299,99 €', 299.99],
            'space as thousands separator' => ['1 234,50 €', 1234.5],
            'dot as thousands separator' => ['1.234', 1234.0],
            'empty' => ['', 0.0],
            'text' => ['à voir', 0.0],
        ];
    }

    #[DataProvider('prices')]
    public function testParsesSpreadsheetAmounts(string $price, float $expected): void
    {
        self::assertSame($expected, (new PriceParser())->parse($price));
    }
}
