<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ItemController
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
                i.id,
                i.secao_id,
                i.codigo,
                i.texto,
                i.tipo_resposta,
                i.ordem,
                i.permite_nao_se_aplica,
                i.ativo,
                i.created_at,
                i.updated_at,
                (SELECT COUNT(*)
                   FROM alternativas a
                  WHERE a.item_id = i.id) AS total_alternativas,
                (SELECT COUNT(*)
                   FROM respostas r
                  WHERE r.item_id = i.id) AS total_respostas
             FROM itens i
             WHERE i.secao_id = :secao_id
             ORDER BY i.ordem ASC, i.id ASC'
        );
        $stmt->execute(['secao_id' => $context['section_id']]);

        $items = array_map(
            [$this, 'normalizeItem'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'versao' => $context['version'],
            'secao' => $context['section'],
            'itens' => $items,
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
                   FROM itens
                  WHERE secao_id = :secao_id'
            );
            $orderStmt->execute(['secao_id' => $context['section_id']]);
            $ordem = (int) $orderStmt->fetchColumn();
        }

        $codigo = $this->generateItemCode(
            $pdo,
            $context['section_id']
        );

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO itens (
                    secao_id,
                    codigo,
                    texto,
                    tipo_resposta,
                    ordem,
                    permite_nao_se_aplica,
                    ativo
                 ) VALUES (
                    :secao_id,
                    :codigo,
                    :texto,
                    :tipo_resposta,
                    :ordem,
                    :permite_nao_se_aplica,
                    :ativo
                 )'
            );
            $stmt->execute([
                'secao_id' => $context['section_id'],
                'codigo' => $codigo,
                'texto' => $validated['texto'],
                'tipo_resposta' => $validated['tipo_resposta'],
                'ordem' => $ordem,
                'permite_nao_se_aplica' => $validated['permite_nao_se_aplica'] ? 1 : 0,
                'ativo' => $validated['ativo'] ? 1 : 0,
            ]);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                return $this->json($response, [
                    'error' => 'item_code_in_use',
                    'message' => 'Este codigo de item ja existe nesta secao.',
                ], 409);
            }

            throw $error;
        }

        $itemId = (int) $pdo->lastInsertId();

        return $this->json(
            $response,
            [
                'message' => 'Item criado com sucesso.',
                'item' => $this->findItem(
                    $context['section_id'],
                    $itemId
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

        $itemId = $this->positiveId($args['itemId'] ?? null);

        if ($itemId === null) {
            return $this->itemNotFound($response);
        }

        $existingItem = $this->findItem(
            $context['section_id'],
            $itemId
        );

        if ($existingItem === null) {
            return $this->itemNotFound($response);
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
                'UPDATE itens
                    SET codigo = :codigo,
                        texto = :texto,
                        tipo_resposta = :tipo_resposta,
                        ordem = :ordem,
                        permite_nao_se_aplica = :permite_nao_se_aplica,
                        ativo = :ativo
                  WHERE id = :id
                    AND secao_id = :secao_id'
            );
            $stmt->execute([
                'codigo' => (string) $existingItem['codigo'],
                'texto' => $validated['texto'],
                'tipo_resposta' => $validated['tipo_resposta'],
                'ordem' => $validated['ordem'],
                'permite_nao_se_aplica' => $validated['permite_nao_se_aplica'] ? 1 : 0,
                'ativo' => $validated['ativo'] ? 1 : 0,
                'id' => $itemId,
                'secao_id' => $context['section_id'],
            ]);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                return $this->json($response, [
                    'error' => 'item_code_in_use',
                    'message' => 'Este codigo de item ja existe nesta secao.',
                ], 409);
            }

            throw $error;
        }

        return $this->json($response, [
            'message' => 'Item atualizado com sucesso.',
            'item' => $this->findItem(
                $context['section_id'],
                $itemId
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

        $itemId = $this->positiveId($args['itemId'] ?? null);

        if ($itemId === null) {
            return $this->itemNotFound($response);
        }

        $item = $this->findItem($context['section_id'], $itemId);

        if ($item === null) {
            return $this->itemNotFound($response);
        }

        if (
            (int) $item['total_alternativas'] > 0
            || (int) $item['total_respostas'] > 0
        ) {
            return $this->json($response, [
                'error' => 'item_in_use',
                'message' => 'Itens com alternativas ou respostas vinculadas nao podem ser excluidos.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM itens
              WHERE id = :id
                AND secao_id = :secao_id'
        );
        $stmt->execute([
            'id' => $itemId,
            'secao_id' => $context['section_id'],
        ]);

        return $this->json($response, [
            'message' => 'Item excluido com sucesso.',
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

        if (
            $instrumentId === null
            || $versionId === null
            || $sectionId === null
        ) {
            return $this->sectionNotFound($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id AS versao_id,
                v.numero_versao,
                v.status AS versao_status,
                s.id AS secao_id,
                s.titulo AS secao_titulo,
                s.descricao AS secao_descricao,
                s.ordem AS secao_ordem,
                s.ativo AS secao_ativo
             FROM instrumento_versoes v
             INNER JOIN instrumentos ins
               ON ins.id = v.instrumento_id
             INNER JOIN secoes s
               ON s.instrumento_versao_id = v.id
             WHERE ins.id = :instrumento_id
               AND ins.profissional_id = :profissional_id
               AND v.id = :versao_id
               AND s.id = :secao_id
             LIMIT 1'
        );
        $stmt->execute([
            'instrumento_id' => $instrumentId,
            'profissional_id' => $professionalId,
            'versao_id' => $versionId,
            'secao_id' => $sectionId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return $this->sectionNotFound($response);
        }

        return [
            'instrument_id' => $instrumentId,
            'version_id' => $versionId,
            'section_id' => $sectionId,
            'version' => [
                'id' => (int) $row['versao_id'],
                'numero_versao' => (string) $row['numero_versao'],
                'status' => (string) $row['versao_status'],
            ],
            'section' => [
                'id' => (int) $row['secao_id'],
                'titulo' => (string) $row['secao_titulo'],
                'descricao' => $row['secao_descricao'],
                'ordem' => (int) $row['secao_ordem'],
                'ativo' => (bool) $row['secao_ativo'],
            ],
        ];
    }

    /**
     * @return array{texto:string,tipo_resposta:string,ordem:?int,permite_nao_se_aplica:bool,ativo:bool}|ResponseInterface
     */
    private function validatePayload(
        array $data,
        ResponseInterface $response,
        bool $requireOrder = false
    ): array|ResponseInterface {
        $texto = trim((string) ($data['texto'] ?? ''));
        $tipoResposta = trim((string) ($data['tipo_resposta'] ?? ''));

        $permiteNaoSeAplica = filter_var(
            $data['permite_nao_se_aplica'] ?? false,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );
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

        if ($texto === '' || strlen($texto) > 10000) {
            return $this->validation(
                $response,
                'Texto e obrigatorio e deve ter no maximo 10000 caracteres.'
            );
        }

        $allowedResponseTypes = [
            'ESCOLHA_UNICA',
            'NUMERICO',
            'TEXTO',
        ];

        if (!in_array($tipoResposta, $allowedResponseTypes, true)) {
            return $this->validation(
                $response,
                'Tipo de resposta invalido. Selecione Escolha unica, Numerico ou Texto livre.'
            );
        }

        if ($permiteNaoSeAplica === null) {
            return $this->validation(
                $response,
                'Configuracao de Nao se aplica invalida.'
            );
        }

        if ($ativo === null) {
            return $this->validation($response, 'Status ativo invalido.');
        }

        return [
            'texto' => $texto,
            'tipo_resposta' => $tipoResposta,
            'ordem' => $ordem,
            'permite_nao_se_aplica' => $permiteNaoSeAplica,
            'ativo' => $ativo,
        ];
    }

    private function generateItemCode(
        PDO $pdo,
        int $sectionId
    ): string {
        $stmt = $pdo->prepare(
            'SELECT codigo
             FROM itens
             WHERE secao_id = :secao_id'
        );
        $stmt->execute([
            'secao_id' => $sectionId,
        ]);

        $used = [];
        $max = 0;

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $code) {
            $code = (string) $code;
            $used[$code] = true;

            if (preg_match('/^ITEM_([0-9]+)$/', $code, $match) === 1) {
                $max = max($max, (int) $match[1]);
            }
        }

        $next = $max + 1;

        do {
            $code = 'ITEM_' . str_pad(
                (string) $next,
                3,
                '0',
                STR_PAD_LEFT
            );
            $next++;
        } while (isset($used[$code]));

        return $code;
    }

    private function findItem(int $sectionId, int $itemId): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                i.id,
                i.secao_id,
                i.codigo,
                i.texto,
                i.tipo_resposta,
                i.ordem,
                i.permite_nao_se_aplica,
                i.ativo,
                i.created_at,
                i.updated_at,
                (SELECT COUNT(*)
                   FROM alternativas a
                  WHERE a.item_id = i.id) AS total_alternativas,
                (SELECT COUNT(*)
                   FROM respostas r
                  WHERE r.item_id = i.id) AS total_respostas
             FROM itens i
             WHERE i.id = :id
               AND i.secao_id = :secao_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $itemId,
            'secao_id' => $sectionId,
        ]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item === false) {
            return null;
        }

        return $this->normalizeItem($item);
    }

    private function normalizeItem(array $item): array
    {
        $item['id'] = (int) $item['id'];
        $item['secao_id'] = (int) $item['secao_id'];
        $item['ordem'] = (int) $item['ordem'];
        $item['permite_nao_se_aplica'] = (bool) $item['permite_nao_se_aplica'];
        $item['ativo'] = (bool) $item['ativo'];
        $item['total_alternativas'] = (int) ($item['total_alternativas'] ?? 0);
        $item['total_respostas'] = (int) ($item['total_respostas'] ?? 0);

        return $item;
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

    private function sectionNotFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Secao nao encontrada.',
        ], 404);
    }

    private function itemNotFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Item nao encontrado.',
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
