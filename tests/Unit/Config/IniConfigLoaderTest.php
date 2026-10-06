<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Unit\Config;

use LotoSorter\Config\IniConfigLoader;
use LotoSorter\Config\LotoConfigFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class IniConfigLoaderTest extends TestCase
{
    public function testLoadsTheExampleConfiguration(): void
    {
        $loader = new IniConfigLoader(new LotoConfigFactory());

        $config = $loader->load(__DIR__ . '/../../../examples/loto_config.exemple.ini');

        self::assertCount(2, $config->games);
        self::assertSame(['APE de Valleiry'], $config->policy->allowedDonors);
    }

    public function testRefusesAMissingFile(): void
    {
        $this->expectException(RuntimeException::class);

        (new IniConfigLoader(new LotoConfigFactory()))->load('/nowhere/loto_config.ini');
    }
}
