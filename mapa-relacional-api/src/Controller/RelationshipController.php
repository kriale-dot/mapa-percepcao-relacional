<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RelationshipController
{
    private const ALLOWED_STATUSES = [
        'ATIVO',
        'INATIVO',
    ];

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.pessoa_a_id,
                pa.nome AS pessoa_a_nome,
                pa.email AS pessoa_a_email,
                v.pessoa_b_id,
                pb.nome AS pessoa_b_nome,
                pb.email AS pessoa_b_email,
                v.tipo,
                v.descricao_tipo,
                v.duracao_texto,
                v.status,
                v.created_at,
                v.updated_at,
                (
                    SELECT COUNT(*)
                    FROM aplicacoes a
                    WHERE a.vinculo_id = v.id
                ) AS total_aplicacoes
             FROM vinculos v
             INNER JOIN pessoas pa
               ON pa.id = v.pessoa_a_id
             INNER JOIN pessoas pb
               ON pb.id = v.pessoa_b_id
             WHERE v.profissional_id = :profissional_id
             ORDER BY v.updated_at DESC, v.id DESC'
        );
        $stmt->execute(['profissional_id' => $professionalId]);

        $relationships = array_map(
            [$this, 'normalizeRelationship'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, ['vinculos' => $relationships]);
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

        $relationship = $this->find($professionalId, $id);

        if ($relationship === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['vinculo' => $relationship]);
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

        $personAId = $this->positiveId($data['pessoa_a_id'] ?? null);
        $personBId = $this->positiveId($data['pessoa_b_id'] ?? null);

        if ($personAId === null || $personBId === null) {
            return $this->validation(
                $response,
                'As duas pessoas do vinculo sao obrigatorias.'
            );
        }

        if ($personAId === $personBId) {
            return $this->validation(
                $response,
                'A mesma pessoa nao pode ocupar os lados A e B do vinculo.'
            );
        }

        if (
            !$this->ownsPerson($professionalId, $personAId)
            || !$this->ownsPerson($professionalId, $personBId)
        ) {
            return $this->validation(
                $response,
                'As duas pessoas devem pertencer ao profissional autenticado.'
            );
        }

        $validated = $this->validateDetails($data, $response);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'INSERT INTO vinculos (
                profissional_id,
                pessoa_a_id,
                pessoa_b_id,
                tipo,
                descricao_tipo,
                duracao_texto,
                status
             ) VALUES (
                :profissional_id,
                :pessoa_a_id,
                :pessoa_b_id,
                :tipo,
                :descricao_tipo,
                :duracao_texto,
                :status
             )'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
            'pessoa_a_id' => $personAId,
            'pessoa_b_id' => $personBId,
            'tipo' => $validated['tipo'],
            'descricao_tipo' => $validated['descricao_tipo'],
            'duracao_texto' => $validated['duracao_texto'],
            'status' => $validated['status'],
        ]);

        $id = (int) $pdo->lastInsertId();

        return $this->json(
            $response,
            [
                'message' => 'Vinculo criado com sucesso.',
                'vinculo' => $this->find($professionalId, $id),
            ],
            201
        );
    }

    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $id = $this->positiveId($args['id'] ?? null);

        if ($id === null || $this->find($professionalId, $id) === null) {
            return $this->notFound($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $validated = $this->validateDetails($data, $response);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE vinculos
                SET tipo = :tipo,
                    descricao_tipo = :descricao_tipo,
                    duracao_texto = :duracao_texto,
                    status = :status
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'tipo' => $validated['tipo'],
            'descricao_tipo' => $validated['descricao_tipo'],
            'duracao_texto' => $validated['duracao_texto'],
            'status' => $validated['status'],
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'message' => 'Vinculo atualizado com sucesso.',
            'vinculo' => $this->find($professionalId, $id),
        ]);
    }

    public function delete(
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

        $relationship = $this->find($professionalId, $id);

        if ($relationship === null) {
            return $this->notFound($response);
        }

        if ((int) $relationship['total_aplicacoes'] > 0) {
            return $this->json($response, [
                'error' => 'relationship_in_use',
                'message' => 'Vinculos com aplicacoes nao podem ser excluidos. Marque o vinculo como inativo.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM vinculos
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'message' => 'Vinculo excluido com sucesso.',
        ]);
    }

    /**
     * @return array{
     *   tipo:string,
     *   descricao_tipo:?string,
     *   duracao_texto:?string,
     *   status:string
     * }|ResponseInterface
     */
    private function validateDetails(
        array $data,
        ResponseInterface $response
    ): array|ResponseInterface {
        $tipo = strtoupper(trim((string) ($data['tipo'] ?? '')));
        $descricaoTipo = $this->optional($data['descricao_tipo'] ?? null);
        $duracaoTexto = $this->optional($data['duracao_texto'] ?? null);
        $status = strtoupper(trim((string) ($data['status'] ?? 'ATIVO')));

        if ($tipo === '' || strlen($tipo) > 50) {
            return $this->validation(
                $response,
                'Tipo do vinculo e obrigatorio e deve ter no maximo 50 caracteres.'
            );
        }

        if ($descricaoTipo !== null && strlen($descricaoTipo) > 150) {
            return $this->validation(
                $response,
                'Descricao do tipo deve ter no maximo 150 caracteres.'
            );
        }

        if ($tipo === 'OUTRO' && $descricaoTipo === null) {
            return $this->validation(
                $response,
                'Informe a descricao quando o tipo do vinculo for OUTRO.'
            );
        }

        if ($duracaoTexto !== null && strlen($duracaoTexto) > 100) {
            return $this->validation(
                $response,
                'Duracao do vinculo deve ter no maximo 100 caracteres.'
            );
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return $this->validation(
                $response,
                'Status do vinculo invalido.'
            );
        }

        return [
            'tipo' => $tipo,
            'descricao_tipo' => $descricaoTipo,
            'duracao_texto' => $duracaoTexto,
            'status' => $status,
        ];
    }

    private function find(int $professionalId, int $id): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.pessoa_a_id,
                pa.nome AS pessoa_a_nome,
                pa.email AS pessoa_a_email,
                v.pessoa_b_id,
                pb.nome AS pessoa_b_nome,
                pb.email AS pessoa_b_email,
                v.tipo,
                v.descricao_tipo,
                v.duracao_texto,
                v.status,
                v.created_at,
                v.updated_at,
                (
                    SELECT COUNT(*)
                    FROM aplicacoes a
                    WHERE a.vinculo_id = v.id
                ) AS total_aplicacoes
             FROM vinculos v
             INNER JOIN pessoas pa
               ON pa.id = v.pessoa_a_id
             INNER JOIN pessoas pb
               ON pb.id = v.pessoa_b_id
             WHERE v.id = :id
               AND v.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $relationship = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($relationship === false) {
            return null;
        }

        return $this->normalizeRelationship($relationship);
    }

    private function ownsPerson(int $professionalId, int $personId): bool
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT 1
               FROM pessoas
              WHERE id = :id
                AND profissional_id = :profissional_id
              LIMIT 1'
        );
        $stmt->execute([
            'id' => $personId,
            'profissional_id' => $professionalId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function normalizeRelationship(array $relationship): array
    {
        $relationship['id'] = (int) $relationship['id'];
        $relationship['pessoa_a_id'] = (int) $relationship['pessoa_a_id'];
        $relationship['pessoa_b_id'] = (int) $relationship['pessoa_b_id'];
        $relationship['total_aplicacoes'] = (int) (
            $relationship['total_aplicacoes'] ?? 0
        );

        return $relationship;
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
            'message' => 'Vinculo nao encontrado.',
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
