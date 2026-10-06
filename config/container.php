<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use LotoSorter\Application\SystemClock;
use LotoSorter\DependencyInjection\Services;
use LotoSorter\Sorting\RandomShuffler;
use LotoSorter\Sorting\Shuffler;
use LotoSorter\Web\WebSettings;
use Psr\Clock\ClockInterface;
use Random\Randomizer;
use Slim\Views\Twig;

$projectDir = dirname(__DIR__);

$container = (new ContainerBuilder())
    ->useAutowiring(true)
    ->addDefinitions([
        ClockInterface::class => static fn(): SystemClock => new SystemClock(
            new DateTimeZone(getenv('APP_TIMEZONE') ?: 'Europe/Paris'),
        ),
        Shuffler::class => static fn(): RandomShuffler => new RandomShuffler(new Randomizer()),
        WebSettings::class => static fn(): WebSettings => new WebSettings(
            configPath: $projectDir . '/loto_config.ini',
            exampleConfigPath: $projectDir . '/examples/loto_config.exemple.ini',
            exampleCsvPath: $projectDir . '/examples/lots_loto_exemple.csv',
            debug: filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL),
        ),
        Twig::class => static fn(): Twig => Twig::create($projectDir . '/templates', [
            'cache' => false,
            'strict_variables' => true,
        ]),
    ])
    ->build();

return new Services($container);
