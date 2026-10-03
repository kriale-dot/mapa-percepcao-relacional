<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProfessionalController
{
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
            if (
                $url !== null
                && (
                    strlen($url) > 500
                    || filter_var($url, FILTER_VALIDATE_URL) === false
                )
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

        return $this->json($response, [
            'message' => 'Perfil atualizado com sucesso.',
            'profissional' => $this->find($id),
        ]);
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
