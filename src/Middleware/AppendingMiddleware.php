<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

final class AppendingMiddleware extends AbstractMiddleware
{
    protected function isAppending(): bool
    {
        return true;
    }
}
