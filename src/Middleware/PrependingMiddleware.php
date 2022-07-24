<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

final class PrependingMiddleware extends AbstractMiddleware
{
    protected function isAppending(): bool
    {
        return false;
    }
}
