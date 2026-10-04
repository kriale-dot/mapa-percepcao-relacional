<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AccessTokenService;
use App\Service\MailService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class PublicEvaluationController
{
    public function __construct(
        private readonly AccessTokenService $tokenService,
        private readonly MailService $mailService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $pdo = Database::connect();

        $stmt = $pdo->query(
            'SELECT
                i.id AS instrumento_id,
                i.nome,
                i.descricao,
                v.id AS versao_id,
                v.numero_versao,
                p.id AS profissional_id,
                p.nome AS profissional_nome
             FROM instrumentos i
             INNER JOIN profissionais p
               ON p.id = i.profissional_id
              AND p.status = \'ATIVO\'
             INNER JOIN instrumento_versoes v
               ON v.id = (
                    SELECT v2.id
                    FROM instrumento_versoes v2
                    WHERE v2.instrumento_id = i.id
                      AND v2.status = \'PUBLICADA\'
                    ORDER BY
                        COALESCE(v2.publicado_em, v2.created_at) DESC,
                        v2.id DESC
                    LIMIT 1
               )
             WHERE i.status = \'ATIVO\'
             ORDER BY i.nome ASC, i.id ASC'
        );

        $evaluations = array_map(
            [$this, 'normalizeEvaluation'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'avaliacoes' => $evaluations,
        ]);
    }

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($versionId === null) {
            return $this->notFound($response);
        }

        $evaluation = $this->findPublicEvaluation($versionId);

        if ($evaluation === null) {
            return $this->notFound($response);
        }

        return $this->json($response, [
            'avaliacao' => $evaluation,
        ]);
    }

    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($versionId === null) {
            return $this->notFound($response);
        }

        $evaluation = $this->findPublicEvaluation($versionId);

        if ($evaluation === null) {
            return $this->notFound($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $nameA = trim((string) ($data['participante_a_nome'] ?? ''));
        $nameB = trim((string) ($data['participante_b_nome'] ?? ''));
        $email = trim((string) ($data['email_contato'] ?? ''));
        $relationshipType = strtoupper(trim(
            (string) ($data['tipo_vinculo'] ?? '')
        ));
        $timeTogether = $this->optional($data['tempo_uniao'] ?? null);

        if ($nameA === '' || strlen($nameA) > 150) {
            return $this->validation(
                $response,
                'Informe o nome do participante A.'
            );
        }

        if ($nameB === '' || strlen($nameB) > 150) {
            return $this->validation(
                $response,
                'Informe o nome do participante B.'
            );
        }

        if (
            $email === ''
            || strlen($email) > 190
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            return $this->validation(
                $response,
                'Informe um e-mail de contato valido.'
            );
        }

        if ($relationshipType === '' || strlen($relationshipType) > 50) {
            return $this->validation(
                $response,
                'Informe o tipo do vinculo.'
            );
        }

        if ($timeTogether !== null && strlen($timeTogether) > 100) {
            return $this->validation(
                $response,
                'Tempo de uniao deve ter no maximo 100 caracteres.'
            );
        }

        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            $insertApplication = $pdo->prepare(
                'INSERT INTO aplicacoes (
                    profissional_id,
                    vinculo_id,
                    instrumento_versao_id,
                    email_contato,
                    tipo_vinculo_snapshot,
                    duracao_vinculo_texto,
                    status
                 ) VALUES (
                    :profissional_id,
                    NULL,
                    :instrumento_versao_id,
                    :email_contato,
                    :tipo_vinculo_snapshot,
                    :tempo_uniao,
                    :status
                 )'
            );
            $insertApplication->execute([
                'profissional_id' => $evaluation['profissional_id'],
                'instrumento_versao_id' => $evaluation['versao_id'],
                'email_contato' => $email,
                'tipo_vinculo_snapshot' => $relationshipType,
                'tempo_uniao' => $timeTogether,
                'status' => 'RASCUNHO',
            ]);

            $applicationId = (int) $pdo->lastInsertId();

            $insertParticipant = $pdo->prepare(
                'INSERT INTO aplicacao_participantes (
                    aplicacao_id,
                    pessoa_id,
                    lado,
                    nome_snapshot,
                    idade_snapshot,
                    genero_snapshot,
                    status
                 ) VALUES (
                    :aplicacao_id,
                    NULL,
                    :lado,
                    :nome_snapshot,
                    NULL,
                    NULL,
                    :status
                 )'
            );

            $insertParticipant->execute([
                'aplicacao_id' => $applicationId,
                'lado' => 'A',
                'nome_snapshot' => $nameA,
                'status' => 'PENDENTE',
            ]);
            $participantAId = (int) $pdo->lastInsertId();

            $insertParticipant->execute([
                'aplicacao_id' => $applicationId,
                'lado' => 'B',
                'nome_snapshot' => $nameB,
                'status' => 'PENDENTE',
            ]);
            $participantBId = (int) $pdo->lastInsertId();

            $accessA = $this->tokenService->generate();
            $accessB = $this->tokenService->generate();

            $insertAccess = $pdo->prepare(
                'INSERT INTO acessos_aplicacao (
                    aplicacao_participante_id,
                    token_hash,
                    status
                 ) VALUES (
                    :participante_id,
                    :token_hash,
                    :status
                 )'
            );

            $insertAccess->execute([
                'participante_id' => $participantAId,
                'token_hash' => $accessA['hash'],
                'status' => 'ATIVO',
            ]);
            $accessAId = (int) $pdo->lastInsertId();

            $insertAccess->execute([
                'participante_id' => $participantBId,
                'token_hash' => $accessB['hash'],
                'status' => 'ATIVO',
            ]);
            $accessBId = (int) $pdo->lastInsertId();

            $linkA = $this->buildAccessLink($accessA['token']);
            $linkB = $this->buildAccessLink($accessB['token']);

            try {
                $this->mailService->sendParticipantAccessLinks(
                    $email,
                    (string) $evaluation['nome'],
                    [
                        'name' => $nameA,
                        'link' => $linkA,
                    ],
                    [
                        'name' => $nameB,
                        'link' => $linkB,
                    ]
                );
            } catch (Throwable $mailError) {
                $pdo->rollBack();

                $this->logger->error('Falha ao enviar acessos por SMTP.', [
                    'exception' => $mailError::class,
                    'message' => $mailError->getMessage(),
                ]);

                $payload = [
                    'error' => 'email_delivery_failed',
                    'message' => 'Nao foi possivel enviar os links de acesso para o e-mail informado. Tente novamente.',
                ];

                if (filter_var(
                    $_ENV['APP_DEBUG'] ?? false,
                    FILTER_VALIDATE_BOOL
                )) {
                    $payload['details'] = $mailError->getMessage();
                }

                return $this->json($response, $payload, 502);
            }

            $markAccessSent = $pdo->prepare(
                'UPDATE acessos_aplicacao
                    SET enviado_em = NOW()
                  WHERE id IN (:acesso_a_id, :acesso_b_id)'
            );
            $markAccessSent->execute([
                'acesso_a_id' => $accessAId,
                'acesso_b_id' => $accessBId,
            ]);

            $markApplicationReady = $pdo->prepare(
                'UPDATE aplicacoes
                    SET status = :status,
                        enviado_em = NOW()
                  WHERE id = :id'
            );
            $markApplicationReady->execute([
                'status' => 'PRONTA',
                'id' => $applicationId,
            ]);

            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        return $this->json(
            $response,
            [
                'message' => 'Avaliacao iniciada e acessos enviados com sucesso.',
                'email_enviado' => true,
                'aplicacao' => [
                    'id' => $applicationId,
                    'status' => 'PRONTA',
                    'avaliacao_nome' => $evaluation['nome'],
                    'email_contato' => $email,
                    'tipo_vinculo' => $relationshipType,
                    'tempo_uniao' => $timeTogether,
                    'participante_a' => [
                        'lado' => 'A',
                        'nome_snapshot' => $nameA,
                        'status' => 'PENDENTE',
                    ],
                    'participante_b' => [
                        'lado' => 'B',
                        'nome_snapshot' => $nameB,
                        'status' => 'PENDENTE',
                    ],
                ],
            ],
            201
        );
    }

    private function findPublicEvaluation(int $versionId): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                i.id AS instrumento_id,
                i.nome,
                i.descricao,
                v.id AS versao_id,
                v.numero_versao,
                p.id AS profissional_id,
                p.nome AS profissional_nome
             FROM instrumento_versoes v
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             INNER JOIN profissionais p
               ON p.id = i.profissional_id
             WHERE v.id = :versao_id
               AND v.status = \'PUBLICADA\'
               AND i.status = \'ATIVO\'
               AND p.status = \'ATIVO\'
               AND v.id = (
                    SELECT v2.id
                    FROM instrumento_versoes v2
                    WHERE v2.instrumento_id = i.id
                      AND v2.status = \'PUBLICADA\'
                    ORDER BY
                        COALESCE(v2.publicado_em, v2.created_at) DESC,
                        v2.id DESC
                    LIMIT 1
               )
             LIMIT 1'
        );
        $stmt->execute(['versao_id' => $versionId]);

        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($evaluation === false) {
            return null;
        }

        return $this->normalizeEvaluation($evaluation);
    }

    private function buildAccessLink(string $token): string
    {
        $frontendUrl = rtrim(
            trim((string) ($_ENV['FRONTEND_URL'] ?? '')),
            '/'
        );

        if (
            $frontendUrl === ''
            || filter_var($frontendUrl, FILTER_VALIDATE_URL) === false
        ) {
            throw new RuntimeException(
                'FRONTEND_URL invalida para gerar os links de acesso.'
            );
        }

        return $frontendUrl . '/avaliacao/acesso/' . $token;
    }

    private function normalizeEvaluation(array $evaluation): array
    {
        $evaluation['instrumento_id'] = (int) $evaluation['instrumento_id'];
        $evaluation['versao_id'] = (int) $evaluation['versao_id'];
        $evaluation['profissional_id'] = (int) $evaluation['profissional_id'];

        return $evaluation;
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

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Avaliacao publica nao encontrada.',
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
