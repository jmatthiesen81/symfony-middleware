<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

use Psr\Http\Server\MiddlewareInterface;

abstract class AbstractMiddleware
{
    protected MiddlewareInterface $middleware;

    protected bool                $append;

    final public function __construct(MiddlewareInterface $middleware)
    {
        $this->middleware     = $middleware;
        $this->append         = $this->isAppending();
    }

    final public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    final public function append(): bool
    {
        return $this->append;
    }

    final public function prepend(): bool
    {
        return !$this->append;
    }

    abstract protected function isAppending(): bool;
}
