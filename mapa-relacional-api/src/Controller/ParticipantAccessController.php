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
        $access = $this->resolveAccess(
            trim((string) ($args['token'] ?? '')),
            $response
        );

        if ($access instanceof ResponseInterface) {
            return $access;
        }

        $pdo = Database::connect();

        $update = $pdo->prepare(
            'UPDATE acessos_aplicacao
                SET primeiro_acesso_em = COALESCE(primeiro_acesso_em, NOW()),
                    ultimo_acesso_em = NOW()
              WHERE id = :id'
        );
        $update->execute(['id' => (int) $access['acesso_id']]);

        return $this->json($response, [
            'acesso' => $this->serializeAccess($access),
        ]);
    }

    public function identify(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $access = $this->resolveAccess(
            trim((string) ($args['token'] ?? '')),
            $response
        );

        if ($access instanceof ResponseInterface) {
            return $access;
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $name = trim((string) ($data['nome'] ?? ''));
        $ageRaw = $data['idade'] ?? null;
        $gender = trim((string) ($data['genero'] ?? ''));

        if ($name === '' || strlen($name) > 150) {
            return $this->validation(
                $response,
                'Informe seu nome com no maximo 150 caracteres.'
            );
        }

        if (
            filter_var(
                $ageRaw,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 120]]
            ) === false
        ) {
            return $this->validation(
                $response,
                'Informe uma idade valida entre 1 e 120 anos.'
            );
        }

        if ($gender === '' || strlen($gender) > 30) {
            return $this->validation(
                $response,
                'Informe o genero com no maximo 30 caracteres.'
            );
        }

        $pdo = Database::connect();

        $pdo->beginTransaction();

        try {
            $updateParticipant = $pdo->prepare(
                'UPDATE aplicacao_participantes
                    SET nome_snapshot = :nome,
                        idade_snapshot = :idade,
                        genero_snapshot = :genero,
                        status = :status,
                        iniciou_em = COALESCE(iniciou_em, NOW())
                  WHERE id = :id'
            );
            $updateParticipant->execute([
                'nome' => $name,
                'idade' => (int) $ageRaw,
                'genero' => $gender,
                'status' => 'EM_ANDAMENTO',
                'id' => (int) $access['participante_id'],
            ]);

            $updateAccess = $pdo->prepare(
                'UPDATE acessos_aplicacao
                    SET primeiro_acesso_em = COALESCE(primeiro_acesso_em, NOW()),
                        ultimo_acesso_em = NOW()
                  WHERE id = :id'
            );
            $updateAccess->execute([
                'id' => (int) $access['acesso_id'],
            ]);

            $updateApplication = $pdo->prepare(
                'UPDATE aplicacoes
                    SET status = CASE
                            WHEN status = :ready_status THEN :progress_status
                            ELSE status
                        END,
                        iniciada_em = COALESCE(iniciada_em, NOW())
                  WHERE id = :id'
            );
            $updateApplication->execute([
                'ready_status' => 'PRONTA',
                'progress_status' => 'EM_ANDAMENTO',
                'id' => (int) $access['aplicacao_id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        $updated = $this->findAccessByHash(
            $this->tokenService->hash(
                trim((string) ($args['token'] ?? ''))
            )
        );

        if ($updated === null) {
            return $this->notFound($response);
        }

        return $this->json($response, [
            'message' => 'Identificacao registrada com sucesso.',
            'acesso' => $this->serializeAccess($updated),
        ]);
    }

    public function questionnaire(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $access = $this->resolveAccess(
            trim((string) ($args['token'] ?? '')),
            $response
        );

        if ($access instanceof ResponseInterface) {
            return $access;
        }

        if (
            (string) $access['participante_status'] === 'PENDENTE'
            || $access['idade_snapshot'] === null
            || trim((string) ($access['genero_snapshot'] ?? '')) === ''
        ) {
            return $this->json($response, [
                'error' => 'identification_required',
                'message' => 'Conclua sua identificacao antes de iniciar o preenchimento.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                s.id AS secao_id,
                s.titulo AS secao_titulo,
                s.descricao AS secao_descricao,
                s.ordem AS secao_ordem,
                i.id AS item_id,
                i.codigo AS item_codigo,
                i.texto AS item_texto,
                i.tipo_resposta,
                i.ordem AS item_ordem,
                i.permite_nao_se_aplica,
                a.id AS alternativa_id,
                a.valor AS alternativa_valor,
                a.rotulo AS alternativa_rotulo,
                a.ordem AS alternativa_ordem
             FROM secoes s
             INNER JOIN itens i
               ON i.secao_id = s.id
              AND i.ativo = 1
             LEFT JOIN alternativas a
               ON a.item_id = i.id
              AND a.ativo = 1
             WHERE s.instrumento_versao_id = :versao_id
               AND s.ativo = 1
               AND NOT EXISTS (
                    SELECT 1
                    FROM aplicacao_itens_excluidos ex
                    WHERE ex.aplicacao_id = :aplicacao_id
                      AND ex.item_id = i.id
               )
             ORDER BY
                s.ordem ASC,
                s.id ASC,
                i.ordem ASC,
                i.id ASC,
                a.ordem ASC,
                a.id ASC'
        );
        $stmt->execute([
            'versao_id' => (int) $access['instrumento_versao_id'],
            'aplicacao_id' => (int) $access['aplicacao_id'],
        ]);

        $sections = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sectionId = (int) $row['secao_id'];
            $itemId = (int) $row['item_id'];

            if (!isset($sections[$sectionId])) {
                $sections[$sectionId] = [
                    'id' => $sectionId,
                    'titulo' => (string) $row['secao_titulo'],
                    'descricao' => $row['secao_descricao'],
                    'ordem' => (int) $row['secao_ordem'],
                    'itens' => [],
                ];
            }

            if (!isset($sections[$sectionId]['itens'][$itemId])) {
                $sections[$sectionId]['itens'][$itemId] = [
                    'id' => $itemId,
                    'codigo' => (string) $row['item_codigo'],
                    'texto' => (string) $row['item_texto'],
                    'tipo_resposta' => (string) $row['tipo_resposta'],
                    'ordem' => (int) $row['item_ordem'],
                    'permite_nao_se_aplica' =>
                        (bool) $row['permite_nao_se_aplica'],
                    'alternativas' => [],
                ];
            }

            if ($row['alternativa_id'] !== null) {
                $sections[$sectionId]['itens'][$itemId]['alternativas'][] = [
                    'id' => (int) $row['alternativa_id'],
                    'valor' => (string) $row['alternativa_valor'],
                    'rotulo' => (string) $row['alternativa_rotulo'],
                    'ordem' => (int) $row['alternativa_ordem'],
                ];
            }
        }

        $sections = array_values(array_map(
            static function (array $section): array {
                $section['itens'] = array_values($section['itens']);

                return $section;
            },
            $sections
        ));

        $totalItems = array_sum(array_map(
            static fn (array $section): int => count($section['itens']),
            $sections
        ));

        return $this->json($response, [
            'questionario' => [
                'avaliacao_nome' => (string) $access['avaliacao_nome'],
                'participante' => [
                    'id' => (int) $access['participante_id'],
                    'lado' => (string) $access['lado'],
                    'nome_snapshot' => $access['nome_snapshot'],
                ],
                'total_itens' => $totalItems,
                'secoes' => $sections,
            ],
        ]);
    }

    /**
     * @return array<string,mixed>|ResponseInterface
     */
    private function resolveAccess(
        string $token,
        ResponseInterface $response
    ): array|ResponseInterface {
        if (!$this->tokenService->isValidFormat($token)) {
            return $this->notFound($response);
        }

        $access = $this->findAccessByHash(
            $this->tokenService->hash($token)
        );

        if ($access === null) {
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

        if ((string) $access['aplicacao_status'] === 'CANCELADA') {
            return $this->json($response, [
                'error' => 'application_cancelled',
                'message' => 'Esta avaliacao foi cancelada.',
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

        return $access;
    }

    private function findAccessByHash(string $hash): ?array
    {
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
                a.instrumento_versao_id,
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

        return $access === false ? null : $access;
    }

    private function serializeAccess(array $access): array
    {
        return [
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
                'iniciou_em' => $access['iniciou_em'],
                'concluiu_em' => $access['concluiu_em'],
            ],
        ];
    }

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Acesso invalido ou indisponivel.',
        ], 404);
    }

    private function validation(
        ResponseInterface $response,
        string $message
    ): ResponseInterface {
        return $this->json($response, [
            'error' => 'validation_error',
            'message' => $message,
        ], 422);
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
