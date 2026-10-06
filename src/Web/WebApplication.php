<?php

declare(strict_types=1);

namespace LotoSorter\Web;

use LotoSorter\Web\Action\ExampleCsvAction;
use LotoSorter\Web\Action\HomeAction;
use LotoSorter\Web\Action\SortAction;
use LotoSorter\DependencyInjection\Services;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

final class WebApplication
{
    /**
     * @return App<ContainerInterface>
     */
    public static function create(Services $services): App
    {
        $app = AppFactory::createFromContainer($services->container());

        $app->get('/', HomeAction::class)->setName('home');
        $app->post('/', SortAction::class)->setName('sort');
        $app->get('/exemple.csv', ExampleCsvAction::class)->setName('example');

        $app->add(TwigMiddleware::create($app, $services->get(Twig::class)));
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware($services->get(WebSettings::class)->debug, true, true);

        return $app;
    }
}
