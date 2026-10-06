<?php

declare(strict_types=1);

use LotoSorter\Cli\SortCommand;
use LotoSorter\DependencyInjection\Services;

require __DIR__ . '/vendor/autoload.php';

/** @var Services $services */
$services = require __DIR__ . '/config/container.php';
$arguments = array_values(array_filter(array_slice((array) ($_SERVER['argv'] ?? []), 1), is_string(...)));

exit($services->get(SortCommand::class)->run($arguments, (string) getcwd(), STDOUT));
