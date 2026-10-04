<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use DateTimeImmutable;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PersonController
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
                p.id,
                p.nome,
                p.email,
                p.telefone,
                p.data_nascimento,
                p.observacao_administrativa,
                p.status,
                p.created_at,
                p.updated_at,
                (
                    SELECT COUNT(*)
                    FROM vinculos v
                    WHERE v.profissional_id = p.profissional_id
                      AND (
                        v.pessoa_a_id = p.id
                        OR v.pessoa_b_id = p.id
                      )
                ) AS total_vinculos,
                (
                    SELECT COUNT(*)
                    FROM aplicacao_participantes ap
                    WHERE ap.pessoa_id = p.id
                ) AS total_aplicacoes
             FROM pessoas p
             WHERE p.profissional_id = :profissional_id
             ORDER BY p.nome ASC, p.id ASC'
        );
        $stmt->execute(['profissional_id' => $professionalId]);

        $people = array_map(
            [$this, 'normalizePerson'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, ['pessoas' => $people]);
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

        $person = $this->find($professionalId, $id);

        if ($person === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['pessoa' => $person]);
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
            'INSERT INTO pessoas (
                profissional_id,
                nome,
                email,
                telefone,
                data_nascimento,
                observacao_administrativa,
                status
             ) VALUES (
                :profissional_id,
                :nome,
                :email,
                :telefone,
                :data_nascimento,
                :observacao_administrativa,
                :status
             )'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
            'nome' => $validated['nome'],
            'email' => $validated['email'],
            'telefone' => $validated['telefone'],
            'data_nascimento' => $validated['data_nascimento'],
            'observacao_administrativa' => $validated['observacao_administrativa'],
            'status' => $validated['status'],
        ]);

        $id = (int) $pdo->lastInsertId();

        return $this->json(
            $response,
            [
                'message' => 'Pessoa criada com sucesso.',
                'pessoa' => $this->find($professionalId, $id),
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
            'UPDATE pessoas
                SET nome = :nome,
                    email = :email,
                    telefone = :telefone,
                    data_nascimento = :data_nascimento,
                    observacao_administrativa = :observacao_administrativa,
                    status = :status
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'nome' => $validated['nome'],
            'email' => $validated['email'],
            'telefone' => $validated['telefone'],
            'data_nascimento' => $validated['data_nascimento'],
            'observacao_administrativa' => $validated['observacao_administrativa'],
            'status' => $validated['status'],
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'message' => 'Pessoa atualizada com sucesso.',
            'pessoa' => $this->find($professionalId, $id),
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

        $person = $this->find($professionalId, $id);

        if ($person === null) {
            return $this->notFound($response);
        }

        if (
            (int) $person['total_vinculos'] > 0
            || (int) $person['total_aplicacoes'] > 0
        ) {
            return $this->json($response, [
                'error' => 'person_in_use',
                'message' => 'Pessoas com vinculos ou aplicacoes nao podem ser excluidas. Marque a pessoa como inativa.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM pessoas
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'message' => 'Pessoa excluida com sucesso.',
        ]);
    }

    /**
     * @return array{
     *   nome:string,
     *   email:?string,
     *   telefone:?string,
     *   data_nascimento:?string,
     *   observacao_administrativa:?string,
     *   status:string
     * }|ResponseInterface
     */
    private function validatePayload(
        array $data,
        ResponseInterface $response
    ): array|ResponseInterface {
        $nome = trim((string) ($data['nome'] ?? ''));
        $email = $this->optional($data['email'] ?? null);
        $telefone = $this->optional($data['telefone'] ?? null);
        $dataNascimento = $this->optional($data['data_nascimento'] ?? null);
        $observacao = $this->optional(
            $data['observacao_administrativa'] ?? null
        );
        $status = strtoupper(trim((string) ($data['status'] ?? 'ATIVO')));

        if ($nome === '' || strlen($nome) > 150) {
            return $this->validation(
                $response,
                'Nome e obrigatorio e deve ter no maximo 150 caracteres.'
            );
        }

        if (
            $email !== null
            && (
                strlen($email) > 190
                || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            )
        ) {
            return $this->validation($response, 'E-mail invalido.');
        }

        if ($telefone !== null && strlen($telefone) > 30) {
            return $this->validation(
                $response,
                'Telefone deve ter no maximo 30 caracteres.'
            );
        }

        if (
            $dataNascimento !== null
            && !$this->validDate($dataNascimento)
        ) {
            return $this->validation(
                $response,
                'Data de nascimento invalida. Use o formato AAAA-MM-DD.'
            );
        }

        if ($observacao !== null && strlen($observacao) > 10000) {
            return $this->validation(
                $response,
                'Observacao administrativa deve ter no maximo 10000 caracteres.'
            );
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return $this->validation($response, 'Status da pessoa invalido.');
        }

        return [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'data_nascimento' => $dataNascimento,
            'observacao_administrativa' => $observacao,
            'status' => $status,
        ];
    }

    private function find(int $professionalId, int $id): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                p.id,
                p.nome,
                p.email,
                p.telefone,
                p.data_nascimento,
                p.observacao_administrativa,
                p.status,
                p.created_at,
                p.updated_at,
                (
                    SELECT COUNT(*)
                    FROM vinculos v
                    WHERE v.profissional_id = p.profissional_id
                      AND (
                        v.pessoa_a_id = p.id
                        OR v.pessoa_b_id = p.id
                      )
                ) AS total_vinculos,
                (
                    SELECT COUNT(*)
                    FROM aplicacao_participantes ap
                    WHERE ap.pessoa_id = p.id
                ) AS total_aplicacoes
             FROM pessoas p
             WHERE p.id = :id
               AND p.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($person === false) {
            return null;
        }

        return $this->normalizePerson($person);
    }

    private function normalizePerson(array $person): array
    {
        $person['id'] = (int) $person['id'];
        $person['total_vinculos'] = (int) ($person['total_vinculos'] ?? 0);
        $person['total_aplicacoes'] = (int) ($person['total_aplicacoes'] ?? 0);

        return $person;
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

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
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
            'message' => 'Pessoa nao encontrada.',
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
