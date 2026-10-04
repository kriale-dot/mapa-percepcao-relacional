<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InstrumentController
{
    private const ALLOWED_STATUSES = [
        'RASCUNHO',
        'ATIVO',
        'ARQUIVADO',
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
                i.id,
                i.nome,
                i.descricao,
                i.status,
                i.created_at,
                i.updated_at,
                COUNT(v.id) AS total_versoes
             FROM instrumentos i
             LEFT JOIN instrumento_versoes v
               ON v.instrumento_id = i.id
             WHERE i.profissional_id = :profissional_id
             GROUP BY
                i.id,
                i.nome,
                i.descricao,
                i.status,
                i.created_at,
                i.updated_at
             ORDER BY i.updated_at DESC, i.id DESC'
        );
        $stmt->execute(['profissional_id' => $professionalId]);

        $items = array_map(
            [$this, 'normalizeInstrument'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, ['instrumentos' => $items]);
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

        $instrument = $this->find($professionalId, $id);

        if ($instrument === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['instrumento' => $instrument]);
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

        $validated = $this->validatePayload($data, $response);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'INSERT INTO instrumentos (
                profissional_id,
                nome,
                descricao,
                status
             ) VALUES (
                :profissional_id,
                :nome,
                :descricao,
                :status
             )'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
            'nome' => $validated['nome'],
            'descricao' => $validated['descricao'],
            'status' => $validated['status'],
        ]);

        $id = (int) $pdo->lastInsertId();
        $instrument = $this->find($professionalId, $id);

        return $this->json(
            $response,
            [
                'message' => 'Instrumento criado com sucesso.',
                'instrumento' => $instrument,
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

        $validated = $this->validatePayload($data, $response);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE instrumentos
                SET nome = :nome,
                    descricao = :descricao,
                    status = :status
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'nome' => $validated['nome'],
            'descricao' => $validated['descricao'],
            'status' => $validated['status'],
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'message' => 'Instrumento atualizado com sucesso.',
            'instrumento' => $this->find($professionalId, $id),
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

        if ($id === null || $this->find($professionalId, $id) === null) {
            return $this->notFound($response);
        }

        $pdo = Database::connect();

        $count = $pdo->prepare(
            'SELECT COUNT(*)
               FROM instrumento_versoes
              WHERE instrumento_id = :id'
        );
        $count->execute(['id' => $id]);

        if ((int) $count->fetchColumn() > 0) {
            return $this->json($response, [
                'error' => 'instrument_has_versions',
                'message' => 'Instrumentos com versoes nao podem ser excluidos. Arquive o instrumento.',
            ], 409);
        }

        $stmt = $pdo->prepare(
            'DELETE FROM instrumentos
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'message' => 'Instrumento excluido com sucesso.',
        ]);
    }

    /**
     * @return array{nome:string,descricao:?string,status:string}|ResponseInterface
     */
    private function validatePayload(
        array $data,
        ResponseInterface $response
    ): array|ResponseInterface {
        $nome = trim((string) ($data['nome'] ?? ''));
        $descricao = $this->optional($data['descricao'] ?? null);
        $status = strtoupper(trim((string) ($data['status'] ?? 'RASCUNHO')));

        if ($nome === '' || strlen($nome) > 180) {
            return $this->validation(
                $response,
                'Nome e obrigatorio e deve ter no maximo 180 caracteres.'
            );
        }

        if ($descricao !== null && strlen($descricao) > 10000) {
            return $this->validation(
                $response,
                'Descricao deve ter no maximo 10000 caracteres.'
            );
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return $this->validation(
                $response,
                'Status do instrumento invalido.'
            );
        }

        return [
            'nome' => $nome,
            'descricao' => $descricao,
            'status' => $status,
        ];
    }

    private function find(int $professionalId, int $id): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                i.id,
                i.nome,
                i.descricao,
                i.status,
                i.created_at,
                i.updated_at,
                COUNT(v.id) AS total_versoes
             FROM instrumentos i
             LEFT JOIN instrumento_versoes v
               ON v.instrumento_id = i.id
             WHERE i.id = :id
               AND i.profissional_id = :profissional_id
             GROUP BY
                i.id,
                i.nome,
                i.descricao,
                i.status,
                i.created_at,
                i.updated_at
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $instrument = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($instrument === false) {
            return null;
        }

        return $this->normalizeInstrument($instrument);
    }

    private function normalizeInstrument(array $instrument): array
    {
        $instrument['id'] = (int) $instrument['id'];
        $instrument['total_versoes'] = (int) ($instrument['total_versoes'] ?? 0);

        return $instrument;
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
        if (!is_scalar($value) || preg_match('/^[1-9][0-9]*$/', (string) $value) !== 1) {
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
            'message' => 'Instrumento nao encontrado.',
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
