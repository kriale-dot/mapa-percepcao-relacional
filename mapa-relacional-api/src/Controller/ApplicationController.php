<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ApplicationController
{
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare($this->applicationSelect() . '
             WHERE a.profissional_id = :profissional_id
             ORDER BY a.created_at DESC, a.id DESC'
        );
        $stmt->execute(['profissional_id' => $professionalId]);

        $applications = array_map(
            [$this, 'normalizeApplication'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, ['aplicacoes' => $applications]);
    }

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $id = $this->positiveId($args['id'] ?? null);

        if ($id === null) {
            return $this->notFound($response);
        }

        $application = $this->find($professionalId, $id);

        if ($application === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['aplicacao' => $application]);
    }

    public function options(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $pdo = Database::connect();

        $versionsStmt = $pdo->prepare(
            'SELECT
                v.id,
                v.instrumento_id,
                i.nome AS instrumento_nome,
                v.numero_versao,
                v.status,
                v.publicado_em
             FROM instrumento_versoes v
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             WHERE i.profissional_id = :profissional_id
               AND v.status = :status
             ORDER BY i.nome ASC, v.id DESC'
        );
        $versionsStmt->execute([
            'profissional_id' => $professionalId,
            'status' => 'PUBLICADA',
        ]);

        $relationshipsStmt = $pdo->prepare(
            'SELECT
                v.id,
                v.pessoa_a_id,
                pa.nome AS pessoa_a_nome,
                v.pessoa_b_id,
                pb.nome AS pessoa_b_nome,
                v.tipo,
                v.descricao_tipo,
                v.duracao_texto,
                v.status
             FROM vinculos v
             INNER JOIN pessoas pa
               ON pa.id = v.pessoa_a_id
             INNER JOIN pessoas pb
               ON pb.id = v.pessoa_b_id
             WHERE v.profissional_id = :profissional_id
               AND v.status = :status
             ORDER BY pa.nome ASC, pb.nome ASC, v.id ASC'
        );
        $relationshipsStmt->execute([
            'profissional_id' => $professionalId,
            'status' => 'ATIVO',
        ]);

        $versions = array_map(
            static function (array $version): array {
                $version['id'] = (int) $version['id'];
                $version['instrumento_id'] = (int) $version['instrumento_id'];

                return $version;
            },
            $versionsStmt->fetchAll(PDO::FETCH_ASSOC)
        );

        $relationships = array_map(
            static function (array $relationship): array {
                $relationship['id'] = (int) $relationship['id'];
                $relationship['pessoa_a_id'] = (int) $relationship['pessoa_a_id'];
                $relationship['pessoa_b_id'] = (int) $relationship['pessoa_b_id'];

                return $relationship;
            },
            $relationshipsStmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'versoes' => $versions,
            'vinculos' => $relationships,
        ]);
    }

    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $versionId = $this->positiveId(
            $data['instrumento_versao_id'] ?? null
        );
        $relationshipId = $this->nullablePositiveId(
            $data['vinculo_id'] ?? null
        );
        $email = trim((string) ($data['email_contato'] ?? ''));

        if ($versionId === null) {
            return $this->validation(
                $response,
                'Selecione uma versao publicada do instrumento.'
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

        $version = $this->findPublishedVersion(
            $professionalId,
            $versionId
        );

        if ($version === null) {
            return $this->validation(
                $response,
                'A versao selecionada nao esta publicada ou nao pertence ao profissional.'
            );
        }

        $relationship = null;
        $personAId = null;
        $personBId = null;
        $nameA = null;
        $nameB = null;

        if ($relationshipId !== null) {
            $relationship = $this->findActiveRelationship(
                $professionalId,
                $relationshipId
            );

            if ($relationship === null) {
                return $this->validation(
                    $response,
                    'O vinculo selecionado nao esta ativo ou nao pertence ao profissional.'
                );
            }

            $typeSnapshot = (string) $relationship['tipo'];
            $durationSnapshot = $relationship['duracao_texto'];
            $personAId = (int) $relationship['pessoa_a_id'];
            $personBId = (int) $relationship['pessoa_b_id'];
            $nameA = (string) $relationship['pessoa_a_nome'];
            $nameB = (string) $relationship['pessoa_b_nome'];
        } else {
            $typeSnapshot = strtoupper(trim(
                (string) ($data['tipo_vinculo_snapshot'] ?? '')
            ));
            $durationSnapshot = $this->optional(
                $data['duracao_vinculo_texto'] ?? null
            );

            if ($typeSnapshot === '' || strlen($typeSnapshot) > 50) {
                return $this->validation(
                    $response,
                    'Informe o tipo do vinculo quando nao houver um vinculo cadastrado.'
                );
            }

            if (
                $durationSnapshot !== null
                && strlen($durationSnapshot) > 100
            ) {
                return $this->validation(
                    $response,
                    'Tempo de uniao deve ter no maximo 100 caracteres.'
                );
            }
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
                    :vinculo_id,
                    :instrumento_versao_id,
                    :email_contato,
                    :tipo_vinculo_snapshot,
                    :duracao_vinculo_texto,
                    :status
                 )'
            );
            $insertApplication->execute([
                'profissional_id' => $professionalId,
                'vinculo_id' => $relationshipId,
                'instrumento_versao_id' => $versionId,
                'email_contato' => $email,
                'tipo_vinculo_snapshot' => $typeSnapshot,
                'duracao_vinculo_texto' => $durationSnapshot,
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
                    :pessoa_id,
                    :lado,
                    :nome_snapshot,
                    NULL,
                    NULL,
                    :status
                 )'
            );

            $insertParticipant->execute([
                'aplicacao_id' => $applicationId,
                'pessoa_id' => $personAId,
                'lado' => 'A',
                'nome_snapshot' => $nameA,
                'status' => 'PENDENTE',
            ]);

            $insertParticipant->execute([
                'aplicacao_id' => $applicationId,
                'pessoa_id' => $personBId,
                'lado' => 'B',
                'nome_snapshot' => $nameB,
                'status' => 'PENDENTE',
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
                'message' => 'Aplicacao criada com sucesso.',
                'aplicacao' => $this->find(
                    $professionalId,
                    $applicationId
                ),
            ],
            201
        );
    }

    private function findPublishedVersion(
        int $professionalId,
        int $versionId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.instrumento_id,
                v.numero_versao,
                v.status,
                i.nome AS instrumento_nome
             FROM instrumento_versoes v
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             WHERE v.id = :versao_id
               AND i.profissional_id = :profissional_id
               AND v.status = :status
             LIMIT 1'
        );
        $stmt->execute([
            'versao_id' => $versionId,
            'profissional_id' => $professionalId,
            'status' => 'PUBLICADA',
        ]);

        $version = $stmt->fetch(PDO::FETCH_ASSOC);

        return $version === false ? null : $version;
    }

    private function findActiveRelationship(
        int $professionalId,
        int $relationshipId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.pessoa_a_id,
                pa.nome AS pessoa_a_nome,
                v.pessoa_b_id,
                pb.nome AS pessoa_b_nome,
                v.tipo,
                v.descricao_tipo,
                v.duracao_texto,
                v.status
             FROM vinculos v
             INNER JOIN pessoas pa
               ON pa.id = v.pessoa_a_id
             INNER JOIN pessoas pb
               ON pb.id = v.pessoa_b_id
             WHERE v.id = :id
               AND v.profissional_id = :profissional_id
               AND v.status = :status
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $relationshipId,
            'profissional_id' => $professionalId,
            'status' => 'ATIVO',
        ]);

        $relationship = $stmt->fetch(PDO::FETCH_ASSOC);

        return $relationship === false ? null : $relationship;
    }

    private function find(int $professionalId, int $id): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            $this->applicationSelect() . '
             WHERE a.id = :id
               AND a.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($application === false) {
            return null;
        }

        return $this->normalizeApplication($application);
    }

    private function applicationSelect(): string
    {
        return 'SELECT
                a.id,
                a.vinculo_id,
                a.instrumento_versao_id,
                a.email_contato,
                a.tipo_vinculo_snapshot,
                a.duracao_vinculo_texto,
                a.status,
                a.enviado_em,
                a.iniciada_em,
                a.concluida_em,
                a.created_at,
                a.updated_at,
                i.id AS instrumento_id,
                i.nome AS instrumento_nome,
                v.numero_versao,
                pa.id AS participante_a_id,
                pa.pessoa_id AS pessoa_a_id,
                pa.nome_snapshot AS pessoa_a_nome_snapshot,
                pa.idade_snapshot AS pessoa_a_idade_snapshot,
                pa.genero_snapshot AS pessoa_a_genero_snapshot,
                pa.status AS participante_a_status,
                pa.iniciou_em AS participante_a_iniciou_em,
                pa.concluiu_em AS participante_a_concluiu_em,
                pb.id AS participante_b_id,
                pb.pessoa_id AS pessoa_b_id,
                pb.nome_snapshot AS pessoa_b_nome_snapshot,
                pb.idade_snapshot AS pessoa_b_idade_snapshot,
                pb.genero_snapshot AS pessoa_b_genero_snapshot,
                pb.status AS participante_b_status,
                pb.iniciou_em AS participante_b_iniciou_em,
                pb.concluiu_em AS participante_b_concluiu_em
             FROM aplicacoes a
             INNER JOIN instrumento_versoes v
               ON v.id = a.instrumento_versao_id
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             LEFT JOIN aplicacao_participantes pa
               ON pa.aplicacao_id = a.id
              AND pa.lado = \'A\'
             LEFT JOIN aplicacao_participantes pb
               ON pb.aplicacao_id = a.id
              AND pb.lado = \'B\'';
    }

    private function normalizeApplication(array $application): array
    {
        $application['id'] = (int) $application['id'];
        $application['vinculo_id'] = $application['vinculo_id'] === null
            ? null
            : (int) $application['vinculo_id'];
        $application['instrumento_versao_id'] =
            (int) $application['instrumento_versao_id'];
        $application['instrumento_id'] = (int) $application['instrumento_id'];

        $participantA = [
            'id' => $application['participante_a_id'] === null
                ? null
                : (int) $application['participante_a_id'],
            'pessoa_id' => $application['pessoa_a_id'] === null
                ? null
                : (int) $application['pessoa_a_id'],
            'lado' => 'A',
            'nome_snapshot' => $application['pessoa_a_nome_snapshot'],
            'idade_snapshot' => $application['pessoa_a_idade_snapshot'] === null
                ? null
                : (int) $application['pessoa_a_idade_snapshot'],
            'genero_snapshot' => $application['pessoa_a_genero_snapshot'],
            'status' => $application['participante_a_status'],
            'iniciou_em' => $application['participante_a_iniciou_em'],
            'concluiu_em' => $application['participante_a_concluiu_em'],
        ];

        $participantB = [
            'id' => $application['participante_b_id'] === null
                ? null
                : (int) $application['participante_b_id'],
            'pessoa_id' => $application['pessoa_b_id'] === null
                ? null
                : (int) $application['pessoa_b_id'],
            'lado' => 'B',
            'nome_snapshot' => $application['pessoa_b_nome_snapshot'],
            'idade_snapshot' => $application['pessoa_b_idade_snapshot'] === null
                ? null
                : (int) $application['pessoa_b_idade_snapshot'],
            'genero_snapshot' => $application['pessoa_b_genero_snapshot'],
            'status' => $application['participante_b_status'],
            'iniciou_em' => $application['participante_b_iniciou_em'],
            'concluiu_em' => $application['participante_b_concluiu_em'],
        ];

        foreach ([
            'participante_a_id',
            'pessoa_a_id',
            'pessoa_a_nome_snapshot',
            'pessoa_a_idade_snapshot',
            'pessoa_a_genero_snapshot',
            'participante_a_status',
            'participante_a_iniciou_em',
            'participante_a_concluiu_em',
            'participante_b_id',
            'pessoa_b_id',
            'pessoa_b_nome_snapshot',
            'pessoa_b_idade_snapshot',
            'pessoa_b_genero_snapshot',
            'participante_b_status',
            'participante_b_iniciou_em',
            'participante_b_concluiu_em',
        ] as $key) {
            unset($application[$key]);
        }

        $application['participante_a'] = $participantA;
        $application['participante_b'] = $participantB;

        return $application;
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
