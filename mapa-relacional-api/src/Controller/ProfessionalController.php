<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AuditService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfessionalController
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function profile(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $id = $this->professionalId($request);

        if ($id === null) {
            return $this->json($response, [
                'error' => 'unauthorized',
                'message' => 'Autenticacao profissional obrigatoria.',
            ], 401);
        }

        $professional = $this->find($id);

        if ($professional === null) {
            return $this->json($response, [
                'error' => 'not_found',
                'message' => 'Profissional nao encontrado.',
            ], 404);
        }

        return $this->json($response, ['profissional' => $professional]);
    }

    public function updateProfile(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $id = $this->professionalId($request);

        if ($id === null) {
            return $this->json($response, [
                'error' => 'unauthorized',
                'message' => 'Autenticacao profissional obrigatoria.',
            ], 401);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $nome = trim((string) ($data['nome'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $telefone = $this->optional($data['telefone'] ?? null);
        $descricao = $this->optional($data['descricao'] ?? null);
        $atuacao = $this->optional($data['atuacao'] ?? null);
        $fotoUrl = $this->optional($data['foto_url'] ?? null);
        $logoUrl = $this->optional($data['logo_url'] ?? null);
        $dadosContato = $this->optional($data['dados_contato'] ?? null);

        if ($nome === '' || strlen($nome) > 150) {
            return $this->validation($response, 'Nome invalido.');
        }

        if (
            $email === ''
            || strlen($email) > 190
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            return $this->validation($response, 'E-mail invalido.');
        }

        if ($telefone !== null && strlen($telefone) > 30) {
            return $this->validation($response, 'Telefone muito longo.');
        }

        if ($descricao !== null && strlen($descricao) > 4000) {
            return $this->validation($response, 'Descricao muito longa.');
        }

        if ($atuacao !== null && strlen($atuacao) > 2000) {
            return $this->validation($response, 'Informacoes de atuacao muito longas.');
        }

        if ($dadosContato !== null && strlen($dadosContato) > 2000) {
            return $this->validation($response, 'Dados de contato muito longos.');
        }

        foreach ([$fotoUrl, $logoUrl] as $url) {
            if ($url === null) {
                continue;
            }

            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if (
                strlen($url) > 500
                || filter_var($url, FILTER_VALIDATE_URL) === false
                || !in_array($scheme, ['http', 'https'], true)
            ) {
                return $this->validation($response, 'URL de imagem invalida.');
            }
        }

        $pdo = Database::connect();

        $check = $pdo->prepare(
            'SELECT id FROM profissionais
              WHERE email = :email AND id <> :id
              LIMIT 1'
        );
        $check->execute(['email' => $email, 'id' => $id]);

        if ($check->fetchColumn() !== false) {
            return $this->json($response, [
                'error' => 'email_in_use',
                'message' => 'Este e-mail ja esta em uso.',
            ], 409);
        }

        $stmt = $pdo->prepare(
            'UPDATE profissionais
                SET nome = :nome,
                    email = :email,
                    telefone = :telefone,
                    descricao = :descricao,
                    atuacao = :atuacao,
                    foto_url = :foto_url,
                    logo_url = :logo_url,
                    dados_contato = :dados_contato
              WHERE id = :id'
        );

        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'descricao' => $descricao,
            'atuacao' => $atuacao,
            'foto_url' => $fotoUrl,
            'logo_url' => $logoUrl,
            'dados_contato' => $dadosContato,
            'id' => $id,
        ]);

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $id,
            'PERFIL_ATUALIZADO',
            'PROFISSIONAL',
            $id,
            [],
            $request,
            $id,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Perfil atualizado com sucesso.',
            'profissional' => $this->find($id),
        ]);
    }

    public function deleteProfileImage(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = $this->professionalId($request);

        if ($id === null) {
            return $this->json($response, [
                'error' => 'unauthorized',
                'message' => 'Autenticacao profissional obrigatoria.',
            ], 401);
        }

        $type = strtolower(trim((string) ($args['tipo'] ?? '')));
        $fields = [
            'foto' => 'foto_url',
            'logo' => 'logo_url',
        ];
        $field = $fields[$type] ?? null;

        if ($field === null) {
            return $this->validation($response, 'Tipo de imagem invalido.');
        }

        $professional = $this->find($id);

        if ($professional === null) {
            return $this->json($response, [
                'error' => 'not_found',
                'message' => 'Profissional nao encontrado.',
            ], 404);
        }

        $currentUrl = $professional[$field] ?? null;

        if ($currentUrl === null || trim((string) $currentUrl) === '') {
            return $this->json($response, [
                'message' => 'A imagem ja esta removida.',
                'profissional' => $professional,
            ]);
        }

        $currentUrl = (string) $currentUrl;
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            "UPDATE profissionais
                SET {$field} = NULL
              WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);

        $fileRemoved = false;
        $fileKeptBecauseReferenced = false;

        if ($this->imageUrlStillReferenced($pdo, $currentUrl)) {
            $fileKeptBecauseReferenced = true;
        } else {
            $fileRemoved = $this->deleteLocalProfessionalUpload(
                $id,
                $currentUrl
            );
        }

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $id,
            'PERFIL_IMAGEM_EXCLUIDA',
            'PROFISSIONAL',
            $id,
            [
                'tipo' => $type,
                'arquivo_local_removido' => $fileRemoved,
                'arquivo_mantido_por_referencia' => $fileKeptBecauseReferenced,
            ],
            $request,
            $id,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Imagem excluida do perfil com sucesso.',
            'profissional' => $this->find($id),
        ]);
    }

    private function imageUrlStillReferenced(PDO $pdo, string $url): bool
    {
        $professionalStmt = $pdo->prepare(
            'SELECT COUNT(*)
               FROM profissionais
              WHERE foto_url = :foto_url
                 OR logo_url = :logo_url'
        );
        $professionalStmt->execute([
            'foto_url' => $url,
            'logo_url' => $url,
        ]);

        if ((int) $professionalStmt->fetchColumn() > 0) {
            return true;
        }

        $blockStmt = $pdo->prepare(
            'SELECT COUNT(*)
               FROM site_blocos
              WHERE midia_url = :midia_url'
        );
        $blockStmt->execute(['midia_url' => $url]);

        return (int) $blockStmt->fetchColumn() > 0;
    }

    private function deleteLocalProfessionalUpload(
        int $professionalId,
        string $url
    ): bool {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return false;
        }

        $prefix = '/uploads/site/' . $professionalId . '/';

        if (!str_starts_with($path, $prefix)) {
            return false;
        }

        $filename = substr($path, strlen($prefix));

        if (
            $filename === ''
            || str_contains($filename, '/')
            || preg_match(
                '/^[a-f0-9]{32}\.(?:jpg|png|webp)$/',
                $filename
            ) !== 1
        ) {
            return false;
        }

        $targetPath = dirname(__DIR__, 2)
            . '/public'
            . $prefix
            . $filename;

        if (!is_file($targetPath)) {
            return false;
        }

        return @unlink($targetPath);
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

    private function find(int $id): ?array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT id, nome, email, telefone, descricao, atuacao,
                    foto_url, logo_url, dados_contato, status,
                    ultimo_login_em, created_at, updated_at
               FROM profissionais
              WHERE id = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $professional = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($professional === false) {
            return null;
        }

        $professional['id'] = (int) $professional['id'];

        return $professional;
    }

    private function optional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
