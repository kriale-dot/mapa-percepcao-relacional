<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AuditService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SiteController
{
    private const TYPES = [
        'TITULO',
        'TEXTO',
        'IMAGEM',
        'TEXTO_IMAGEM',
        'VIDEO',
        'AUDIO',
        'PERFIL',
        'APRESENTACAO',
        'CTA',
        'LINK',
        'AVALIACAO',
        'BIBLIOTECA',
    ];

    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function publicShow(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $pdo = Database::connect();

        $professionalStmt = $pdo->query(
            'SELECT
                id,
                nome,
                email,
                telefone,
                descricao,
                atuacao,
                foto_url,
                logo_url,
                dados_contato
             FROM profissionais
             WHERE status = \'ATIVO\'
             ORDER BY id ASC
             LIMIT 1'
        );

        $professional = $professionalStmt->fetch(PDO::FETCH_ASSOC);

        if ($professional === false) {
            return $this->json($response, [
                'error' => 'site_unavailable',
                'message' => 'Site institucional indisponivel.',
            ], 404);
        }

        $professionalId = (int) $professional['id'];

        $blocksStmt = $pdo->prepare(
            'SELECT
                id,
                tipo,
                titulo,
                descricao,
                conteudo,
                midia_url,
                texto_alternativo,
                link_url,
                link_texto,
                ordem
             FROM site_blocos
             WHERE profissional_id = :profissional_id
               AND status = :status
               AND visivel = 1
             ORDER BY ordem ASC, id ASC'
        );
        $blocksStmt->execute([
            'profissional_id' => $professionalId,
            'status' => 'ATIVO',
        ]);

        $blocks = array_map(
            [$this, 'normalizeBlock'],
            $blocksStmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'profissional' => $this->normalizeProfessional($professional),
            'blocos' => $blocks,
        ]);
    }

    public function uploadImage(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        $uploadedFiles = $request->getUploadedFiles();
        $image = $uploadedFiles['imagem'] ?? null;

        if ($image === null) {
            return $this->validation(
                $response,
                'Selecione uma imagem para enviar.'
            );
        }

        if ($image->getError() !== UPLOAD_ERR_OK) {
            $message = match ($image->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'A imagem excede o limite permitido pelo servidor.',
                UPLOAD_ERR_PARTIAL =>
                    'O envio da imagem foi interrompido. Tente novamente.',
                UPLOAD_ERR_NO_FILE =>
                    'Nenhuma imagem foi selecionada.',
                default =>
                    'Nao foi possivel receber a imagem enviada.',
            };

            return $this->validation($response, $message);
        }

        $maxMb = max(
            1,
            min(20, (int) ($_ENV['SITE_IMAGE_MAX_MB'] ?? 5))
        );
        $maxBytes = $maxMb * 1024 * 1024;
        $size = $image->getSize();

        if ($size === null || $size <= 0 || $size > $maxBytes) {
            return $this->validation(
                $response,
                "A imagem deve ter no maximo {$maxMb} MB."
            );
        }

        $stream = $image->getStream();
        $stream->rewind();
        $contents = $stream->getContents();

        if ($contents === '' || strlen($contents) > $maxBytes) {
            return $this->validation(
                $response,
                "A imagem deve ter no maximo {$maxMb} MB."
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->buffer($contents);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            return $this->validation(
                $response,
                'Formato invalido. Envie JPG, PNG ou WEBP.'
            );
        }

        $publicRoot = dirname(__DIR__, 2) . '/public';
        $relativeDir = '/uploads/site/' . $professionalId;
        $targetDir = $publicRoot . $relativeDir;

        if (
            !is_dir($targetDir)
            && !@mkdir($targetDir, 0775, true)
            && !is_dir($targetDir)
        ) {
            return $this->json($response, [
                'error' => 'upload_directory_unavailable',
                'message' => 'Nao foi possivel preparar a pasta de imagens.',
            ], 500);
        }

        $filename = bin2hex(random_bytes(16))
            . '.'
            . $extensions[$mime];
        $targetPath = $targetDir . '/' . $filename;

        if (@file_put_contents($targetPath, $contents, LOCK_EX) === false) {
            return $this->json($response, [
                'error' => 'upload_write_failed',
                'message' => 'Nao foi possivel salvar a imagem no servidor.',
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

        $url = $baseUrl . $relativeUrl;

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'SITE_IMAGEM_ENVIADA',
            'PROFISSIONAL',
            $professionalId,
            [
                'mime' => $mime,
                'tamanho_bytes' => strlen($contents),
            ],
            $request,
            $professionalId
        );

        return $this->json($response, [
            'message' => 'Imagem enviada com sucesso.',
            'imagem' => [
                'url' => $url,
                'caminho' => $relativeUrl,
                'mime' => $mime,
                'tamanho_bytes' => strlen($contents),
            ],
        ], 201);
    }

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
                id,
                tipo,
                titulo,
                descricao,
                conteudo,
                midia_url,
                texto_alternativo,
                link_url,
                link_texto,
                ordem,
                visivel,
                status,
                created_at,
                updated_at
             FROM site_blocos
             WHERE profissional_id = :profissional_id
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
        ]);

        return $this->json($response, [
            'blocos' => array_map(
                [$this, 'normalizeBlock'],
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            ),
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

        $data = $this->validatedData($request, $response);

        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $pdo = Database::connect();

        $nextOrderStmt = $pdo->prepare(
            'SELECT COALESCE(MAX(ordem), 0) + 10
             FROM site_blocos
             WHERE profissional_id = :profissional_id'
        );
        $nextOrderStmt->execute([
            'profissional_id' => $professionalId,
        ]);

        $order = (int) $nextOrderStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO site_blocos (
                profissional_id,
                tipo,
                titulo,
                descricao,
                conteudo,
                midia_url,
                texto_alternativo,
                link_url,
                link_texto,
                ordem,
                visivel,
                status
             ) VALUES (
                :profissional_id,
                :tipo,
                :titulo,
                :descricao,
                :conteudo,
                :midia_url,
                :texto_alternativo,
                :link_url,
                :link_texto,
                :ordem,
                :visivel,
                :status
             )'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'],
            'conteudo' => $data['conteudo'],
            'midia_url' => $data['midia_url'],
            'texto_alternativo' => $data['texto_alternativo'],
            'link_url' => $data['link_url'],
            'link_texto' => $data['link_texto'],
            'ordem' => $order,
            'visivel' => $data['visivel'] ? 1 : 0,
            'status' => $data['status'],
        ]);

        $id = (int) $pdo->lastInsertId();

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'SITE_BLOCO_CRIADO',
            'SITE_BLOCO',
            $id,
            ['tipo' => $data['tipo']],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json(
            $response,
            [
                'message' => 'Bloco criado com sucesso.',
                'bloco' => $this->findBlock($professionalId, $id),
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
        $id = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($id === null || $this->findBlock($professionalId, $id) === null) {
            return $this->notFound($response);
        }

        $data = $this->validatedData($request, $response);

        if ($data instanceof ResponseInterface) {
            return $data;
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE site_blocos
                SET tipo = :tipo,
                    titulo = :titulo,
                    descricao = :descricao,
                    conteudo = :conteudo,
                    midia_url = :midia_url,
                    texto_alternativo = :texto_alternativo,
                    link_url = :link_url,
                    link_texto = :link_texto,
                    visivel = :visivel,
                    status = :status
              WHERE id = :id
                AND profissional_id = :profissional_id'
        );
        $stmt->execute([
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'],
            'conteudo' => $data['conteudo'],
            'midia_url' => $data['midia_url'],
            'texto_alternativo' => $data['texto_alternativo'],
            'link_url' => $data['link_url'],
            'link_texto' => $data['link_texto'],
            'visivel' => $data['visivel'] ? 1 : 0,
            'status' => $data['status'],
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'SITE_BLOCO_ATUALIZADO',
            'SITE_BLOCO',
            $id,
            ['tipo' => $data['tipo']],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Bloco atualizado com sucesso.',
            'bloco' => $this->findBlock($professionalId, $id),
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

        if ($id === null || $this->findBlock($professionalId, $id) === null) {
            return $this->notFound($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'DELETE FROM site_blocos
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
            'SITE_BLOCO_EXCLUIDO',
            'SITE_BLOCO',
            $id,
            [],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Bloco excluido com sucesso.',
        ]);
    }

    public function move(
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

        $current = $this->findBlock($professionalId, $id);

        if ($current === null) {
            return $this->notFound($response);
        }

        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $direction = strtoupper(trim((string) ($body['direcao'] ?? '')));

        if (!in_array($direction, ['CIMA', 'BAIXO'], true)) {
            return $this->validation(
                $response,
                'Direcao invalida. Use CIMA ou BAIXO.'
            );
        }

        $pdo = Database::connect();

        $operator = $direction === 'CIMA' ? '<' : '>';
        $orderDirection = $direction === 'CIMA' ? 'DESC' : 'ASC';

        $neighborStmt = $pdo->prepare(
            "SELECT id, ordem
             FROM site_blocos
             WHERE profissional_id = :profissional_id
               AND (
                   ordem {$operator} :ordem
                   OR (ordem = :ordem_igual AND id {$operator} :id)
               )
             ORDER BY ordem {$orderDirection}, id {$orderDirection}
             LIMIT 1"
        );
        $neighborStmt->execute([
            'profissional_id' => $professionalId,
            'ordem' => (int) $current['ordem'],
            'ordem_igual' => (int) $current['ordem'],
            'id' => $id,
        ]);

        $neighbor = $neighborStmt->fetch(PDO::FETCH_ASSOC);

        if ($neighbor === false) {
            return $this->json($response, [
                'message' => 'O bloco ja esta no limite desta direcao.',
                'blocos' => $this->listBlocks($professionalId),
            ]);
        }

        $pdo->beginTransaction();

        try {
            $temporary = $pdo->prepare(
                'UPDATE site_blocos
                    SET ordem = -1
                  WHERE id = :id
                    AND profissional_id = :profissional_id'
            );
            $temporary->execute([
                'id' => $id,
                'profissional_id' => $professionalId,
            ]);

            $swapNeighbor = $pdo->prepare(
                'UPDATE site_blocos
                    SET ordem = :ordem
                  WHERE id = :id
                    AND profissional_id = :profissional_id'
            );
            $swapNeighbor->execute([
                'ordem' => (int) $current['ordem'],
                'id' => (int) $neighbor['id'],
                'profissional_id' => $professionalId,
            ]);

            $swapCurrent = $pdo->prepare(
                'UPDATE site_blocos
                    SET ordem = :ordem
                  WHERE id = :id
                    AND profissional_id = :profissional_id'
            );
            $swapCurrent->execute([
                'ordem' => (int) $neighbor['ordem'],
                'id' => $id,
                'profissional_id' => $professionalId,
            ]);

            $this->auditService->recordSafe(
                'PROFISSIONAL',
                $professionalId,
                'SITE_BLOCO_REORDENADO',
                'SITE_BLOCO',
                $id,
                ['direcao' => $direction],
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
            'message' => 'Ordem atualizada.',
            'blocos' => $this->listBlocks($professionalId),
        ]);
    }

    private function validatedData(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): array|ResponseInterface {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];

        $type = strtoupper(trim((string) ($body['tipo'] ?? '')));
        $title = $this->optional($body['titulo'] ?? null);
        $description = $this->optional($body['descricao'] ?? null);
        $content = $this->optional($body['conteudo'] ?? null);
        $mediaUrl = $this->optional($body['midia_url'] ?? null);
        $altText = $this->optional($body['texto_alternativo'] ?? null);
        $linkUrl = $this->optional($body['link_url'] ?? null);
        $linkText = $this->optional($body['link_texto'] ?? null);
        $visible = filter_var(
            $body['visivel'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );
        $status = strtoupper(trim((string) ($body['status'] ?? 'ATIVO')));

        if (!in_array($type, self::TYPES, true)) {
            return $this->validation(
                $response,
                'Tipo de bloco invalido.'
            );
        }

        if ($title !== null && strlen($title) > 200) {
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

        if ($content !== null && strlen($content) > 20000) {
            return $this->validation(
                $response,
                'Conteudo deve ter no maximo 20000 caracteres.'
            );
        }

        if ($altText !== null && strlen($altText) > 255) {
            return $this->validation(
                $response,
                'Texto alternativo deve ter no maximo 255 caracteres.'
            );
        }

        if ($linkText !== null && strlen($linkText) > 120) {
            return $this->validation(
                $response,
                'Texto do link deve ter no maximo 120 caracteres.'
            );
        }

        foreach ([$mediaUrl, $linkUrl] as $url) {
            if ($url === null || str_starts_with($url, '/')) {
                continue;
            }

            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if (
                strlen($url) > 1000
                || filter_var($url, FILTER_VALIDATE_URL) === false
                || !in_array($scheme, ['http', 'https'], true)
            ) {
                return $this->validation(
                    $response,
                    'URL de bloco invalida.'
                );
            }
        }

        if ($visible === null) {
            return $this->validation(
                $response,
                'Visibilidade invalida.'
            );
        }

        if (!in_array($status, ['ATIVO', 'INATIVO'], true)) {
            return $this->validation(
                $response,
                'Status de bloco invalido.'
            );
        }

        if (
            in_array(
                $type,
                ['IMAGEM', 'TEXTO_IMAGEM', 'VIDEO', 'AUDIO'],
                true
            )
            && $mediaUrl === null
        ) {
            return $this->validation(
                $response,
                'Este tipo de bloco precisa de uma URL de midia.'
            );
        }

        return [
            'tipo' => $type,
            'titulo' => $title,
            'descricao' => $description,
            'conteudo' => $content,
            'midia_url' => $mediaUrl,
            'texto_alternativo' => $altText,
            'link_url' => $linkUrl,
            'link_texto' => $linkText,
            'visivel' => $visible,
            'status' => $status,
        ];
    }

    private function listBlocks(int $professionalId): array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                id,
                tipo,
                titulo,
                descricao,
                conteudo,
                midia_url,
                texto_alternativo,
                link_url,
                link_texto,
                ordem,
                visivel,
                status,
                created_at,
                updated_at
             FROM site_blocos
             WHERE profissional_id = :profissional_id
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute([
            'profissional_id' => $professionalId,
        ]);

        return array_map(
            [$this, 'normalizeBlock'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function findBlock(
        int $professionalId,
        int $id
    ): ?array {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                id,
                tipo,
                titulo,
                descricao,
                conteudo,
                midia_url,
                texto_alternativo,
                link_url,
                link_texto,
                ordem,
                visivel,
                status,
                created_at,
                updated_at
             FROM site_blocos
             WHERE id = :id
               AND profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'profissional_id' => $professionalId,
        ]);

        $block = $stmt->fetch(PDO::FETCH_ASSOC);

        return $block === false
            ? null
            : $this->normalizeBlock($block);
    }

    private function normalizeBlock(array $block): array
    {
        $block['id'] = (int) $block['id'];
        $block['ordem'] = (int) $block['ordem'];

        if (array_key_exists('visivel', $block)) {
            $block['visivel'] = (bool) $block['visivel'];
        }

        return $block;
    }

    private function normalizeProfessional(array $professional): array
    {
        return [
            'id' => (int) $professional['id'],
            'nome' => (string) $professional['nome'],
            'email' => (string) $professional['email'],
            'telefone' => $professional['telefone'],
            'descricao' => $professional['descricao'],
            'atuacao' => $professional['atuacao'],
            'foto_url' => $professional['foto_url'],
            'logo_url' => $professional['logo_url'],
            'dados_contato' => $professional['dados_contato'],
        ];
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
            'message' => 'Bloco do site nao encontrado.',
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
