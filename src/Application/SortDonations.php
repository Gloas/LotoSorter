<?php

declare(strict_types=1);

namespace LotoSorter\Application;

use LotoSorter\Config\LotoConfig;
use LotoSorter\Csv\DonationCsvReader;
use LotoSorter\Report\ReportBuilder;
use LotoSorter\Report\SortReport;
use LotoSorter\Sorting\DonationSorter;
use LotoSorter\Sorting\Exception\SortFailedException;
use Psr\Clock\ClockInterface;

/**
 * Use case shared by the command line and the web interface: from a CSV of donations to the sorted report.
 */
final class SortDonations
{
    public function __construct(
        private readonly DonationCsvReader $reader,
        private readonly DonationSorter $sorter,
        private readonly ReportBuilder $reports,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @throws SortFailedException
     */
    public function execute(string $csv, LotoConfig $config): SortReport
    {
        $result = $this->sorter->sort($this->reader->read($csv), $config);

        return $this->reports->build($result, $config, $this->clock->now());
    }
}
