<?php

declare(strict_types=1);

namespace LotoSorter\Tests\Web;

use DI\Container;
use LotoSorter\DependencyInjection\Services;
use LotoSorter\Sorting\RandomShuffler;
use LotoSorter\Sorting\Shuffler;
use LotoSorter\Web\WebApplication;
use LotoSorter\Web\WebSettings;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\UploadedFile;

final class WebApplicationTest extends TestCase
{
    private const EXAMPLES = __DIR__ . '/../../examples/';

    /** @var App<ContainerInterface> */
    private App $app;

    protected function setUp(): void
    {
        /** @var Services $services */
        $services = require __DIR__ . '/../../config/container.php';
        $container = $services->container();
        self::assertInstanceOf(Container::class, $container);
        $container->set(WebSettings::class, new WebSettings(
            configPath: self::EXAMPLES . 'loto_config.exemple.ini',
            exampleConfigPath: self::EXAMPLES . 'loto_config.exemple.ini',
            exampleCsvPath: self::EXAMPLES . 'lots_loto_exemple.csv',
            debug: true,
        ));
        $container->set(Shuffler::class, new RandomShuffler(new Randomizer(new Mt19937(42))));
        $this->app = WebApplication::create($services);
    }

    public function testShowsTheFormWithTheConfiguration(): void
    {
        $response = $this->handle($this->request('GET', '/'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="donations[]"', (string) $response->getBody());
        self::assertStringContainsString('name="config[adult][quine]"', (string) $response->getBody());
        self::assertStringContainsString('APE de Valleiry</textarea>', (string) $response->getBody());
    }

    public function testServesTheExampleSheet(): void
    {
        $response = $this->handle($this->request('GET', '/exemple.csv'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('text/csv', $response->getHeaderLine('Content-Type'));
        self::assertStringStartsWith('COMMERCES,', (string) $response->getBody());
    }

    public function testSortsTheUploadedSheets(): void
    {
        $response = $this->handle($this->sortRequest([$this->upload('lots_loto_exemple.csv')]));
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Résultat du tri', $body);
        self::assertStringContainsString('Partie n°2', $body);
        self::assertStringContainsString('download="auto_sort_loto_donations.csv"', $body);
        self::assertStringContainsString('href="data:text/csv;charset=utf-8;base64,', $body);
    }

    public function testAsksForAFile(): void
    {
        $response = $this->handle($this->sortRequest([]));

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Ajoutez au moins un fichier CSV', (string) $response->getBody());
    }

    public function testRefusesAFileThatIsNotACsv(): void
    {
        $response = $this->handle($this->sortRequest([$this->upload('loto_config.exemple.ini')]));

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('n&#039;est pas un CSV', (string) $response->getBody());
    }

    public function testShowsTheConfigurationErrors(): void
    {
        $response = $this->handle($this->sortRequest(
            [$this->upload('lots_loto_exemple.csv')],
            ['adult' => ['round' => 'beaucoup']],
        ));
        $body = (string) $response->getBody();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('is-invalid', $body);
        self::assertStringContainsString('value="beaucoup"', $body);
    }

    public function testExplainsWhenNoSortIsPossible(): void
    {
        $response = $this->handle($this->sortRequest(
            [$this->upload('lots_loto_exemple.csv')],
            ['adult' => ['round' => '50', 'quine' => '40', 'double_quine' => '80', 'carton' => '120'],
             'sorting' => ['max_attempts' => '2']],
        ));

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Trop de boucles', (string) $response->getBody());
    }

    /**
     * @param list<UploadedFile> $files
     * @param array<string, array<string, string>>|null $config
     */
    private function sortRequest(array $files, ?array $config = null): ServerRequestInterface
    {
        $config ??= (array) parse_ini_file(self::EXAMPLES . 'loto_config.exemple.ini', true);

        return $this->request('POST', '/')
            ->withParsedBody(['config' => $config])
            ->withUploadedFiles(['donations' => $files]);
    }

    private function upload(string $example): UploadedFile
    {
        $path = self::EXAMPLES . $example;

        return new UploadedFile($path, $example, 'text/csv', (int) filesize($path), UPLOAD_ERR_OK);
    }

    private function request(string $method, string $uri): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri);
    }

    private function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }
}
