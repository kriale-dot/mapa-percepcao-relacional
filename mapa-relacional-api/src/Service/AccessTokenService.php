<?php

declare(strict_types=1);

namespace App\Service;

final class AccessTokenService
{
    /**
     * @return array{token:string,hash:string}
     */
    public function generate(): array
    {
        $token = bin2hex(random_bytes(32));

        return [
            'token' => $token,
            'hash' => hash('sha256', $token),
        ];
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isValidFormat(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
    }
}
