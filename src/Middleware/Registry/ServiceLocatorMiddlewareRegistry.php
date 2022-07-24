<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware\Registry;

use Kafkiansky\SymfonyMiddleware\DependencyInjection\Configuration;
use Kafkiansky\SymfonyMiddleware\Middleware\AbstractMiddleware;
use Kafkiansky\SymfonyMiddleware\Middleware\AppendingMiddleware;
use Kafkiansky\SymfonyMiddleware\Middleware\MiddlewareNotConfigured;
use Kafkiansky\SymfonyMiddleware\Middleware\PrependingMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @psalm-import-type MiddlewareConfigurationType from Configuration
 */
final class ServiceLocatorMiddlewareRegistry implements MiddlewareRegistry
{
    private ContainerInterface $container;

    /**
     * @var array<string, array{if?: bool, middlewares: MiddlewareConfigurationType[]}>
     */
    private array $groups;

    /**
     * @psalm-param array<string, array{if?: bool, middlewares: MiddlewareConfigurationType[]}> $groups
     */
    public function __construct(ContainerInterface $container, array $groups)
    {
        $this->container = $container;
        $this->groups = $groups;
    }

    /**
     * @param class-string<MiddlewareInterface>|string $middlewareFqcnOrGroup
     *
     * @throws MiddlewareNotConfigured
     *
     * @return AbstractMiddleware[]
     */
    public function byName(string $middlewareFqcnOrGroup): array
    {
        if (
            !isset($this->groups[$middlewareFqcnOrGroup])
            && $this->container->has($middlewareFqcnOrGroup) === false
        ) {
            throw MiddlewareNotConfigured::forMiddleware($middlewareFqcnOrGroup);
        }

        if ($this->container->has($middlewareFqcnOrGroup)) {
            \dump('DIRECT CALL', $middlewareFqcnOrGroup);

            $middleware = $this->container->get($middlewareFqcnOrGroup);

            if (!$middleware instanceof MiddlewareInterface) {
                throw MiddlewareNotConfigured::forMiddleware($middlewareFqcnOrGroup);
            }

            return [ new PrependingMiddleware($middleware) ];
        }

        $middlewares = [];

        if (!isset($this->groups[$middlewareFqcnOrGroup]['if']) || $this->groups[$middlewareFqcnOrGroup]['if']) {
            /** @var AbstractMiddleware[] $middlewares */
            $middlewares = array_map(
                /**
                 * @psalm-param MiddlewareConfigurationType $middlewareConfig
                 */
                function (array $middlewareConfig): AbstractMiddleware
                {
                    $middleware = $this->container->get($middlewareConfig['id']);

                    if (!$middleware instanceof MiddlewareInterface) {
                        throw MiddlewareNotConfigured::forMiddleware($middlewareConfig['id']);
                    }

                    return $middlewareConfig['append']
                        ? new AppendingMiddleware($middleware)
                        : new PrependingMiddleware($middleware);
                },
                $this->groups[$middlewareFqcnOrGroup]['middlewares']
                    ?? throw MiddlewareNotConfigured::becauseGroupIsEmpty($middlewareFqcnOrGroup)
            );
        }

        return $middlewares;
    }
}
