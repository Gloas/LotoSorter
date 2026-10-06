<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Cli;

use LotoSorter\Cli\SortCommand;
use PHPUnit\Framework\TestCase;
use LotoSorter\DependencyInjection\Services;

final class SortCommandTest extends TestCase
{
    private const EXAMPLES = __DIR__ . '/../../examples/';

    private string $workingDir;
    private SortCommand $command;

    protected function setUp(): void
    {
        $this->workingDir = sys_get_temp_dir() . '/loto_sorter_' . uniqid();
        mkdir($this->workingDir);

        /** @var Services $services */
        $services = require __DIR__ . '/../../config/container.php';
        $this->command = $services->get(SortCommand::class);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->workingDir . '/*') ?: []);
        rmdir($this->workingDir);
    }

    public function testSortsTheExampleSheet(): void
    {
        copy(self::EXAMPLES . 'loto_config.exemple.ini', $this->workingDir . '/loto_config.ini');

        [$code, $output] = $this->runCommand([realpath(self::EXAMPLES . 'lots_loto_exemple.csv')]);

        self::assertSame(SortCommand::SUCCESS, $code);
        self::assertStringContainsString('Nombre de lots triés', $output);
        foreach (['auto_sort_loto_donations.csv', 'sorted_donations.csv', 'not_sorted_donations.csv'] as $file) {
            self::assertFileExists($this->workingDir . '/' . $file);
        }
    }

    public function testShowsTheUsage(): void
    {
        [$code, $output] = $this->runCommand([]);

        self::assertSame(SortCommand::USAGE, $code);
        self::assertStringStartsWith('Usage', $output);
    }

    public function testFailsWithoutConfiguration(): void
    {
        [$code, $output] = $this->runCommand([realpath(self::EXAMPLES . 'lots_loto_exemple.csv')]);

        self::assertSame(SortCommand::FAILURE, $code);
        self::assertStringContainsString('loto_config.ini', $output);
    }

    /**
     * @param list<string|false> $arguments
     *
     * @return array{int, string}
     */
    private function runCommand(array $arguments): array
    {
        $output = fopen('php://memory', 'w+');
        self::assertIsResource($output);
        $code = $this->command->run(array_map('strval', $arguments), $this->workingDir, $output);
        rewind($output);

        return [$code, (string) stream_get_contents($output)];
    }
}
