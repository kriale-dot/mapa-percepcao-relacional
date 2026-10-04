<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SectionController
{
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                s.id,
                s.instrumento_versao_id,
                s.titulo,
                s.descricao,
                s.ordem,
                s.ativo,
                s.created_at,
                s.updated_at,
                (SELECT COUNT(*)
                   FROM itens i
                  WHERE i.secao_id = s.id) AS total_itens
             FROM secoes s
             WHERE s.instrumento_versao_id = :versao_id
             ORDER BY s.ordem ASC, s.id ASC'
        );
        $stmt->execute(['versao_id' => $context['version_id']]);

        $sections = array_map(
            [$this, 'normalizeSection'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'versao' => $context['version'],
            'secoes' => $sections,
        ]);
    }

    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $args);

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
                   FROM secoes
                  WHERE instrumento_versao_id = :versao_id'
            );
            $orderStmt->execute(['versao_id' => $context['version_id']]);
            $ordem = (int) $orderStmt->fetchColumn();
        }

        $stmt = $pdo->prepare(
            'INSERT INTO secoes (
                instrumento_versao_id,
                titulo,
                descricao,
                ordem,
                ativo
             ) VALUES (
                :versao_id,
                :titulo,
                :descricao,
                :ordem,
                :ativo
             )'
        );
        $stmt->execute([
            'versao_id' => $context['version_id'],
            'titulo' => $validated['titulo'],
            'descricao' => $validated['descricao'],
            'ordem' => $ordem,
            'ativo' => $validated['ativo'] ? 1 : 0,
        ]);

        $sectionId = (int) $pdo->lastInsertId();

        return $this->json(
            $response,
            [
                'message' => 'Secao criada com sucesso.',
                'secao' => $this->findSection(
                    $context['version_id'],
                    $sectionId
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
        $context = $this->resolveContext($request, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        if ($context['version']['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        $sectionId = $this->positiveId($args['sectionId'] ?? null);

        if (
            $sectionId === null
            || $this->findSection($context['version_id'], $sectionId) === null
        ) {
            return $this->sectionNotFound($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $validated = $this->validatePayload($data, $response, true);

        if ($validated instanceof ResponseInterface) {
            return $validated;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE secoes
                SET titulo = :titulo,
                    descricao = :descricao,
                    ordem = :ordem,
                    ativo = :ativo
              WHERE id = :id
                AND instrumento_versao_id = :versao_id'
        );
        $stmt->execute([
            'titulo' => $validated['titulo'],
            'descricao' => $validated['descricao'],
            'ordem' => $validated['ordem'],
            'ativo' => $validated['ativo'] ? 1 : 0,
            'id' => $sectionId,
            'versao_id' => $context['version_id'],
        ]);

        return $this->json($response, [
            'message' => 'Secao atualizada com sucesso.',
            'secao' => $this->findSection(
                $context['version_id'],
                $sectionId
            ),
        ]);
    }

    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $context = $this->resolveContext($request, $args);

        if ($context instanceof ResponseInterface) {
            return $context;
        }

        if ($context['version']['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        $sectionId = $this->positiveId($args['sectionId'] ?? null);

        if ($sectionId === null) {
            return $this->sectionNotFound($response);
        }

        $section = $this->findSection($context['version_id'], $sectionId);

        if ($section === null) {
            return $this->sectionNotFound($response);
        }

        if ((int) $section['total_itens'] > 0) {
            return $this->json($response, [
                'error' => 'section_has_items',
                'message' => 'Secoes com itens nao podem ser excluidas. Desative a secao ou remova os itens primeiro.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM secoes
              WHERE id = :id
                AND instrumento_versao_id = :versao_id'
        );
        $stmt->execute([
            'id' => $sectionId,
            'versao_id' => $context['version_id'],
        ]);

        return $this->json($response, [
            'message' => 'Secao excluida com sucesso.',
        ]);
    }

    /**
     * @return array<string,mixed>|ResponseInterface
     */
    private function resolveContext(
        ServerRequestInterface $request,
        array $args
    ): array|ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized(new \Slim\Psr7\Response());
        }

        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($instrumentId === null || $versionId === null) {
            return $this->versionNotFound(new \Slim\Psr7\Response());
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.instrumento_id,
                v.numero_versao,
                v.status,
                v.publicado_em
             FROM instrumento_versoes v
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             WHERE v.id = :versao_id
               AND v.instrumento_id = :instrumento_id
               AND i.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'versao_id' => $versionId,
            'instrumento_id' => $instrumentId,
            'profissional_id' => $professionalId,
        ]);

        $version = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($version === false) {
            return $this->versionNotFound(new \Slim\Psr7\Response());
        }

        $version['id'] = (int) $version['id'];
        $version['instrumento_id'] = (int) $version['instrumento_id'];

        return [
            'professional_id' => $professionalId,
            'instrument_id' => $instrumentId,
            'version_id' => $versionId,
            'version' => $version,
        ];
    }

    /**
     * @return array{titulo:string,descricao:?string,ordem:?int,ativo:bool}|ResponseInterface
     */
    private function validatePayload(
        array $data,
        ResponseInterface $response,
        bool $requireOrder = false
    ): array|ResponseInterface {
        $titulo = trim((string) ($data['titulo'] ?? ''));
        $descricao = $this->optional($data['descricao'] ?? null);
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

        if ($titulo === '' || strlen($titulo) > 180) {
            return $this->validation(
                $response,
                'Titulo e obrigatorio e deve ter no maximo 180 caracteres.'
            );
        }

        if ($descricao !== null && strlen($descricao) > 10000) {
            return $this->validation(
                $response,
                'Descricao deve ter no maximo 10000 caracteres.'
            );
        }

        if ($ativo === null) {
            return $this->validation($response, 'Status ativo invalido.');
        }

        return [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'ordem' => $ordem,
            'ativo' => $ativo,
        ];
    }

    private function findSection(int $versionId, int $sectionId): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                s.id,
                s.instrumento_versao_id,
                s.titulo,
                s.descricao,
                s.ordem,
                s.ativo,
                s.created_at,
                s.updated_at,
                (SELECT COUNT(*)
                   FROM itens i
                  WHERE i.secao_id = s.id) AS total_itens
             FROM secoes s
             WHERE s.id = :id
               AND s.instrumento_versao_id = :versao_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $sectionId,
            'versao_id' => $versionId,
        ]);

        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($section === false) {
            return null;
        }

        return $this->normalizeSection($section);
    }

    private function normalizeSection(array $section): array
    {
        $section['id'] = (int) $section['id'];
        $section['instrumento_versao_id'] = (int) $section['instrumento_versao_id'];
        $section['ordem'] = (int) $section['ordem'];
        $section['ativo'] = (bool) $section['ativo'];
        $section['total_itens'] = (int) ($section['total_itens'] ?? 0);

        return $section;
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

    private function versionNotFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Versao nao encontrada.',
        ], 404);
    }

    private function sectionNotFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Secao nao encontrada.',
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
