<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\JwtService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function login(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['senha'] ?? $data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json($response, [
                'error' => 'validation_error',
                'message' => 'E-mail e senha sao obrigatorios.',
            ], 422);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->json($response, [
                'error' => 'validation_error',
                'message' => 'E-mail invalido.',
            ], 422);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT id, nome, email, senha_hash, status
               FROM profissionais
              WHERE email = :email
              LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        $professional = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $professional === false
            || (string) $professional['status'] !== 'ATIVO'
            || empty($professional['senha_hash'])
            || !password_verify($password, (string) $professional['senha_hash'])
        ) {
            return $this->json($response, [
                'error' => 'invalid_credentials',
                'message' => 'E-mail ou senha invalidos.',
            ], 401);
        }

        if (password_needs_rehash((string) $professional['senha_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);

            if ($newHash !== false) {
                $rehash = $pdo->prepare(
                    'UPDATE profissionais
                        SET senha_hash = :senha_hash,
                            senha_alterada_em = NOW()
                      WHERE id = :id'
                );
                $rehash->execute([
                    'senha_hash' => $newHash,
                    'id' => $professional['id'],
                ]);
            }
        }

        $updateLogin = $pdo->prepare(
            'UPDATE profissionais
                SET ultimo_login_em = NOW()
              WHERE id = :id'
        );
        $updateLogin->execute(['id' => $professional['id']]);

        $token = (new JwtService())->issueProfessionalToken([
            'id' => $professional['id'],
            'nome' => (string) $professional['nome'],
            'email' => (string) $professional['email'],
            'status' => (string) $professional['status'],
        ]);

        return $this->json($response, [
            ...$token,
            'profissional' => [
                'id' => (int) $professional['id'],
                'nome' => (string) $professional['nome'],
                'email' => (string) $professional['email'],
                'status' => (string) $professional['status'],
            ],
        ]);
    }

    public function me(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professional = $request->getAttribute('auth.professional');

        if (!is_array($professional)) {
            return $this->json($response, [
                'error' => 'unauthorized',
                'message' => 'Autenticacao profissional obrigatoria.',
            ], 401);
        }

        return $this->json($response, [
            'profissional' => $professional,
        ]);
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
