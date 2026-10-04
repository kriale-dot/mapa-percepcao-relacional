<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ServerRequestInterface;

final class RateLimitService
{
    /**
     * @return array{allowed:bool,retry_after:int,count:int,limit:int}
     */
    public function hit(
        string $scope,
        string $key,
        int $limit,
        int $windowSeconds,
        ?PDO $pdo = null
    ): array {
        $pdo ??= Database::connect();

        $limit = max(1, $limit);
        $windowSeconds = max(60, $windowSeconds);
        $scope = substr(trim($scope), 0, 60);
        $keyHash = hash('sha256', $key);

        $sql = sprintf(
            'INSERT INTO rate_limites (
                escopo,
                chave_hash,
                contador,
                janela_expira_em
             ) VALUES (
                :escopo,
                :chave_hash,
                1,
                DATE_ADD(NOW(), INTERVAL %d SECOND)
             )
             ON DUPLICATE KEY UPDATE
                contador = IF(
                    janela_expira_em <= NOW(),
                    1,
                    contador + 1
                ),
                janela_expira_em = IF(
                    janela_expira_em <= NOW(),
                    DATE_ADD(NOW(), INTERVAL %d SECOND),
                    janela_expira_em
                )',
            $windowSeconds,
            $windowSeconds
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'escopo' => $scope,
            'chave_hash' => $keyHash,
        ]);

        $select = $pdo->prepare(
            'SELECT
                contador,
                GREATEST(
                    0,
                    TIMESTAMPDIFF(
                        SECOND,
                        NOW(),
                        janela_expira_em
                    )
                ) AS retry_after
             FROM rate_limites
             WHERE escopo = :escopo
               AND chave_hash = :chave_hash
             LIMIT 1'
        );
        $select->execute([
            'escopo' => $scope,
            'chave_hash' => $keyHash,
        ]);

        $row = $select->fetch(PDO::FETCH_ASSOC);

        $count = $row === false ? 1 : (int) $row['contador'];
        $retryAfter = $row === false ? $windowSeconds : (int) $row['retry_after'];

        return [
            'allowed' => $count <= $limit,
            'retry_after' => $retryAfter,
            'count' => $count,
            'limit' => $limit,
        ];
    }

    /**
     * @return array{allowed:bool,retry_after:int,count:int,limit:int}
     */
    public function check(
        string $scope,
        string $key,
        int $limit,
        ?PDO $pdo = null
    ): array {
        $pdo ??= Database::connect();

        $limit = max(1, $limit);
        $scope = substr(trim($scope), 0, 60);
        $keyHash = hash('sha256', $key);

        $stmt = $pdo->prepare(
            'SELECT
                contador,
                janela_expira_em,
                GREATEST(
                    0,
                    TIMESTAMPDIFF(
                        SECOND,
                        NOW(),
                        janela_expira_em
                    )
                ) AS retry_after
             FROM rate_limites
             WHERE escopo = :escopo
               AND chave_hash = :chave_hash
             LIMIT 1'
        );
        $stmt->execute([
            'escopo' => $scope,
            'chave_hash' => $keyHash,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $row === false
            || strtotime((string) $row['janela_expira_em']) <= time()
        ) {
            return [
                'allowed' => true,
                'retry_after' => 0,
                'count' => 0,
                'limit' => $limit,
            ];
        }

        $count = (int) $row['contador'];

        return [
            'allowed' => $count < $limit,
            'retry_after' => (int) $row['retry_after'],
            'count' => $count,
            'limit' => $limit,
        ];
    }

    public function requestKey(
        ServerRequestInterface $request,
        ?string $secondary = null
    ): string {
        $server = $request->getServerParams();
        $ip = trim((string) ($server['REMOTE_ADDR'] ?? 'unknown'));

        if ($ip === '') {
            $ip = 'unknown';
        }

        $secondary = strtolower(trim((string) $secondary));

        return $ip . '|' . $secondary;
    }
}
