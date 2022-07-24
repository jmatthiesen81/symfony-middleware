<?php

declare(strict_types=1);

namespace Kafkiansky\SymfonyMiddleware\Middleware;

use Kafkiansky\SymfonyMiddleware\Middleware\Event\MiddlewareEvents;
use Kafkiansky\SymfonyMiddleware\Middleware\Event\ResponseEvent;
use Kafkiansky\SymfonyMiddleware\Psr\PsrRequestCloner;
use Kafkiansky\SymfonyMiddleware\Psr\PsrResponseTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SymfonyActionRequestHandler implements RequestHandlerInterface
{
    /**
     * @var callable(SymfonyRequest): Response
     */
    private                          $destination;

    private PsrResponseTransformer   $psrResponseTransformer;

    private SymfonyRequest           $symfonyRequest;

    private PsrRequestCloner         $psrRequestCloner;

    private EventDispatcherInterface $dispatcher;

    /**
     * @param callable(SymfonyRequest): Response $destination
     */
    public function __construct(
        callable                 $destination,
        SymfonyRequest           $symfonyRequest,
        PsrResponseTransformer   $psrResponseTransformer,
        PsrRequestCloner         $psrRequestCloner,
        EventDispatcherInterface $dispatcher
    ) {
        $this->destination            = $destination;
        $this->psrResponseTransformer = $psrResponseTransformer;
        $this->symfonyRequest         = $symfonyRequest;
        $this->psrRequestCloner       = $psrRequestCloner;
        $this->dispatcher             = $dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $clonedSymfonyRequest = $this->psrRequestCloner->clone($this->symfonyRequest, $request);
        $response             = ($this->destination)($clonedSymfonyRequest);

        $this->dispatcher->dispatch(
            new ResponseEvent($clonedSymfonyRequest, $response),
            MiddlewareEvents::RESPONSE
        );

        return $this->psrResponseTransformer->toPsrResponse($response);
    }
}
