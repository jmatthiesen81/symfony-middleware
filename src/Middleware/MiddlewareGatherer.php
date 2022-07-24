<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

use Kafkiansky\SymfonyMiddleware\Attribute\Middleware;
use Kafkiansky\SymfonyMiddleware\DependencyInjection\Configuration;
use Kafkiansky\SymfonyMiddleware\Middleware\Registry\MiddlewareRegistry;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @psalm-import-type MiddlewareConfigurationType from Configuration
 */
final class MiddlewareGatherer
{
    private MiddlewareRegistry $middlewareRegistry;

    public function __construct(MiddlewareRegistry $middlewareRegistry)
    {
        $this->middlewareRegistry = $middlewareRegistry;
    }

    /**
     * @param Middleware[] $attributes
     *
     * @throws MiddlewareNotConfigured
     *
     * @return AbstractMiddleware[]
     */
    public function gather(array $attributes): array
    {
        \restore_error_handler();
        \dump([
            '__METHOD__'  => __METHOD__,
            '$attributes' => $attributes,
            'trace'       => \debug_backtrace(),
        ]);

        foreach ($attributes as &$attribute) {
            $attribute = $this->normalizeAttribute($attribute);
        }

        \dump(\array_unique(\array_merge(...\array_map(
            static fn (Middleware $middleware): array => $middleware->list,
            $attributes
        ))));

        $middlewaresOrGroups = \array_unique(\array_merge(...\array_map(
            static fn (Middleware $middleware): array => $middleware->list,
            $attributes
        )));

        \dump($attributes);

        \dd('fin');

        $middlewares = [ $this->middlewareRegistry->byName(MiddlewareRegistry::GLOBAL_MIDDLEWARE_GROUP) ];

        foreach ($middlewaresOrGroups as $middlewareOrGroup) {
            $middlewares[] = $this->middlewareRegistry->byName($middlewareOrGroup);
        }

        $middlewares = array_merge([], ...$middlewares);

        return array_values(array_unique($middlewares, SORT_REGULAR));
    }

    /**
     * @param array<class-string<MiddlewareInterface>|string|MiddlewareConfigurationType> $attribute
     *
     * @return MiddlewareConfigurationType
     */
    private function normalizeAttribute(mixed $attribute): array
    {
        if (\is_string($attribute)) {
            return [
                'id'     => $attribute,
                'append' => false,
            ];
        }

        return [
            'id'     => $attribute['id'],
            'append' => $attribute['append'] ?? false,
        ];
    }
}
