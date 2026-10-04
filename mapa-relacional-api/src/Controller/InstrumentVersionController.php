<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AuditService;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InstrumentVersionController
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if (
            $instrumentId === null
            || !$this->ownsInstrument($professionalId, $instrumentId)
        ) {
            return $this->instrumentNotFound($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.instrumento_id,
                v.numero_versao,
                v.status,
                v.publicado_em,
                v.created_at,
                v.updated_at,
                (SELECT COUNT(*)
                   FROM secoes s
                  WHERE s.instrumento_versao_id = v.id) AS total_secoes,
                (SELECT COUNT(*)
                   FROM aplicacoes a
                  WHERE a.instrumento_versao_id = v.id) AS total_aplicacoes
             FROM instrumento_versoes v
             WHERE v.instrumento_id = :instrumento_id
             ORDER BY v.id DESC'
        );
        $stmt->execute(['instrumento_id' => $instrumentId]);

        $versions = array_map(
            [$this, 'normalizeVersion'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, ['versoes' => $versions]);
    }

    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if (
            $instrumentId === null
            || !$this->ownsInstrument($professionalId, $instrumentId)
        ) {
            return $this->instrumentNotFound($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $numeroVersao = trim((string) ($data['numero_versao'] ?? ''));

        if ($numeroVersao === '' || strlen($numeroVersao) > 30) {
            return $this->validation(
                $response,
                'Numero da versao e obrigatorio e deve ter no maximo 30 caracteres.'
            );
        }

        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO instrumento_versoes (
                    instrumento_id,
                    numero_versao,
                    status
                 ) VALUES (
                    :instrumento_id,
                    :numero_versao,
                    :status
                 )'
            );
            $stmt->execute([
                'instrumento_id' => $instrumentId,
                'numero_versao' => $numeroVersao,
                'status' => 'RASCUNHO',
            ]);

            $versionId = (int) $pdo->lastInsertId();

            $bandStmt = $pdo->prepare(
                'INSERT INTO resultado_faixas (
                    instrumento_versao_id,
                    codigo,
                    rotulo,
                    minimo,
                    maximo,
                    ordem
                 ) VALUES (
                    :versao_id,
                    :codigo,
                    :rotulo,
                    :minimo,
                    :maximo,
                    :ordem
                 )'
            );

            foreach ([
                ['RUIM', 'Ruim', 0.00, 33.99, 1],
                ['REGULAR', 'Regular', 34.00, 66.99, 2],
                ['BOM', 'Bom', 67.00, 100.00, 3],
            ] as [$codigo, $rotulo, $minimo, $maximo, $ordem]) {
                $bandStmt->execute([
                    'versao_id' => $versionId,
                    'codigo' => $codigo,
                    'rotulo' => $rotulo,
                    'minimo' => $minimo,
                    'maximo' => $maximo,
                    'ordem' => $ordem,
                ]);
            }

            $pdo->commit();
        } catch (PDOException $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ((string) $error->getCode() === '23000') {
                return $this->json($response, [
                    'error' => 'version_number_in_use',
                    'message' => 'Este numero de versao ja existe neste instrumento.',
                ], 409);
            }

            throw $error;
        }

        $this->auditService->record(
            'PROFISSIONAL',
            $professionalId,
            'VERSAO_CRIADA',
            'INSTRUMENTO_VERSAO',
            $versionId,
            [
                'instrumento_id' => $instrumentId,
                'numero_versao' => $numeroVersao,
            ],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json(
            $response,
            [
                'message' => 'Versao criada com sucesso.',
                'versao' => $this->findVersion(
                    $professionalId,
                    $instrumentId,
                    $versionId
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
        $professionalId = $this->professionalId($request);
        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($instrumentId === null || $versionId === null) {
            return $this->versionNotFound($response);
        }

        $version = $this->findVersion(
            $professionalId,
            $instrumentId,
            $versionId
        );

        if ($version === null) {
            return $this->versionNotFound($response);
        }

        if ($version['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $numeroVersao = trim((string) ($data['numero_versao'] ?? ''));

        if ($numeroVersao === '' || strlen($numeroVersao) > 30) {
            return $this->validation(
                $response,
                'Numero da versao e obrigatorio e deve ter no maximo 30 caracteres.'
            );
        }

        $pdo = Database::connect();

        try {
            $stmt = $pdo->prepare(
                'UPDATE instrumento_versoes
                    SET numero_versao = :numero_versao
                  WHERE id = :id
                    AND instrumento_id = :instrumento_id
                    AND status = :status'
            );
            $stmt->execute([
                'numero_versao' => $numeroVersao,
                'id' => $versionId,
                'instrumento_id' => $instrumentId,
                'status' => 'RASCUNHO',
            ]);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                return $this->json($response, [
                    'error' => 'version_number_in_use',
                    'message' => 'Este numero de versao ja existe neste instrumento.',
                ], 409);
            }

            throw $error;
        }

        return $this->json($response, [
            'message' => 'Versao atualizada com sucesso.',
            'versao' => $this->findVersion(
                $professionalId,
                $instrumentId,
                $versionId
            ),
        ]);
    }

    public function publish(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($instrumentId === null || $versionId === null) {
            return $this->versionNotFound($response);
        }

        $version = $this->findVersion(
            $professionalId,
            $instrumentId,
            $versionId
        );

        if ($version === null) {
            return $this->versionNotFound($response);
        }

        if ($version['status'] !== 'RASCUNHO') {
            return $this->json($response, [
                'error' => 'invalid_version_transition',
                'message' => 'Somente versoes em rascunho podem ser publicadas.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE instrumento_versoes
                SET status = :published_status,
                    publicado_em = NOW()
              WHERE id = :id
                AND instrumento_id = :instrumento_id
                AND status = :draft_status'
        );
        $stmt->execute([
            'published_status' => 'PUBLICADA',
            'id' => $versionId,
            'instrumento_id' => $instrumentId,
            'draft_status' => 'RASCUNHO',
        ]);

        $this->auditService->record(
            'PROFISSIONAL',
            $professionalId,
            'VERSAO_PUBLICADA',
            'INSTRUMENTO_VERSAO',
            $versionId,
            ['instrumento_id' => $instrumentId],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Versao publicada com sucesso.',
            'versao' => $this->findVersion(
                $professionalId,
                $instrumentId,
                $versionId
            ),
        ]);
    }

    public function archive(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($instrumentId === null || $versionId === null) {
            return $this->versionNotFound($response);
        }

        $version = $this->findVersion(
            $professionalId,
            $instrumentId,
            $versionId
        );

        if ($version === null) {
            return $this->versionNotFound($response);
        }

        if ($version['status'] !== 'PUBLICADA') {
            return $this->json($response, [
                'error' => 'invalid_version_transition',
                'message' => 'Somente versoes publicadas podem ser arquivadas.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE instrumento_versoes
                SET status = :archived_status
              WHERE id = :id
                AND instrumento_id = :instrumento_id
                AND status = :published_status'
        );
        $stmt->execute([
            'archived_status' => 'ARQUIVADA',
            'id' => $versionId,
            'instrumento_id' => $instrumentId,
            'published_status' => 'PUBLICADA',
        ]);

        $this->auditService->record(
            'PROFISSIONAL',
            $professionalId,
            'VERSAO_ARQUIVADA',
            'INSTRUMENTO_VERSAO',
            $versionId,
            ['instrumento_id' => $instrumentId],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Versao arquivada com sucesso.',
            'versao' => $this->findVersion(
                $professionalId,
                $instrumentId,
                $versionId
            ),
        ]);
    }

    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $instrumentId = $this->positiveId($args['instrumentId'] ?? null);
        $versionId = $this->positiveId($args['versionId'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($instrumentId === null || $versionId === null) {
            return $this->versionNotFound($response);
        }

        $version = $this->findVersion(
            $professionalId,
            $instrumentId,
            $versionId
        );

        if ($version === null) {
            return $this->versionNotFound($response);
        }

        if ($version['status'] !== 'RASCUNHO') {
            return $this->immutable($response);
        }

        if (
            (int) $version['total_secoes'] > 0
            || (int) $version['total_aplicacoes'] > 0
        ) {
            return $this->json($response, [
                'error' => 'version_in_use',
                'message' => 'A versao possui estrutura ou aplicacoes e nao pode ser excluida.',
            ], 409);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM instrumento_versoes
              WHERE id = :id
                AND instrumento_id = :instrumento_id
                AND status = :status'
        );
        $stmt->execute([
            'id' => $versionId,
            'instrumento_id' => $instrumentId,
            'status' => 'RASCUNHO',
        ]);

        return $this->json($response, [
            'message' => 'Versao excluida com sucesso.',
        ]);
    }

    private function findVersion(
        int $professionalId,
        int $instrumentId,
        int $versionId
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                v.id,
                v.instrumento_id,
                v.numero_versao,
                v.status,
                v.publicado_em,
                v.created_at,
                v.updated_at,
                (SELECT COUNT(*)
                   FROM secoes s
                  WHERE s.instrumento_versao_id = v.id) AS total_secoes,
                (SELECT COUNT(*)
                   FROM aplicacoes a
                  WHERE a.instrumento_versao_id = v.id) AS total_aplicacoes
             FROM instrumento_versoes v
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             WHERE v.id = :version_id
               AND v.instrumento_id = :instrument_id
               AND i.profissional_id = :professional_id
             LIMIT 1'
        );
        $stmt->execute([
            'version_id' => $versionId,
            'instrument_id' => $instrumentId,
            'professional_id' => $professionalId,
        ]);

        $version = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($version === false) {
            return null;
        }

        return $this->normalizeVersion($version);
    }

    private function ownsInstrument(int $professionalId, int $instrumentId): bool
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT 1
               FROM instrumentos
              WHERE id = :id
                AND profissional_id = :professional_id
              LIMIT 1'
        );
        $stmt->execute([
            'id' => $instrumentId,
            'professional_id' => $professionalId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function normalizeVersion(array $version): array
    {
        $version['id'] = (int) $version['id'];
        $version['instrumento_id'] = (int) $version['instrumento_id'];
        $version['total_secoes'] = (int) ($version['total_secoes'] ?? 0);
        $version['total_aplicacoes'] = (int) ($version['total_aplicacoes'] ?? 0);

        return $version;
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

    private function unauthorized(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'unauthorized',
            'message' => 'Autenticacao profissional obrigatoria.',
        ], 401);
    }

    private function instrumentNotFound(
        ResponseInterface $response
    ): ResponseInterface {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Instrumento nao encontrado.',
        ], 404);
    }

    private function versionNotFound(
        ResponseInterface $response
    ): ResponseInterface {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Versao nao encontrada.',
        ], 404);
    }

    private function immutable(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'version_immutable',
            'message' => 'Versoes publicadas ou arquivadas sao imutaveis. Crie uma nova versao para fazer alteracoes.',
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
