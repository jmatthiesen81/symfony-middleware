<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

use Closure;
use Kafkiansky\SymfonyMiddleware\Psr\PsrResponseTransformer;
use Nyholm\Psr7\Response as PsrResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MiddlewareRunner
{
    /**
     * @var AbstractMiddleware[]
     */
    private array                   $middlewares;

    private RequestHandlerInterface $requestHandler;

    private PsrResponseTransformer  $psrResponseTransformer;

    /**
     * @param AbstractMiddleware[] $middlewares
     */
    public function __construct(
        array                   $middlewares,
        RequestHandlerInterface $requestHandler,
        PsrResponseTransformer  $psrResponseTransformer,
    ) {
        $this->middlewares            = $middlewares;
        $this->requestHandler         = $requestHandler;
        $this->psrResponseTransformer = $psrResponseTransformer;
    }

    public function run(ServerRequestInterface $serverRequest): Response
    {
        $middlewares = [
            ...$this->getPrependingMiddlewares(),
            new SymfonyBridgeMiddleware($this->requestHandler),
            ...$this->getAppendingMiddlewares(),
        ];

        \dump($middlewares);

        /** @var Closure(ServerRequestInterface): ResponseInterface */
        $processor = array_reduce(
            \array_reverse($middlewares),
            /** @param Closure(ServerRequestInterface): ResponseInterface $stack */
            static function (Closure $stack, MiddlewareInterface $middleware): Closure {
                return static function (ServerRequestInterface $request) use ($middleware, $stack): ResponseInterface {
                    return $middleware->process($request, new StackMiddleware($stack));
                };
            },
            static fn (ServerRequestInterface $request): ResponseInterface => new PsrResponse(),
        );

        return $this->psrResponseTransformer->fromPsrResponse($processor($serverRequest));
    }

    /**
     * @return list<MiddlewareInterface>
     */
    private function getPrependingMiddlewares(): array
    {
        $middlewares = [];

        foreach ($this->middlewares as $middleware) {
            if ($middleware->prepend()) {
                $middlewares[] = $middleware->getMiddleware();
            }
        }

        return $middlewares;
    }

    /**
     * @return list<MiddlewareInterface>
     */
    private function getAppendingMiddlewares(): array
    {
        $middlewares = [];

        foreach ($this->middlewares as $middleware) {
            if ($middleware->append()) {
                $middlewares[] = $middleware->getMiddleware();
            }
        }

        return $middlewares;
    }
}
