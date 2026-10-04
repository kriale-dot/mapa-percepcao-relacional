<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AccessTokenService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ParticipantAccessController
{
    public function __construct(
        private readonly AccessTokenService $tokenService
    ) {
    }

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $token = trim((string) ($args['token'] ?? ''));

        if (!$this->tokenService->isValidFormat($token)) {
            return $this->notFound($response);
        }

        $hash = $this->tokenService->hash($token);
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                aa.id AS acesso_id,
                aa.status AS acesso_status,
                aa.enviado_em,
                aa.primeiro_acesso_em,
                aa.ultimo_acesso_em,
                aa.concluido_em AS acesso_concluido_em,
                aa.revogado_em,
                ap.id AS participante_id,
                ap.lado,
                ap.nome_snapshot,
                ap.idade_snapshot,
                ap.genero_snapshot,
                ap.status AS participante_status,
                ap.iniciou_em,
                ap.concluiu_em,
                a.id AS aplicacao_id,
                a.status AS aplicacao_status,
                a.tipo_vinculo_snapshot,
                a.duracao_vinculo_texto,
                i.nome AS avaliacao_nome
             FROM acessos_aplicacao aa
             INNER JOIN aplicacao_participantes ap
               ON ap.id = aa.aplicacao_participante_id
             INNER JOIN aplicacoes a
               ON a.id = ap.aplicacao_id
             INNER JOIN instrumento_versoes v
               ON v.id = a.instrumento_versao_id
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             WHERE aa.token_hash = :token_hash
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $hash]);

        $access = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($access === false) {
            return $this->notFound($response);
        }

        if (
            (string) $access['acesso_status'] !== 'ATIVO'
            || $access['revogado_em'] !== null
        ) {
            return $this->json($response, [
                'error' => 'access_unavailable',
                'message' => 'Este acesso nao esta mais disponivel.',
            ], 410);
        }

        if (
            $access['concluido_em'] !== null
            || $access['acesso_concluido_em'] !== null
            || (string) $access['participante_status'] === 'CONCLUIDO'
        ) {
            return $this->json($response, [
                'error' => 'access_completed',
                'message' => 'Esta avaliacao ja foi concluida por este participante.',
            ], 410);
        }

        $update = $pdo->prepare(
            'UPDATE acessos_aplicacao
                SET primeiro_acesso_em = COALESCE(primeiro_acesso_em, NOW()),
                    ultimo_acesso_em = NOW()
              WHERE id = :id'
        );
        $update->execute(['id' => (int) $access['acesso_id']]);

        return $this->json($response, [
            'acesso' => [
                'aplicacao_id' => (int) $access['aplicacao_id'],
                'avaliacao_nome' => (string) $access['avaliacao_nome'],
                'aplicacao_status' => (string) $access['aplicacao_status'],
                'tipo_vinculo' => (string) $access['tipo_vinculo_snapshot'],
                'tempo_uniao' => $access['duracao_vinculo_texto'],
                'participante' => [
                    'id' => (int) $access['participante_id'],
                    'lado' => (string) $access['lado'],
                    'nome_snapshot' => $access['nome_snapshot'],
                    'idade_snapshot' => $access['idade_snapshot'] === null
                        ? null
                        : (int) $access['idade_snapshot'],
                    'genero_snapshot' => $access['genero_snapshot'],
                    'status' => (string) $access['participante_status'],
                ],
            ],
        ]);
    }

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Acesso invalido ou indisponivel.',
        ], 404);
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
