<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ServerRequestInterface;

final class AuditService
{
    /**
     * @param array<string,mixed> $context
     */
    public function record(
        string $actorType,
        ?int $actorId,
        string $action,
        ?string $entityType,
        ?int $entityId,
        array $context,
        ServerRequestInterface $request,
        ?int $professionalId = null,
        ?PDO $pdo = null
    ): void {
        $pdo ??= Database::connect();

        $safeContext = $this->sanitize($context);
        $contextJson = $safeContext === []
            ? null
            : json_encode(
                $safeContext,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        $stmt = $pdo->prepare(
            'INSERT INTO auditoria_eventos (
                profissional_id,
                ator_tipo,
                ator_id,
                acao,
                entidade_tipo,
                entidade_id,
                contexto_json,
                ip,
                user_agent
             ) VALUES (
                :profissional_id,
                :ator_tipo,
                :ator_id,
                :acao,
                :entidade_tipo,
                :entidade_id,
                :contexto_json,
                :ip,
                :user_agent
             )'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
            'ator_tipo' => $actorType,
            'ator_id' => $actorId,
            'acao' => $action,
            'entidade_tipo' => $entityType,
            'entidade_id' => $entityId,
            'contexto_json' => $contextJson,
            'ip' => $this->clientIp($request),
            'user_agent' => $this->userAgent($request),
        ]);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function sanitize(array $context): array
    {
        $blockedKeys = [
            'senha',
            'password',
            'token',
            'token_hash',
            'jwt',
            'authorization',
            'smtp_password',
        ];

        $result = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            $containsSensitiveFragment = false;

            foreach ($blockedKeys as $blockedKey) {
                if (str_contains($normalizedKey, $blockedKey)) {
                    $containsSensitiveFragment = true;
                    break;
                }
            }

            if ($containsSensitiveFragment) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->sanitize($value);
                continue;
            }

            if (
                $value === null
                || is_bool($value)
                || is_int($value)
                || is_float($value)
            ) {
                $result[$key] = $value;
                continue;
            }

            $result[$key] = substr((string) $value, 0, 2000);
        }

        return $result;
    }

    private function clientIp(ServerRequestInterface $request): ?string
    {
        $server = $request->getServerParams();
        $ip = trim((string) ($server['REMOTE_ADDR'] ?? ''));

        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }

    private function userAgent(ServerRequestInterface $request): ?string
    {
        $value = trim($request->getHeaderLine('User-Agent'));

        if ($value === '') {
            return null;
        }

        return substr($value, 0, 500);
    }
}
