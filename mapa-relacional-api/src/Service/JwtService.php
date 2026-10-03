<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;

final class JwtService
{
    private string $secret;
    private int $ttl;
    private string $issuer;

    public function __construct()
    {
        $secret = trim((string) ($_ENV['JWT_SECRET'] ?? ''));

        if (
            $secret === ''
            || $secret === 'troque-por-um-segredo-longo-e-aleatorio'
            || strlen($secret) < 32
        ) {
            throw new \RuntimeException(
                'JWT_SECRET deve estar configurado com pelo menos 32 caracteres.'
            );
        }

        $ttl = (int) ($_ENV['JWT_TTL_SECONDS'] ?? 3600);

        if ($ttl < 300) {
            throw new \RuntimeException(
                'JWT_TTL_SECONDS deve ser de pelo menos 300 segundos.'
            );
        }

        $this->secret = $secret;
        $this->ttl = $ttl;
        $this->issuer = rtrim(
            (string) ($_ENV['APP_URL'] ?? 'mapa-relacional-api'),
            '/'
        );
    }

    /**
     * @param array{id:int|string,nome:string,email:string,status:string} $professional
     * @return array{access_token:string,token_type:string,expires_in:int,expires_at:string}
     */
    public function issueProfessionalToken(array $professional): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttl;

        $payload = [
            'iss' => $this->issuer,
            'sub' => (string) $professional['id'],
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAt,
            'type' => 'professional',
            'email' => $professional['email'],
        ];

        return [
            'access_token' => JWT::encode($payload, $this->secret, 'HS256'),
            'token_type' => 'Bearer',
            'expires_in' => $this->ttl,
            'expires_at' => gmdate(DATE_ATOM, $expiresAt),
        ];
    }
}
