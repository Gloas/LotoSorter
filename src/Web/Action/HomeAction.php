<?php

declare(strict_types=1);

namespace LotoSorter\Web\Action;

use LotoSorter\Web\Form\ConfigDefaults;
use LotoSorter\Web\Form\FormPage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeAction
{
    public function __construct(
        private readonly FormPage $page,
        private readonly ConfigDefaults $defaults,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $example = isset($request->getQueryParams()['exemple']);

        return $this->page->render($response, $this->defaults->values($example));
    }
}
