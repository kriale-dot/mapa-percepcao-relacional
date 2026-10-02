<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $allowedOrigin
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = new Response();
        } else {
            $response = $handler->handle($request);
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowedOrigin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader(
                'Access-Control-Allow-Headers',
                'Content-Type, Authorization, X-Requested-With'
            )
            ->withHeader(
                'Access-Control-Allow-Methods',
                'GET, POST, PUT, PATCH, DELETE, OPTIONS'
            );
    }
}
