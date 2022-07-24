<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SymfonyBridgeMiddleware implements MiddlewareInterface
{
    private RequestHandlerInterface $symfonyActionRequestHandler;

    public function __construct(RequestHandlerInterface $symfonyActionRequestHandler)
    {
        $this->symfonyActionRequestHandler = $symfonyActionRequestHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->symfonyActionRequestHandler->handle($request);

        return $this->enrichHeaders($response, $handler->handle($request)
            ->withStatus($response->getStatusCode(), $response->getReasonPhrase())
            ->withBody($response->getBody())
            ->withProtocolVersion($response->getProtocolVersion())
        );
    }

    private function enrichHeaders(ResponseInterface $source, ResponseInterface $target): ResponseInterface
    {
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($source->getHeaders() as $key => $value) {
            $target = $target->withHeader($key, $value);
        }

        return $target;
    }
}
