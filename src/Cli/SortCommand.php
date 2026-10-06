<?php

declare(strict_types=1);

namespace LotoSorter\Cli;

use LotoSorter\Application\SortDonations;
use LotoSorter\Config\IniConfigLoader;
use LotoSorter\Config\InvalidConfigException;
use LotoSorter\Report\SortReport;
use LotoSorter\Sorting\Exception\SortFailedException;
use RuntimeException;

/**
 * php sort_donation.php <donations.csv>: reads ./loto_config.ini, writes the result CSVs in the working directory.
 */
final class SortCommand
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const USAGE = 2;

    private const CONFIG_FILE = 'loto_config.ini';
    private const FULL_REPORT_FILE = 'auto_sort_loto_donations.csv';
    private const GRIDS_FILE = 'sorted_donations.csv';
    private const UNSORTED_FILE = 'not_sorted_donations.csv';

    public function __construct(
        private readonly SortDonations $sortDonations,
        private readonly IniConfigLoader $configLoader,
    ) {
    }

    /**
     * @param list<string> $arguments the command line arguments, without the script name
     * @param resource $output
     */
    public function run(array $arguments, string $workingDir, $output): int
    {
        if (1 !== count($arguments)) {
            fwrite($output, "Usage : php sort_donation.php <dons.csv>\n");

            return self::USAGE;
        }

        try {
            $report = $this->sortDonations->execute(
                $this->readFile($this->path($workingDir, $arguments[0])),
                $this->configLoader->load($this->path($workingDir, self::CONFIG_FILE)),
            );
        } catch (InvalidConfigException $exception) {
            fwrite($output, implode("\n", $exception->errors) . "\n");

            return self::FAILURE;
        } catch (SortFailedException | RuntimeException $exception) {
            fwrite($output, $exception->getMessage() . "\n");

            return self::FAILURE;
        }

        $this->writeFiles($report, $workingDir);
        fwrite($output, implode("\n", $report->summary) . "\n");

        return self::SUCCESS;
    }

    private function writeFiles(SortReport $report, string $workingDir): void
    {
        $files = [
            self::FULL_REPORT_FILE => $report->fullCsv,
            self::GRIDS_FILE => $report->gridsCsv,
            self::UNSORTED_FILE => $report->unsortedCsv,
        ];

        foreach ($files as $name => $csv) {
            if (false === file_put_contents($this->path($workingDir, $name), $csv)) {
                throw new RuntimeException(sprintf('Impossible d\'écrire « %s ».', $name));
            }
        }
    }

    private function readFile(string $path): string
    {
        $content = is_readable($path) ? file_get_contents($path) : false;
        if (false === $content) {
            throw new RuntimeException(sprintf('Impossible de lire « %s ».', $path));
        }

        return $content;
    }

    private function path(string $workingDir, string $file): string
    {
        return str_starts_with($file, '/') ? $file : $workingDir . '/' . $file;
    }
}
