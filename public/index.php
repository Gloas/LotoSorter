<?php

declare(strict_types=1);

use LotoSorter\DependencyInjection\Services;
use LotoSorter\Web\WebApplication;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var Services $services */
$services = require dirname(__DIR__) . '/config/container.php';

WebApplication::create($services)->run();
