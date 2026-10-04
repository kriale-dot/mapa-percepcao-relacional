<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AlternativeController
{
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $response, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                a.id,
                a.item_id,
                a.valor,
                a.rotulo,
                a.ordem,
                a.ativo,
                a.created_at,
                a.updated_at,
                (SELECT COUNT(*)
                   FROM respostas r
                  WHERE r.alternativa_id = a.id) AS total_respostas
             FROM alternativas a
             WHERE a.item_id = :item_id
             ORDER BY a.ordem ASC, a.id ASC'
        );
        $stmt->execute(['item_id' => $context['item_id']]);

        $alternatives = array_map(
            [$this, 'normalizeAlternative'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'versao' => $context['version'],
            'secao' => $context['section'],
            'item' => $context['item'],
            'alternativas' => $alternatives,
        ]);
    }

    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $response, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        if ($context['version']['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $validated = $this->validatePayload($data, $response);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        $ordem = $validated['ordem'];

        if ($ordem === null) {
            $orderStmt = $pdo->prepare(
                'SELECT COALESCE(MAX(ordem), 0) + 1
                   FROM alternativas
                  WHERE item_id = :item_id'
            );
            $orderStmt->execute(['item_id' => $context['item_id']]);
            $ordem = (int) $orderStmt->fetchColumn();
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO alternativas (
                    item_id,
                    valor,
                    rotulo,
                    ordem,
                    ativo
                 ) VALUES (
                    :item_id,
                    :valor,
                    :rotulo,
                    :ordem,
                    :ativo
                 )'
            );
            $stmt->execute([
                'item_id' => $context['item_id'],
                'valor' => $validated['valor'],
                'rotulo' => $validated['rotulo'],
                'ordem' => $ordem,
                'ativo' => $validated['ativo'] ? 1 : 0,
            ]);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                return $this->json($response, [
                    'error' => 'alternative_value_in_use',
                    'message' => 'Este valor de alternativa ja existe neste item.',
                ], 409);
            }

            throw $error;
        }

        $alternativeId = (int) $pdo->lastInsertId();

        return $this->json(
            $response,
            [
                'message' => 'Alternativa criada com sucesso.',
                'alternativa' => $this->findAlternative(
                    $context['item_id'],
                    $alternativeId
                ),
            ],
            201
        );
    }

    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $response, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        if ($context['version']['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        $alternativeId = $this->positiveId($args['alternativeId'] ?? null);

        if (
            $alternativeId === null
            || $this->findAlternative(
                $context['item_id'],
                $alternativeId
            ) === null
        ) {
            return $this->alternativeNotFound($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $validated = $this->validatePayload($data, $response, true);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        try {
            $stmt = $pdo->prepare(
                'UPDATE alternativas
                    SET valor = :valor,
                        rotulo = :rotulo,
                        ordem = :ordem,
                        ativo = :ativo
                  WHERE id = :id
                    AND item_id = :item_id'
            );
            $stmt->execute([
                'valor' => $validated['valor'],
                'rotulo' => $validated['rotulo'],
                'ordem' => $validated['ordem'],
                'ativo' => $validated['ativo'] ? 1 : 0,
                'id' => $alternativeId,
                'item_id' => $context['item_id'],
            ]);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                return $this->json($response, [
                    'error' => 'alternative_value_in_use',
                    'message' => 'Este valor de alternativa ja existe neste item.',
                ], 409);
            }

            throw $error;
        }

        return $this->json($response, [
            'message' => 'Alternativa atualizada com sucesso.',
            'alternativa' => $this->findAlternative(
                $context['item_id'],
                $alternativeId
            ),
        ]);
    }

    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $response, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        if ($context['version']['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        $alternativeId = $this->positiveId($args['alternativeId'] ?? null);

        if ($alternativeId === null) {
            return $this->alternativeNotFound($response);
        }

        $alternative = $this->findAlternative(
            $context['item_id'],
            $alternativeId
        );

        if ($alternative === null) {
            return $this->alternativeNotFound($response);
        }

        if ((int) $alternative['total_respostas'] > 0) {
            return $this->json($response, [
                'error' => 'alternative_in_use',
                'message' => 'Alternativas com respostas vinculadas nao podem ser excluidas.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM alternativas
              WHERE id = :id
                AND item_id = :item_id'
        );
        $stmt->execute([
            'id' => $alternativeId,
            'item_id' => $context['item_id'],
        ]);

        return $this->json($response, [
            'message' => 'Alternativa excluida com sucesso.',
        ]);
    }

    /**
     * @return array<string,mixed>|ResponseInterface
     */
    private function resolveContext(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): array|ResponseInterface {
        $professional = $request->getAttribute('auth.professional');
        $professionalId = is_array($professional)
            ? (int) ($professional['id'] ?? 0)
            : 0;

        if ($professionalId <= 0) {
            return $this->unauthorized($response);
        }

        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);
        $versionId = $this->positiveId($args['versionId'] ?? null);
        $sectionId = $this->positiveId($args['sectionId'] ?? null);
        $itemId = $this->positiveId($args['itemId'] ?? null);

        if (
            $instrumentId === null
            || $versionId === null
            || $sectionId === null
            || $itemId === null
        ) {
            return $this->itemNotFound($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id AS versao_id,
                v.numero_versao,
                v.status AS versao_status,
                s.id AS secao_id,
                s.titulo AS secao_titulo,
                i.id AS item_id,
                i.codigo AS item_codigo,
                i.texto AS item_texto,
                i.tipo_resposta,
                i.ordem AS item_ordem,
                i.permite_nao_se_aplica,
                i.ativo AS item_ativo
             FROM instrumento_versoes v
             INNER JOIN instrumentos ins
               ON ins.id = v.instrumento_id
             INNER JOIN secoes s
               ON s.instrumento_versao_id = v.id
             INNER JOIN itens i
               ON i.secao_id = s.id
             WHERE ins.id = :instrumento_id
               AND ins.profissional_id = :profissional_id
               AND v.id = :versao_id
               AND s.id = :secao_id
               AND i.id = :item_id
             LIMIT 1'
        );
        $stmt->execute([
            'instrumento_id' => $instrumentId,
            'profissional_id' => $professionalId,
            'versao_id' => $versionId,
            'secao_id' => $sectionId,
            'item_id' => $itemId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return $this->itemNotFound($response);
        }

        return [
            'instrument_id' => $instrumentId,
            'version_id' => $versionId,
            'section_id' => $sectionId,
            'item_id' => $itemId,
            'version' => [
                'id' => (int) $row['versao_id'],
                'numero_versao' => (string) $row['numero_versao'],
                'status' => (string) $row['versao_status'],
            ],
            'section' => [
                'id' => (int) $row['secao_id'],
                'titulo' => (string) $row['secao_titulo'],
            ],
            'item' => [
                'id' => (int) $row['item_id'],
                'codigo' => (string) $row['item_codigo'],
                'texto' => (string) $row['item_texto'],
                'tipo_resposta' => (string) $row['tipo_resposta'],
                'ordem' => (int) $row['item_ordem'],
                'permite_nao_se_aplica' => (bool) $row['permite_nao_se_aplica'],
                'ativo' => (bool) $row['item_ativo'],
            ],
        ];
    }

    /**
     * @return array{valor:string,rotulo:string,ordem:?int,ativo:bool}|ResponseInterface
     */
    private function validatePayload(
        array $data,
        ResponseInterface $response,
        bool $requireOrder = false
    ): array|ResponseInterface {
        $valor = trim((string) ($data['valor'] ?? ''));
        $rotulo = trim((string) ($data['rotulo'] ?? ''));

        $ativo = filter_var(
            $data['ativo'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        $ordemRaw = $data['ordem'] ?? null;
        $ordem = null;

        if ($ordemRaw !== null && $ordemRaw !== '') {
            if (
                filter_var(
                    $ordemRaw,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 0]]
                ) === false
            ) {
                return $this->validation(
                    $response,
                    'Ordem deve ser um numero inteiro igual ou maior que zero.'
                );
            }

            $ordem = (int) $ordemRaw;
        }

        if ($requireOrder && $ordem === null) {
            return $this->validation($response, 'Ordem e obrigatoria.');
        }

        if ($valor === '' || strlen($valor) > 100) {
            return $this->validation(
                $response,
                'Valor e obrigatorio e deve ter no maximo 100 caracteres.'
            );
        }

        if ($rotulo === '' || strlen($rotulo) > 255) {
            return $this->validation(
                $response,
                'Rotulo e obrigatorio e deve ter no maximo 255 caracteres.'
            );
        }

        if ($ativo === null) {
            return $this->validation($response, 'Status ativo invalido.');
        }

        return [
            'valor' => $valor,
            'rotulo' => $rotulo,
            'ordem' => $ordem,
            'ativo' => $ativo,
        ];
    }

    private function findAlternative(
        int $itemId,
        int $alternativeId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                a.id,
                a.item_id,
                a.valor,
                a.rotulo,
                a.ordem,
                a.ativo,
                a.created_at,
                a.updated_at,
                (SELECT COUNT(*)
                   FROM respostas r
                  WHERE r.alternativa_id = a.id) AS total_respostas
             FROM alternativas a
             WHERE a.id = :id
               AND a.item_id = :item_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $alternativeId,
            'item_id' => $itemId,
        ]);

        $alternative = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($alternative === false) {
            return null;
        }

        return $this->normalizeAlternative($alternative);
    }

    private function normalizeAlternative(array $alternative): array
    {
        $alternative['id'] = (int) $alternative['id'];
        $alternative['item_id'] = (int) $alternative['item_id'];
        $alternative['ordem'] = (int) $alternative['ordem'];
        $alternative['ativo'] = (bool) $alternative['ativo'];
        $alternative['total_respostas'] = (int) ($alternative['total_respostas'] ?? 0);

        return $alternative;
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

    private function unauthorized(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'unauthorized',
            'message' => 'Autenticacao profissional obrigatoria.',
        ], 401);
    }

    private function itemNotFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Item nao encontrado.',
        ], 404);
    }

    private function alternativeNotFound(
        ResponseInterface $response
    ): ResponseInterface {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Alternativa nao encontrada.',
        ], 404);
    }

    private function immutable(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'version_immutable',
            'message' => 'Versoes publicadas ou arquivadas sao imutaveis.',
        ], 409);
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
