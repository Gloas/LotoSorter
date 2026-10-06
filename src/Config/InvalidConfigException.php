<?php

declare(strict_types=1);

namespace LotoSorter\Config;

use InvalidArgumentException;

final class InvalidConfigException extends InvalidArgumentException
{
    /**
     * @param array<string, string> $errors error message by field ("adult.quine")
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Configuration invalide : ' . implode(' ', $errors));
    }
}
