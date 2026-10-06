<?php

declare(strict_types=1);

namespace LotoSorter\Web\Form;

use LotoSorter\Config\IniConfigLoader;
use LotoSorter\Config\LotoConfigFactory;
use LotoSorter\Web\WebSettings;

/**
 * The values the form starts with: the project's loto_config.ini, or the example configuration.
 */
final class ConfigDefaults
{
    public function __construct(
        private readonly WebSettings $settings,
        private readonly IniConfigLoader $loader,
        private readonly LotoConfigFactory $factory,
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function values(bool $example = false): array
    {
        $path = $example || !is_readable($this->settings->configPath)
            ? $this->settings->exampleConfigPath
            : $this->settings->configPath;

        return $this->factory->toArray($this->loader->load($path));
    }
}
