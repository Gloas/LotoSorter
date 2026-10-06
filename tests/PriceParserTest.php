<?php

use PHPUnit\Framework\Attributes\DataProvider;

class PriceParserTest extends LotoTestCase
{
    public static function prices(): array
    {
        return ['euros with cents' => ['25,00 €', 25.0],
                'euros without cents' => ['37€', 37.0],
                'euros without symbol' => ['37', 37.0],
                'one decimal' => ['6,5 €', 6.5],
                'dot as decimal' => ['12.50', 12.5],
                'cents only' => ['0,50 €', 0.5],
                'big amount' => ['299,99 €', 299.99],
                'space thousands' => ['1 234,50 €', 1234.5],
                'dot thousands' => ['1.234', 1234.0],
                'empty' => ['', 0.0],
                'text' => ['à voir', 0.0]];
    }


    #[DataProvider('prices')]
    public function testParsePrice(string $raw, float $expected): void
    {
        $parser = new class extends SortDonationsForKid {
            public function __construct() {}
            public function parse(string $raw): float { return $this->_parsePrice($raw); }
        };

        $this->assertSame($expected, $parser->parse($raw));
    }
}
