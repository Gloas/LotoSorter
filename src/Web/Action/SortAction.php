<?php

declare(strict_types=1);

namespace LotoSorter\Web\Action;

use LotoSorter\Application\SortDonations;
use LotoSorter\Config\InvalidConfigException;
use LotoSorter\Config\LotoConfig;
use LotoSorter\Config\LotoConfigFactory;
use LotoSorter\Report\SortReport;
use LotoSorter\Sorting\Exception\SortFailedException;
use LotoSorter\Web\Form\FormPage;
use LotoSorter\Web\Form\InvalidUploadException;
use LotoSorter\Web\Form\UploadedDonations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class SortAction
{
    private const UNPROCESSABLE = 422;

    public function __construct(
        private readonly UploadedDonations $uploads,
        private readonly LotoConfigFactory $configs,
        private readonly SortDonations $sortDonations,
        private readonly FormPage $page,
        private readonly Twig $twig,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $values = is_array($body['config'] ?? null) ? $body['config'] : [];
        $errors = [];

        try {
            $csv = $this->uploads->read($request->getUploadedFiles()['donations'] ?? []);
        } catch (InvalidUploadException $exception) {
            $csv = null;
            $errors['donations'] = $exception->getMessage();
        }

        try {
            $config = $this->configs->fromArray($values);
        } catch (InvalidConfigException $exception) {
            $config = null;
            $errors += $exception->errors;
        }

        if (null === $csv || null === $config) {
            return $this->page->render($response->withStatus(self::UNPROCESSABLE), $values, $errors);
        }

        return $this->sort($response, $csv, $config, $values);
    }

    /**
     * @param array<mixed> $values
     */
    private function sort(
        ResponseInterface $response,
        string $csv,
        LotoConfig $config,
        array $values,
    ): ResponseInterface {
        try {
            $report = $this->sortDonations->execute($csv, $config);
        } catch (SortFailedException $exception) {
            return $this->page->render(
                $response->withStatus(self::UNPROCESSABLE),
                $values,
                ['sort' => $exception->getMessage()],
            );
        }

        return $this->twig->render($response, 'result.html.twig', [
            'report' => $report,
            'downloads' => $this->downloads($report),
        ]);
    }

    /**
     * @return list<array{name: string, title: string, url: string}>
     */
    private function downloads(SortReport $report): array
    {
        $files = [
            ['auto_sort_loto_donations.csv', 'Tout en un (à importer)', $report->fullCsv],
            ['sorted_donations.csv', 'Grilles seules', $report->gridsCsv],
            ['not_sorted_donations.csv', 'Dons non triés', $report->unsortedCsv],
        ];

        return array_map(static fn(array $file): array => [
            'name' => $file[0],
            'title' => $file[1],
            'url' => 'data:text/csv;charset=utf-8;base64,' . base64_encode($file[2]),
        ], $files);
    }
}
