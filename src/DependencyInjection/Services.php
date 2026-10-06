<?php

declare(strict_types=1);

namespace LotoSorter\DependencyInjection;

use LogicException;
use Psr\Container\ContainerInterface;

/**
 * Typed access to the container, for the entry points only: everything else gets its dependencies injected.
 */
final class Services
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id): object
    {
        $service = $this->container->get($id);
        if (!$service instanceof $id) {
            throw new LogicException(sprintf('The container entry "%s" is not a %s.', $id, $id));
        }

        return $service;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
