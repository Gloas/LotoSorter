<?php

declare(strict_types=1);

namespace LotoSorter\Config;

use RuntimeException;

final class IniConfigLoader
{
    public function __construct(private readonly LotoConfigFactory $factory)
    {
    }

    /**
     * @throws InvalidConfigException
     */
    public function load(string $path): LotoConfig
    {
        $sections = is_readable($path) ? parse_ini_file($path, true) : false;
        if (false === $sections) {
            throw new RuntimeException(sprintf('Impossible de lire la configuration « %s ».', $path));
        }

        return $this->factory->fromArray($sections);
    }
}
