<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Database;
use App\Service\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class ProfessionalAuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authorization = trim($request->getHeaderLine('Authorization'));

        if (
            $authorization === ''
            || preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) !== 1
        ) {
            return $this->unauthorized(
                'missing_token',
                'Token de autenticacao nao informado.'
            );
        }

        $token = trim((string) ($matches[1] ?? ''));

        if ($token === '') {
            return $this->unauthorized(
                'missing_token',
                'Token de autenticacao nao informado.'
            );
        }

        try {
            $claims = (new JwtService())->decodeProfessionalToken($token);
        } catch (\Throwable) {
            return $this->unauthorized(
                'invalid_token',
                'Token invalido ou expirado.'
            );
        }

        $professionalId = (int) ($claims['sub'] ?? 0);

        if ($professionalId <= 0) {
            return $this->unauthorized(
                'invalid_token',
                'Token invalido ou expirado.'
            );
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT id, nome, email, telefone, status, ultimo_login_em
               FROM profissionais
              WHERE id = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $professionalId]);

        $professional = $stmt->fetch();

        if (
            $professional === false
            || (string) $professional['status'] !== 'ATIVO'
        ) {
            return $this->unauthorized(
                'invalid_token',
                'Token invalido ou expirado.'
            );
        }

        $request = $request->withAttribute(
            'auth.professional',
            [
                'id' => (int) $professional['id'],
                'nome' => (string) $professional['nome'],
                'email' => (string) $professional['email'],
                'telefone' => $professional['telefone'] !== null
                    ? (string) $professional['telefone']
                    : null,
                'status' => (string) $professional['status'],
                'ultimo_login_em' => $professional['ultimo_login_em'],
            ]
        );

        return $handler->handle($request);
    }

    private function unauthorized(
        string $error,
        string $message
    ): ResponseInterface {
        $response = new Response(401);

        $payload = json_encode(
            [
                'error' => $error,
                'message' => $message,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $response->getBody()->write($payload ?: '{}');

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('WWW-Authenticate', 'Bearer');
    }
}
