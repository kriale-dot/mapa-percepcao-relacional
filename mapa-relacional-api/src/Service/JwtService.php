<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
     * @return array<string, mixed>
     */
    public function decodeProfessionalToken(string $token): array
    {
        $claims = (array) JWT::decode(
            $token,
            new Key($this->secret, 'HS256')
        );

        if (($claims['iss'] ?? null) !== $this->issuer) {
            throw new \UnexpectedValueException('JWT issuer invalido.');
        }

        if (($claims['type'] ?? null) !== 'professional') {
            throw new \UnexpectedValueException('JWT type invalido.');
        }

        $subject = (string) ($claims['sub'] ?? '');

        if ($subject === '' || preg_match('/^[1-9][0-9]*$/', $subject) !== 1) {
            throw new \UnexpectedValueException('JWT subject invalido.');
        }

        return $claims;
    }

    /**
     * @param array{id:int|string,nome:string,email:string,status:string,senha_hash:string} $professional
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
            'pwd' => hash('sha256', $professional['senha_hash']),
        ];

        return [
            'access_token' => JWT::encode($payload, $this->secret, 'HS256'),
            'token_type' => 'Bearer',
            'expires_in' => $this->ttl,
            'expires_at' => gmdate(DATE_ATOM, $expiresAt),
        ];
    }
}
