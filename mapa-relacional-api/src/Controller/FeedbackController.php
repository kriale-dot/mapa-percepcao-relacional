<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AccessTokenService;
use App\Service\AuditService;
use App\Service\MailService;
use App\Service\ResultService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class FeedbackController
{
    public function __construct(
        private readonly AccessTokenService $tokenService,
        private readonly MailService $mailService,
        private readonly ResultService $resultService,
        private readonly AuditService $auditService
    ) {
    }

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $applicationId = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($applicationId === null) {
            return $this->notFound($response);
        }

        $application = $this->findApplication(
            $professionalId,
            $applicationId
        );

        if ($application === null) {
            return $this->notFound($response);
        }

        return $this->json($response, [
            'aplicacao' => $this->serializeApplication($application),
            'devolutiva' => $this->findFeedback($applicationId),
        ]);
    }

    public function save(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $applicationId = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($applicationId === null) {
            return $this->notFound($response);
        }

        $application = $this->findApplication(
            $professionalId,
            $applicationId
        );

        if ($application === null) {
            return $this->notFound($response);
        }

        if ((string) $application['status'] !== 'CONCLUIDA') {
            return $this->json($response, [
                'error' => 'application_not_completed',
                'message' => 'A devolutiva so pode ser preparada depois que os dois participantes concluirem.',
            ], 409);
        }

        $current = $this->findFeedback($applicationId);

        if ($current !== null && $current['status'] === 'LIBERADA') {
            return $this->json($response, [
                'error' => 'feedback_already_released',
                'message' => 'A devolutiva ja foi liberada e esta congelada para preservar o historico.',
            ], 409);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $summary = $this->optional($data['sintese'] ?? null);
        $observations = $this->optional($data['observacoes'] ?? null);
        $comment = $this->optional($data['comentario_profissional'] ?? null);

        if ($summary !== null && strlen($summary) > 5000) {
            return $this->validation(
                $response,
                'A sintese deve ter no maximo 5000 caracteres.'
            );
        }

        if ($observations !== null && strlen($observations) > 10000) {
            return $this->validation(
                $response,
                'As observacoes devem ter no maximo 10000 caracteres.'
            );
        }

        if ($comment !== null && strlen($comment) > 10000) {
            return $this->validation(
                $response,
                'O comentario profissional deve ter no maximo 10000 caracteres.'
            );
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'INSERT INTO devolutivas (
                aplicacao_id,
                profissional_id,
                sintese,
                observacoes,
                comentario_profissional,
                status
             ) VALUES (
                :aplicacao_id,
                :profissional_id,
                :sintese,
                :observacoes,
                :comentario_profissional,
                :status
             )
             ON DUPLICATE KEY UPDATE
                sintese = :u_sintese,
                observacoes = :u_observacoes,
                comentario_profissional = :u_comentario_profissional,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
            'profissional_id' => $professionalId,
            'sintese' => $summary,
            'observacoes' => $observations,
            'comentario_profissional' => $comment,
            'status' => 'RASCUNHO',
            'u_sintese' => $summary,
            'u_observacoes' => $observations,
            'u_comentario_profissional' => $comment,
        ]);

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'DEVOLUTIVA_RASCUNHO_SALVA',
            'APLICACAO',
            $applicationId,
            [],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Devolutiva salva como rascunho.',
            'devolutiva' => $this->findFeedback($applicationId),
        ]);
    }

    public function release(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $applicationId = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($applicationId === null) {
            return $this->notFound($response);
        }

        $application = $this->findApplication(
            $professionalId,
            $applicationId
        );

        if ($application === null) {
            return $this->notFound($response);
        }

        if ((string) $application['status'] !== 'CONCLUIDA') {
            return $this->json($response, [
                'error' => 'application_not_completed',
                'message' => 'A devolutiva so pode ser liberada depois que os dois participantes concluirem.',
            ], 409);
        }

        $feedback = $this->findFeedback($applicationId);

        if ($feedback !== null && $feedback['status'] === 'LIBERADA') {
            return $this->json($response, [
                'error' => 'feedback_already_released',
                'message' => 'A devolutiva ja foi liberada para os participantes.',
            ], 409);
        }

        $pdo = Database::connect();

        $resultCountStmt = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*)
                   FROM resultados
                  WHERE aplicacao_id = :aplicacao_id_direcional)
                +
                (SELECT COUNT(*)
                   FROM resultados_gerais
                  WHERE aplicacao_id = :aplicacao_id_geral)'
        );
        $resultCountStmt->execute([
            'aplicacao_id_direcional' => $applicationId,
            'aplicacao_id_geral' => $applicationId,
        ]);

        if ((int) $resultCountStmt->fetchColumn() < 3) {
            $pdo->beginTransaction();

            try {
                $this->resultService->calculate($applicationId, $pdo);
                $pdo->commit();
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $error;
            }
        }

        if ($feedback === null) {
            $create = $pdo->prepare(
                'INSERT INTO devolutivas (
                    aplicacao_id,
                    profissional_id,
                    status
                 ) VALUES (
                    :aplicacao_id,
                    :profissional_id,
                    :status
                 )'
            );
            $create->execute([
                'aplicacao_id' => $applicationId,
                'profissional_id' => $professionalId,
                'status' => 'RASCUNHO',
            ]);
        }

        $tokenData = $this->tokenService->generate();
        $link = $this->buildPublicResultLink($tokenData['token']);

        $tokenStmt = $pdo->prepare(
            'UPDATE devolutivas
                SET token_hash = :token_hash
              WHERE aplicacao_id = :aplicacao_id
                AND status = :status'
        );
        $tokenStmt->execute([
            'token_hash' => $tokenData['hash'],
            'aplicacao_id' => $applicationId,
            'status' => 'RASCUNHO',
        ]);

        try {
            $this->mailService->sendResultRelease(
                (string) $application['email_contato'],
                (string) $application['instrumento_nome'],
                (string) $application['participante_a_nome'],
                (string) $application['participante_b_nome'],
                $link
            );
        } catch (Throwable $error) {
            $clear = $pdo->prepare(
                'UPDATE devolutivas
                    SET token_hash = NULL
                  WHERE aplicacao_id = :aplicacao_id
                    AND status = :status'
            );
            $clear->execute([
                'aplicacao_id' => $applicationId,
                'status' => 'RASCUNHO',
            ]);

            return $this->json($response, [
                'error' => 'email_delivery_failed',
                'message' => 'Nao foi possivel enviar o link da devolutiva para o e-mail cadastrado.',
            ], 502);
        }

        $release = $pdo->prepare(
            'UPDATE devolutivas
                SET status = :released_status,
                    liberada_em = NOW(),
                    enviado_em = NOW()
              WHERE aplicacao_id = :aplicacao_id
                AND status = :draft_status'
        );
        $release->execute([
            'released_status' => 'LIBERADA',
            'aplicacao_id' => $applicationId,
            'draft_status' => 'RASCUNHO',
        ]);

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'DEVOLUTIVA_LIBERADA',
            'APLICACAO',
            $applicationId,
            ['email_enviado' => true],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Devolutiva liberada e enviada para o e-mail cadastrado.',
            'email_enviado' => true,
            'devolutiva' => $this->findFeedback($applicationId),
        ]);
    }

    public function publicShow(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $token = trim((string) ($args['token'] ?? ''));

        if (!$this->tokenService->isValidFormat($token)) {
            return $this->publicNotFound($response);
        }

        $hash = $this->tokenService->hash($token);
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                d.aplicacao_id,
                d.sintese,
                d.observacoes,
                d.comentario_profissional,
                d.liberada_em,
                a.status AS aplicacao_status,
                a.tipo_vinculo_snapshot,
                a.duracao_vinculo_texto,
                i.nome AS instrumento_nome,
                pa.nome_snapshot AS participante_a_nome,
                pb.nome_snapshot AS participante_b_nome
             FROM devolutivas d
             INNER JOIN aplicacoes a
               ON a.id = d.aplicacao_id
             INNER JOIN instrumento_versoes v
               ON v.id = a.instrumento_versao_id
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             INNER JOIN aplicacao_participantes pa
               ON pa.aplicacao_id = a.id
              AND pa.lado = \'A\'
             INNER JOIN aplicacao_participantes pb
               ON pb.aplicacao_id = a.id
              AND pb.lado = \'B\'
             WHERE d.token_hash = :token_hash
               AND d.status = :status
               AND a.status = :application_status
             LIMIT 1'
        );
        $stmt->execute([
            'token_hash' => $hash,
            'status' => 'LIBERADA',
            'application_status' => 'CONCLUIDA',
        ]);

        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($feedback === false) {
            return $this->publicNotFound($response);
        }

        $result = $this->resultService->getResults(
            (int) $feedback['aplicacao_id']
        );

        $excludedItems = array_map(
            static fn (array $item): array => [
                'item_codigo' => $item['item_codigo'],
                'item_texto' => $item['item_texto'],
                'secao_titulo' => $item['secao_titulo'],
            ],
            $result['itens_excluidos'] ?? []
        );

        return $this->json($response, [
            'devolutiva' => [
                'avaliacao_nome' => (string) $feedback['instrumento_nome'],
                'participante_a' => (string) $feedback['participante_a_nome'],
                'participante_b' => (string) $feedback['participante_b_nome'],
                'tipo_vinculo' => (string) $feedback['tipo_vinculo_snapshot'],
                'tempo_uniao' => $feedback['duracao_vinculo_texto'],
                'sintese' => $feedback['sintese'],
                'observacoes' => $feedback['observacoes'],
                'comentario_profissional' =>
                    $feedback['comentario_profissional'],
                'liberada_em' => $feedback['liberada_em'],
                'resultados' => $result['resultados'],
                'resultado_geral' => $result['resultado_geral'],
                'secoes' => $result['secoes'],
                'itens_excluidos' => $excludedItems,
            ],
        ]);
    }

    private function findApplication(
        int $professionalId,
        int $applicationId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                a.id,
                a.profissional_id,
                a.status,
                a.email_contato,
                a.tipo_vinculo_snapshot,
                a.duracao_vinculo_texto,
                i.nome AS instrumento_nome,
                pa.nome_snapshot AS participante_a_nome,
                pb.nome_snapshot AS participante_b_nome
             FROM aplicacoes a
             INNER JOIN instrumento_versoes v
               ON v.id = a.instrumento_versao_id
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             INNER JOIN aplicacao_participantes pa
               ON pa.aplicacao_id = a.id
              AND pa.lado = \'A\'
             INNER JOIN aplicacao_participantes pb
               ON pb.aplicacao_id = a.id
              AND pb.lado = \'B\'
             WHERE a.id = :aplicacao_id
               AND a.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
            'profissional_id' => $professionalId,
        ]);

        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        return $application === false ? null : $application;
    }

    private function findFeedback(int $applicationId): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                id,
                aplicacao_id,
                profissional_id,
                sintese,
                observacoes,
                comentario_profissional,
                status,
                liberada_em,
                enviado_em,
                created_at,
                updated_at
             FROM devolutivas
             WHERE aplicacao_id = :aplicacao_id
             LIMIT 1'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
        ]);

        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($feedback === false) {
            return null;
        }

        $feedback['id'] = (int) $feedback['id'];
        $feedback['aplicacao_id'] = (int) $feedback['aplicacao_id'];
        $feedback['profissional_id'] = (int) $feedback['profissional_id'];

        return $feedback;
    }

    private function serializeApplication(array $application): array
    {
        return [
            'id' => (int) $application['id'],
            'status' => (string) $application['status'],
            'email_contato' => (string) $application['email_contato'],
            'avaliacao_nome' => (string) $application['instrumento_nome'],
            'participante_a' => (string) $application['participante_a_nome'],
            'participante_b' => (string) $application['participante_b_nome'],
            'tipo_vinculo' => (string) $application['tipo_vinculo_snapshot'],
            'tempo_uniao' => $application['duracao_vinculo_texto'],
        ];
    }

    private function buildPublicResultLink(string $token): string
    {
        $frontendUrl = rtrim(
            trim((string) ($_ENV['FRONTEND_URL'] ?? '')),
            '/'
        );

        if (
            $frontendUrl === ''
            || filter_var($frontendUrl, FILTER_VALIDATE_URL) === false
        ) {
            throw new \RuntimeException(
                'FRONTEND_URL invalida para gerar o link da devolutiva.'
            );
        }

        return $frontendUrl . '/resultado/' . $token;
    }

    private function professionalId(ServerRequestInterface $request): ?int
    {
        $professional = $request->getAttribute('auth.professional');

        if (!is_array($professional)) {
            return null;
        }

        $id = (int) ($professional['id'] ?? 0);

        return $id > 0 ? $id : null;
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

    private function optional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function unauthorized(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'unauthorized',
            'message' => 'Autenticacao profissional obrigatoria.',
        ], 401);
    }

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Aplicacao nao encontrada.',
        ], 404);
    }

    private function publicNotFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Devolutiva indisponivel ou link invalido.',
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
