<?php

declare(strict_types=1);

namespace LotoSorter\Web\Action;

use LotoSorter\Web\WebSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Serves the example sheet, to import as a starting point in Google Sheets.
 */
final class ExampleCsvAction
{
    public function __construct(private readonly WebSettings $settings)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $csv = file_get_contents($this->settings->exampleCsvPath);
        if (false === $csv) {
            throw new RuntimeException('Exemple introuvable.');
        }

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="lots_loto_exemple.csv"');
    }
}
