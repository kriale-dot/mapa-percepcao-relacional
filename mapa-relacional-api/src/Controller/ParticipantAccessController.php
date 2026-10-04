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

        $savedResponses = $this->listSavedResponses(
            (int) $access['aplicacao_id'],
            (int) $access['participante_id']
        );

        $responseMap = [];

        foreach ($savedResponses as $savedResponse) {
            $savedItemId = (int) $savedResponse['item_id'];

            if (!isset($responseMap[$savedItemId])) {
                $responseMap[$savedItemId] = [
                    'sobre_mim' => null,
                    'sobre_outro' => null,
                ];
            }

            $perspective = (int) $savedResponse['alvo_id']
                === (int) $access['participante_id']
                    ? 'sobre_mim'
                    : 'sobre_outro';

            $responseMap[$savedItemId][$perspective] =
                $this->serializeSavedResponse(
                    $savedResponse,
                    (int) $access['participante_id']
                );
        }

        $sections = array_map(
            static function (array $section) use ($responseMap): array {
                $section['itens'] = array_map(
                    static function (array $item) use ($responseMap): array {
                        $item['respostas'] = $responseMap[$item['id']]
                            ?? [
                                'sobre_mim' => null,
                                'sobre_outro' => null,
                            ];

                        return $item;
                    },
                    $section['itens']
                );

                return $section;
            },
            $sections
        );

        return $this->json($response, [
            'questionario' => [
                'avaliacao_nome' => (string) $access['avaliacao_nome'],
                'participante' => [
                    'id' => (int) $access['participante_id'],
                    'lado' => (string) $access['lado'],
                    'nome_snapshot' => $access['nome_snapshot'],
                ],
                'total_itens' => $totalItems,
                'progresso' => $this->calculateProgress($access),
                'secoes' => $sections,
            ],
        ]);
    }

    public function saveResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $token = trim((string) ($args['token'] ?? ''));
        $access = $this->resolveAccess($token, $response);

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
                'message' => 'Conclua sua identificacao antes de responder.',
            ], 409);
        }

        $itemId = $this->positiveId($args['itemId'] ?? null);

        if ($itemId === null) {
            return $this->json($response, [
                'error' => 'item_not_found',
                'message' => 'Item nao encontrado nesta avaliacao.',
            ], 404);
        }

        $item = $this->findAnswerableItem(
            (int) $access['instrumento_versao_id'],
            (int) $access['aplicacao_id'],
            $itemId
        );

        if ($item === null) {
            return $this->json($response, [
                'error' => 'item_not_available',
                'message' => 'Este item nao esta disponivel para resposta.',
            ], 409);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $perspective = strtoupper(trim(
            (string) ($data['perspectiva'] ?? '')
        ));

        if (!in_array($perspective, ['SOBRE_MIM', 'SOBRE_OUTRO'], true)) {
            return $this->validation(
                $response,
                'Perspectiva invalida. Use SOBRE_MIM ou SOBRE_OUTRO.'
            );
        }

        $respondentId = (int) $access['participante_id'];
        $otherParticipantId = $this->findOtherParticipantId(
            (int) $access['aplicacao_id'],
            $respondentId
        );

        if ($otherParticipantId === null) {
            return $this->json($response, [
                'error' => 'participant_pair_incomplete',
                'message' => 'Nao foi possivel identificar o outro participante.',
            ], 409);
        }

        $targetId = $perspective === 'SOBRE_MIM'
            ? $respondentId
            : $otherParticipantId;

        $alternativeId = $this->nullablePositiveId(
            $data['alternativa_id'] ?? null
        );
        $textValue = $this->optional($data['valor_texto'] ?? null);
        $numberValueRaw = $data['valor_numero'] ?? null;
        $numberValue = null;

        if ($numberValueRaw !== null && $numberValueRaw !== '') {
            if (!is_numeric($numberValueRaw)) {
                return $this->validation(
                    $response,
                    'O valor numerico informado e invalido.'
                );
            }

            $numberValue = (string) $numberValueRaw;
        }

        $activeAlternativeCount = (int) $item['total_alternativas_ativas'];

        if ($activeAlternativeCount > 0) {
            if ($alternativeId === null) {
                return $this->validation(
                    $response,
                    'Selecione uma das alternativas disponiveis.'
                );
            }

            if (
                !$this->alternativeBelongsToItem(
                    $itemId,
                    $alternativeId
                )
            ) {
                return $this->validation(
                    $response,
                    'A alternativa selecionada nao pertence a este item.'
                );
            }

            $textValue = null;
            $numberValue = null;
        } else {
            if ($alternativeId !== null) {
                return $this->validation(
                    $response,
                    'Este item nao utiliza alternativas cadastradas.'
                );
            }

            $filledValues = ($textValue !== null ? 1 : 0)
                + ($numberValue !== null ? 1 : 0);

            if ($filledValues !== 1) {
                return $this->validation(
                    $response,
                    'Informe exatamente uma resposta textual ou numerica.'
                );
            }

            if ($textValue !== null && strlen($textValue) > 10000) {
                return $this->validation(
                    $response,
                    'A resposta textual deve ter no maximo 10000 caracteres.'
                );
            }
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'INSERT INTO respostas (
                aplicacao_id,
                respondente_id,
                alvo_id,
                item_id,
                alternativa_id,
                valor_texto,
                valor_numero,
                nao_se_aplica,
                respondido_em
             ) VALUES (
                :aplicacao_id,
                :respondente_id,
                :alvo_id,
                :item_id,
                :alternativa_id,
                :valor_texto,
                :valor_numero,
                0,
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                alternativa_id = :u_alternativa_id,
                valor_texto = :u_valor_texto,
                valor_numero = :u_valor_numero,
                nao_se_aplica = 0,
                respondido_em = NOW()'
        );
        $stmt->execute([
            'aplicacao_id' => (int) $access['aplicacao_id'],
            'respondente_id' => $respondentId,
            'alvo_id' => $targetId,
            'item_id' => $itemId,
            'alternativa_id' => $alternativeId,
            'valor_texto' => $textValue,
            'valor_numero' => $numberValue,
            'u_alternativa_id' => $alternativeId,
            'u_valor_texto' => $textValue,
            'u_valor_numero' => $numberValue,
        ]);

        $updateAccess = $pdo->prepare(
            'UPDATE acessos_aplicacao
                SET ultimo_acesso_em = NOW()
              WHERE id = :id'
        );
        $updateAccess->execute([
            'id' => (int) $access['acesso_id'],
        ]);

        $saved = $this->findSavedResponse(
            (int) $access['aplicacao_id'],
            $respondentId,
            $targetId,
            $itemId
        );

        return $this->json($response, [
            'message' => 'Resposta salva com sucesso.',
            'resposta' => $saved === null
                ? null
                : $this->serializeSavedResponse(
                    $saved,
                    $respondentId
                ),
            'progresso' => $this->calculateProgress($access),
        ]);
    }

    public function excludeItem(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $token = trim((string) ($args['token'] ?? ''));
        $access = $this->resolveAccess($token, $response);

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
                'message' => 'Conclua sua identificacao antes de marcar Nao se aplica.',
            ], 409);
        }

        $itemId = $this->positiveId($args['itemId'] ?? null);

        if ($itemId === null) {
            return $this->json($response, [
                'error' => 'item_not_found',
                'message' => 'Item nao encontrado nesta avaliacao.',
            ], 404);
        }

        $item = $this->findExcludableItem(
            (int) $access['instrumento_versao_id'],
            $itemId
        );

        if ($item === null) {
            return $this->json($response, [
                'error' => 'not_applicable_not_allowed',
                'message' => 'Este item nao permite a opcao Nao se aplica.',
            ], 409);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $reason = $this->optional($data['motivo'] ?? null);

        if ($reason !== null && strlen($reason) > 255) {
            return $this->validation(
                $response,
                'O motivo deve ter no maximo 255 caracteres.'
            );
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO aplicacao_itens_excluidos (
                aplicacao_id,
                item_id,
                marcado_por_participante_id,
                motivo
             ) VALUES (
                :aplicacao_id,
                :item_id,
                :participante_id,
                :motivo
             )'
        );
        $stmt->execute([
            'aplicacao_id' => (int) $access['aplicacao_id'],
            'item_id' => $itemId,
            'participante_id' => (int) $access['participante_id'],
            'motivo' => $reason,
        ]);

        $touch = $pdo->prepare(
            'UPDATE acessos_aplicacao
                SET ultimo_acesso_em = NOW()
              WHERE id = :id'
        );
        $touch->execute([
            'id' => (int) $access['acesso_id'],
        ]);

        return $this->json($response, [
            'message' => 'Item marcado como Nao se aplica para esta avaliacao.',
            'item_id' => $itemId,
            'progresso' => $this->calculateProgress($access),
        ]);
    }

    public function complete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $token = trim((string) ($args['token'] ?? ''));
        $access = $this->resolveAccess($token, $response);

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
                'message' => 'Conclua sua identificacao antes de finalizar.',
            ], 409);
        }

        $progress = $this->calculateProgress($access);

        if ((int) $progress['respondidas'] !== (int) $progress['total']) {
            return $this->json($response, [
                'error' => 'incomplete_questionnaire',
                'message' => 'Responda todas as perspectivas dos itens validos antes de concluir.',
                'progresso' => $progress,
            ], 409);
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $participantStmt = $pdo->prepare(
                'UPDATE aplicacao_participantes
                    SET status = :status,
                        concluiu_em = COALESCE(concluiu_em, NOW())
                  WHERE id = :id'
            );
            $participantStmt->execute([
                'status' => 'CONCLUIDO',
                'id' => (int) $access['participante_id'],
            ]);

            $accessStmt = $pdo->prepare(
                'UPDATE acessos_aplicacao
                    SET status = :status,
                        ultimo_acesso_em = NOW(),
                        concluido_em = COALESCE(concluido_em, NOW())
                  WHERE id = :id'
            );
            $accessStmt->execute([
                'status' => 'CONCLUIDO',
                'id' => (int) $access['acesso_id'],
            ]);

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*)
                   FROM aplicacao_participantes
                  WHERE aplicacao_id = :aplicacao_id
                    AND status = :status'
            );
            $countStmt->execute([
                'aplicacao_id' => (int) $access['aplicacao_id'],
                'status' => 'CONCLUIDO',
            ]);
            $completedParticipants = (int) $countStmt->fetchColumn();

            $applicationCompleted = $completedParticipants === 2;

            if ($applicationCompleted) {
                $applicationStmt = $pdo->prepare(
                    'UPDATE aplicacoes
                        SET status = :status,
                            concluida_em = COALESCE(concluida_em, NOW())
                      WHERE id = :id'
                );
                $applicationStmt->execute([
                    'status' => 'CONCLUIDA',
                    'id' => (int) $access['aplicacao_id'],
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        return $this->json($response, [
            'message' => $applicationCompleted
                ? 'Avaliacao concluida pelos dois participantes.'
                : 'Sua participacao foi concluida com sucesso.',
            'participante_status' => 'CONCLUIDO',
            'aplicacao_status' => $applicationCompleted
                ? 'CONCLUIDA'
                : 'EM_ANDAMENTO',
            'ambos_concluidos' => $applicationCompleted,
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

    private function findExcludableItem(
        int $versionId,
        int $itemId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                i.id,
                i.codigo,
                i.texto
             FROM itens i
             INNER JOIN secoes s
               ON s.id = i.secao_id
             WHERE i.id = :item_id
               AND i.ativo = 1
               AND i.permite_nao_se_aplica = 1
               AND s.instrumento_versao_id = :versao_id
               AND s.ativo = 1
             LIMIT 1'
        );
        $stmt->execute([
            'item_id' => $itemId,
            'versao_id' => $versionId,
        ]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        return $item === false ? null : $item;
    }

    private function findAnswerableItem(
        int $versionId,
        int $applicationId,
        int $itemId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                i.id,
                i.tipo_resposta,
                i.permite_nao_se_aplica,
                (
                    SELECT COUNT(*)
                    FROM alternativas a
                    WHERE a.item_id = i.id
                      AND a.ativo = 1
                ) AS total_alternativas_ativas
             FROM itens i
             INNER JOIN secoes s
               ON s.id = i.secao_id
             WHERE i.id = :item_id
               AND i.ativo = 1
               AND s.instrumento_versao_id = :versao_id
               AND s.ativo = 1
               AND NOT EXISTS (
                    SELECT 1
                    FROM aplicacao_itens_excluidos ex
                    WHERE ex.aplicacao_id = :aplicacao_id
                      AND ex.item_id = i.id
               )
             LIMIT 1'
        );
        $stmt->execute([
            'item_id' => $itemId,
            'versao_id' => $versionId,
            'aplicacao_id' => $applicationId,
        ]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        return $item === false ? null : $item;
    }

    private function alternativeBelongsToItem(
        int $itemId,
        int $alternativeId
    ): bool {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT 1
             FROM alternativas
             WHERE id = :id
               AND item_id = :item_id
               AND ativo = 1
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $alternativeId,
            'item_id' => $itemId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function findOtherParticipantId(
        int $applicationId,
        int $respondentId
    ): ?int {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT id
             FROM aplicacao_participantes
             WHERE aplicacao_id = :aplicacao_id
               AND id <> :respondente_id
             ORDER BY id ASC
             LIMIT 1'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
            'respondente_id' => $respondentId,
        ]);

        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function listSavedResponses(
        int $applicationId,
        int $respondentId
    ): array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                r.id,
                r.item_id,
                r.alvo_id,
                r.alternativa_id,
                r.valor_texto,
                r.valor_numero,
                r.nao_se_aplica,
                r.respondido_em,
                a.rotulo AS alternativa_rotulo,
                a.valor AS alternativa_valor
             FROM respostas r
             LEFT JOIN alternativas a
               ON a.id = r.alternativa_id
             WHERE r.aplicacao_id = :aplicacao_id
               AND r.respondente_id = :respondente_id
             ORDER BY r.item_id ASC, r.id ASC'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
            'respondente_id' => $respondentId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findSavedResponse(
        int $applicationId,
        int $respondentId,
        int $targetId,
        int $itemId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                r.id,
                r.item_id,
                r.alvo_id,
                r.alternativa_id,
                r.valor_texto,
                r.valor_numero,
                r.nao_se_aplica,
                r.respondido_em,
                a.rotulo AS alternativa_rotulo,
                a.valor AS alternativa_valor
             FROM respostas r
             LEFT JOIN alternativas a
               ON a.id = r.alternativa_id
             WHERE r.aplicacao_id = :aplicacao_id
               AND r.respondente_id = :respondente_id
               AND r.alvo_id = :alvo_id
               AND r.item_id = :item_id
             LIMIT 1'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
            'respondente_id' => $respondentId,
            'alvo_id' => $targetId,
            'item_id' => $itemId,
        ]);

        $saved = $stmt->fetch(PDO::FETCH_ASSOC);

        return $saved === false ? null : $saved;
    }

    private function serializeSavedResponse(
        array $saved,
        int $respondentId
    ): array {
        return [
            'id' => (int) $saved['id'],
            'item_id' => (int) $saved['item_id'],
            'perspectiva' => (int) $saved['alvo_id'] === $respondentId
                ? 'SOBRE_MIM'
                : 'SOBRE_OUTRO',
            'alternativa_id' => $saved['alternativa_id'] === null
                ? null
                : (int) $saved['alternativa_id'],
            'alternativa_rotulo' => $saved['alternativa_rotulo'],
            'alternativa_valor' => $saved['alternativa_valor'],
            'valor_texto' => $saved['valor_texto'],
            'valor_numero' => $saved['valor_numero'],
            'nao_se_aplica' => (bool) $saved['nao_se_aplica'],
            'respondido_em' => $saved['respondido_em'],
        ];
    }

    private function calculateProgress(array $access): array
    {
        $pdo = Database::connect();

        $totalStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM itens i
             INNER JOIN secoes s
               ON s.id = i.secao_id
             WHERE s.instrumento_versao_id = :versao_id
               AND s.ativo = 1
               AND i.ativo = 1
               AND NOT EXISTS (
                    SELECT 1
                    FROM aplicacao_itens_excluidos ex
                    WHERE ex.aplicacao_id = :aplicacao_id
                      AND ex.item_id = i.id
               )'
        );
        $totalStmt->execute([
            'versao_id' => (int) $access['instrumento_versao_id'],
            'aplicacao_id' => (int) $access['aplicacao_id'],
        ]);

        $totalItems = (int) $totalStmt->fetchColumn();
        $totalPerspectives = $totalItems * 2;

        $answeredStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM respostas r
             INNER JOIN itens i
               ON i.id = r.item_id
              AND i.ativo = 1
             INNER JOIN secoes s
               ON s.id = i.secao_id
              AND s.ativo = 1
             WHERE r.aplicacao_id = :aplicacao_id
               AND r.respondente_id = :respondente_id
               AND r.nao_se_aplica = 0
               AND s.instrumento_versao_id = :versao_id
               AND NOT EXISTS (
                    SELECT 1
                    FROM aplicacao_itens_excluidos ex
                    WHERE ex.aplicacao_id = r.aplicacao_id
                      AND ex.item_id = r.item_id
               )'
        );
        $answeredStmt->execute([
            'aplicacao_id' => (int) $access['aplicacao_id'],
            'respondente_id' => (int) $access['participante_id'],
            'versao_id' => (int) $access['instrumento_versao_id'],
        ]);

        $answeredPerspectives = (int) $answeredStmt->fetchColumn();
        $percentage = $totalPerspectives > 0
            ? round(($answeredPerspectives / $totalPerspectives) * 100, 1)
            : 0.0;

        return [
            'respondidas' => $answeredPerspectives,
            'total' => $totalPerspectives,
            'percentual' => $percentage,
        ];
    }

    private function positiveId(mixed $value): ?int
    {
        if (
            !is_scalar($value)
            || preg_match('/^[1-9][0-9]*$/', (string) $value) !== 1
        ) {
            return null;
        }

        return (int) $value;
    }

    private function nullablePositiveId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->positiveId($value);
    }

    private function optional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
