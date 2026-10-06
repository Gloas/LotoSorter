<?php

declare(strict_types=1);

namespace LotoSorter\Web\Form;

use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

final class FormPage
{
    public function __construct(private readonly Twig $twig)
    {
    }

    /**
     * @param array<mixed> $values the form values, shaped like the loto_config.ini sections
     * @param array<string, string> $errors error message by field ("adult.quine") or by topic ("donations")
     */
    public function render(ResponseInterface $response, array $values, array $errors = []): ResponseInterface
    {
        return $this->twig->render($response, 'home.html.twig', [
            'values' => $values,
            'errors' => $errors,
        ]);
    }
}
