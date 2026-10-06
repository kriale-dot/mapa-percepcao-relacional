<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AuditService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class LibraryController
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function publicIndex(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $pdo = Database::connect();

        $professionalStmt = $pdo->query(
            'SELECT id, nome, logo_url
             FROM profissionais
             WHERE status = \'ATIVO\'
             ORDER BY id ASC
             LIMIT 1'
        );

        $professional = $professionalStmt->fetch(PDO::FETCH_ASSOC);

        if ($professional === false) {
            return $this->json($response, [
                'error' => 'library_unavailable',
                'message' => 'Biblioteca indisponivel.',
            ], 404);
        }

        $query = $this->searchQuery($request);

        if ($query === null) {
            return $this->validation(
                $response,
                'A busca deve ter no maximo 120 caracteres.'
            );
        }

        $documents = $this->listDocuments(
            (int) $professional['id'],
            true,
            $query
        );

        return $this->json($response, [
            'profissional' => [
                'id' => (int) $professional['id'],
                'nome' => (string) $professional['nome'],
                'logo_url' => $professional['logo_url'],
            ],
            'busca' => $query,
            'total' => count($documents),
            'documentos' => $documents,
        ]);
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $query = $this->searchQuery($request);

        if ($query === null) {
            return $this->validation(
                $response,
                'A busca deve ter no maximo 120 caracteres.'
            );
        }

        $documents = $this->listDocuments(
            $professionalId,
            false,
            $query
        );

        return $this->json($response, [
            'busca' => $query,
            'total' => count($documents),
            'documentos' => $documents,
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

        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];

        $data = $this->validatedMetadata($body, $response);

        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['arquivo'] ?? null;

        if (!$file instanceof UploadedFileInterface) {
            return $this->validation(
                $response,
                'Selecione um arquivo PDF ou PNG.'
            );
        }

        $stored = $this->storeFile(
            $request,
            $response,
            $professionalId,
            $file
        );

        if ($stored instanceof ResponseInterface) {
            return $stored;
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO biblioteca_documentos (
                    profissional_id,
                    titulo,
                    descricao,
                    arquivo_url,
                    arquivo_caminho,
                    nome_original,
                    mime_type,
                    extensao,
                    tamanho_bytes,
                    status
                 ) VALUES (
                    :profissional_id,
                    :titulo,
                    :descricao,
                    :arquivo_url,
                    :arquivo_caminho,
                    :nome_original,
                    :mime_type,
                    :extensao,
                    :tamanho_bytes,
                    :status
                 )'
            );
            $stmt->execute([
                'profissional_id' => $professionalId,
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'arquivo_url' => $stored['url'],
                'arquivo_caminho' => $stored['caminho'],
                'nome_original' => $stored['nome_original'],
                'mime_type' => $stored['mime'],
                'extensao' => $stored['extensao'],
                'tamanho_bytes' => $stored['tamanho_bytes'],
                'status' => $data['status'],
            ]);

            $id = (int) $pdo->lastInsertId();

            $this->auditService->recordSafe(
                'PROFISSIONAL',
                $professionalId,
                'BIBLIOTECA_DOCUMENTO_CRIADO',
                'BIBLIOTECA_DOCUMENTO',
                $id,
                [
                    'tipo' => strtoupper($stored['extensao']),
                    'tamanho_bytes' => $stored['tamanho_bytes'],
                    'status' => $data['status'],
                ],
                $request,
                $professionalId,
                $pdo
            );

            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->deleteStoredFile(
                $professionalId,
                $stored['caminho']
            );

            throw $error;
        }

        $document = $this->findDocument($professionalId, $id);

        return $this->json($response, [
            'message' => 'Documento publicado na biblioteca.',
            'documento' => $document,
        ], 201);
    }

    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $id = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($id === null) {
            return $this->notFound($response);
        }

        $current = $this->findDocument($professionalId, $id);

        if ($current === null) {
            return $this->notFound($response);
        }

        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $data = $this->validatedMetadata($body, $response);

        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE biblioteca_documentos
                    SET titulo = :titulo,
                        descricao = :descricao,
                        status = :status
                  WHERE id = :id
                    AND profissional_id = :profissional_id'
            );
            $stmt->execute([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'status' => $data['status'],
                'id' => $id,
                'profissional_id' => $professionalId,
            ]);

            $this->auditService->recordSafe(
                'PROFISSIONAL',
                $professionalId,
                'BIBLIOTECA_DOCUMENTO_ATUALIZADO',
                'BIBLIOTECA_DOCUMENTO',
                $id,
                ['status' => $data['status']],
                $request,
                $professionalId,
                $pdo
            );

            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        return $this->json($response, [
            'message' => 'Documento atualizado.',
            'documento' => $this->findDocument($professionalId, $id),
        ]);
    }

    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $id = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($id === null) {
            return $this->notFound($response);
        }

        $document = $this->findDocument($professionalId, $id);

        if ($document === null) {
            return $this->notFound($response);
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'DELETE FROM biblioteca_documentos
                 WHERE id = :id
                   AND profissional_id = :profissional_id'
            );
            $stmt->execute([
                'id' => $id,
                'profissional_id' => $professionalId,
            ]);

            $this->auditService->recordSafe(
                'PROFISSIONAL',
                $professionalId,
                'BIBLIOTECA_DOCUMENTO_EXCLUIDO',
                'BIBLIOTECA_DOCUMENTO',
                $id,
                [
                    'tipo' => strtoupper((string) $document['extensao']),
                ],
                $request,
                $professionalId,
                $pdo
            );

            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        $this->deleteStoredFile(
            $professionalId,
            (string) $document['arquivo_caminho']
        );

        return $this->json($response, [
            'message' => 'Documento excluido da biblioteca.',
        ]);
    }

    private function validatedMetadata(
        array $body,
        ResponseInterface $response
    ): array|ResponseInterface {
        $title = trim((string) ($body['titulo'] ?? ''));
        $description = $this->optional($body['descricao'] ?? null);
        $status = strtoupper(trim((string) ($body['status'] ?? 'ATIVO')));

        if ($title === '') {
            return $this->validation(
                $response,
                'Informe o titulo do documento.'
            );
        }

        if (strlen($title) > 200) {
            return $this->validation(
                $response,
                'Titulo deve ter no maximo 200 caracteres.'
            );
        }

        if ($description !== null && strlen($description) > 4000) {
            return $this->validation(
                $response,
                'Descricao deve ter no maximo 4000 caracteres.'
            );
        }

        if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
            return $this->validation(
                $response,
                'Status invalido.'
            );
        }

        return [
            'titulo' => $title,
            'descricao' => $description,
            'status' => $status,
        ];
    }

    private function storeFile(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $professionalId,
        UploadedFileInterface $file
    ): array|ResponseInterface {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $message = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'O arquivo excede o limite permitido pelo servidor.',
                UPLOAD_ERR_PARTIAL =>
                    'O envio do arquivo foi interrompido. Tente novamente.',
                UPLOAD_ERR_NO_FILE =>
                    'Nenhum arquivo foi selecionado.',
                default =>
                    'Nao foi possivel receber o arquivo enviado.',
            };

            return $this->validation($response, $message);
        }

        $maxMb = max(
            1,
            min(100, (int) ($_ENV['LIBRARY_FILE_MAX_MB'] ?? 20))
        );
        $maxBytes = $maxMb * 1024 * 1024;
        $size = $file->getSize();

        if ($size === null || $size <= 0 || $size > $maxBytes) {
            return $this->validation(
                $response,
                "O arquivo deve ter no maximo {$maxMb} MB."
            );
        }

        $stream = $file->getStream();
        $stream->rewind();
        $contents = $stream->getContents();

        if ($contents === '' || strlen($contents) > $maxBytes) {
            return $this->validation(
                $response,
                "O arquivo deve ter no maximo {$maxMb} MB."
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->buffer($contents);

        $extensions = [
            'application/pdf' => 'pdf',
            'application/x-pdf' => 'pdf',
            'image/png' => 'png',
        ];

        if (!isset($extensions[$mime])) {
            return $this->validation(
                $response,
                'Formato invalido. Envie somente PDF ou PNG.'
            );
        }

        $extension = $extensions[$mime];
        $publicRoot = dirname(__DIR__, 2) . '/public';
        $relativeDir = '/uploads/biblioteca/' . $professionalId;
        $targetDir = $publicRoot . $relativeDir;

        if (
            !is_dir($targetDir)
            && !@mkdir($targetDir, 0775, true)
            && !is_dir($targetDir)
        ) {
            return $this->json($response, [
                'error' => 'upload_directory_unavailable',
                'message' => 'Nao foi possivel preparar a pasta da biblioteca.',
            ], 500);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;

        if (@file_put_contents($targetPath, $contents, LOCK_EX) === false) {
            return $this->json($response, [
                'error' => 'upload_write_failed',
                'message' => 'Nao foi possivel salvar o documento no servidor.',
            ], 500);
        }

        $relativeUrl = $relativeDir . '/' . $filename;
        $baseUrl = rtrim(
            trim((string) ($_ENV['APP_URL'] ?? '')),
            '/'
        );

        if (
            $baseUrl === ''
            || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
        ) {
            $uri = $request->getUri();
            $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority();
        }

        $originalName = trim((string) $file->getClientFilename());
        $originalName = $originalName === ''
            ? 'documento.' . $extension
            : basename($originalName);

        if (strlen($originalName) > 255) {
            $originalName = substr($originalName, 0, 255);
        }

        return [
            'url' => $baseUrl . $relativeUrl,
            'caminho' => $relativeUrl,
            'nome_original' => $originalName,
            'mime' => $mime,
            'extensao' => $extension,
            'tamanho_bytes' => strlen($contents),
        ];
    }

    private function listDocuments(
        int $professionalId,
        bool $onlyActive,
        string $query
    ): array {
        $pdo = Database::connect();

        $sql = 'SELECT
                    id,
                    titulo,
                    descricao,
                    arquivo_url,
                    arquivo_caminho,
                    nome_original,
                    mime_type,
                    extensao,
                    tamanho_bytes,
                    status,
                    created_at,
                    updated_at
                FROM biblioteca_documentos
                WHERE profissional_id = :profissional_id';

        $params = [
            'profissional_id' => $professionalId,
        ];

        if ($onlyActive) {
            $sql .= ' AND status = :status';
            $params['status'] = 'ATIVO';
        }

        if ($query !== '') {
            $sql .= ' AND (
                titulo LIKE :busca_titulo
                OR descricao LIKE :busca_descricao
                OR nome_original LIKE :busca_nome
            )';
            $search = '%' . $query . '%';
            $params['busca_titulo'] = $search;
            $params['busca_descricao'] = $search;
            $params['busca_nome'] = $search;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(
            [$this, 'normalizeDocument'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function findDocument(
        int $professionalId,
        int $id
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                id,
                titulo,
                descricao,
                arquivo_url,
                arquivo_caminho,
                nome_original,
                mime_type,
                extensao,
                tamanho_bytes,
                status,
                created_at,
                updated_at
             FROM biblioteca_documentos
             WHERE id = :id
               AND profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        return $document === false
            ? null
            : $this->normalizeDocument($document);
    }

    private function normalizeDocument(array $document): array
    {
        $document['id'] = (int) $document['id'];
        $document['tamanho_bytes'] = (int) $document['tamanho_bytes'];
        $document['tipo'] = strtoupper((string) $document['extensao']);

        return $document;
    }

    private function searchQuery(ServerRequestInterface $request): ?string
    {
        $params = $request->getQueryParams();
        $query = trim((string) ($params['q'] ?? ''));

        return strlen($query) <= 120 ? $query : null;
    }

    private function deleteStoredFile(
        int $professionalId,
        string $relativePath
    ): void {
        $prefix = '/uploads/biblioteca/' . $professionalId . '/';

        if (!str_starts_with($relativePath, $prefix)) {
            return;
        }

        $publicRoot = dirname(__DIR__, 2) . '/public';
        $fullPath = $publicRoot . $relativePath;

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
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
            'message' => 'Documento da biblioteca nao encontrado.',
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
