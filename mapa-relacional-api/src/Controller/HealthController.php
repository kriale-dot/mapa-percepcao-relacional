<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HealthController
{
    public function app(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $this->json($response, [
            'status' => 'ok',
            'service' => 'mapa-relacional-api',
            'version' => '0.1.0',
            'environment' => $_ENV['APP_ENV'] ?? 'development',
            'timestamp' => gmdate(DATE_ATOM),
        ]);
    }

    public function database(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        try {
            $pdo = Database::connect();
            $pdo->query('SELECT 1');

            return $this->json($response, [
                'status' => 'ok',
                'database' => 'connected',
                'timestamp' => gmdate(DATE_ATOM),
            ]);
        } catch (\Throwable $e) {
            return $this->json($response, [
                'status' => 'error',
                'database' => 'unavailable',
                'message' => $e->getMessage(),
                'timestamp' => gmdate(DATE_ATOM),
            ], 503);
        }
    }

    private function json(
        ResponseInterface $response,
        array $data,
        int $status = 200
    ): ResponseInterface {
        $payload = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $response->getBody()->write($payload ?: '{}');

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
