<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\DependencyInjection;

use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

/**
 * @psalm-type MiddlewareConfigurationType = array{
 *     id: class-string<MiddlewareInterface>,
 *     append: boolean,
 * }
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress PossiblyNullReference
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('symiddleware');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $tree->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('global')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('id')
                                ->defaultNull()
                            ->end()
                            ->booleanNode('append')
                                ->defaultFalse()
                            ->end()
                        ->end()
                        ->beforeNormalization()
                            ->always($this->createMiddlewareNormalizer())
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('groups')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('if')->end()
                            ->arrayNode('middlewares')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('id')
                                            ->defaultNull()
                                        ->end()
                                        ->booleanNode('append')
                                            ->defaultFalse()
                                        ->end()
                                    ->end()
                                    ->beforeNormalization()
                                        ->always($this->createMiddlewareNormalizer())
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tree;
    }

    /**
     * @return \Closure
     */
    private function createMiddlewareNormalizer(): callable
    {
        return function (mixed $middleware): array {
            if (!(\is_string($middleware) || \is_array($middleware))) {
                throw new InvalidTypeException(
                    \vsprintf('Each middleware configuration must be of type string or array "%s" given.', [
                        \get_debug_type($middleware),
                    ])
                );
            }

            if (\is_string($middleware)) {
                $middleware = [ 'id' => $middleware ];
            }

            $middleware = \array_merge([
                'id'     => null,
                'append' => false,
            ], $middleware);

            return $middleware;
        };
    }
}
