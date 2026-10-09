<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AuditService;
use App\Service\MailService;
use App\Service\RateLimitService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PasswordResetController
{
    public function __construct(
        private readonly MailService $mailService,
        private readonly RateLimitService $rateLimitService,
        private readonly AuditService $auditService
    ) {}

    public function request(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $email = strtolower(trim((string) ($body['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => 'validation_error', 'message' => 'Informe um e-mail válido.'], 422);
        }

        $pdo = Database::connect();
        $window = 900;
        $ip = $this->rateLimitService->requestKey($request);
        $ipLimit = $this->rateLimitService->hit('pwd_reset_ip', $ip, 15, $window, $pdo);
        $emailLimit = $this->rateLimitService->hit('pwd_reset_email', $email, 3, $window, $pdo);
        if (!$ipLimit['allowed'] || !$emailLimit['allowed']) {
            return $this->json($response, [
                'error' => 'rate_limit_exceeded',
                'message' => 'Muitas solicitações. Aguarde antes de tentar novamente.',
            ], 429)->withHeader('Retry-After', (string) max($ipLimit['retry_after'], $emailLimit['retry_after']));
        }

        $generic = ['message' => 'Se houver uma conta ativa para este e-mail, enviaremos um link de recuperação.'];
        $stmt = $pdo->prepare('SELECT id, nome, email FROM profissionais WHERE email = :email AND status = :status AND senha_hash IS NOT NULL LIMIT 1');
        $stmt->execute(['email' => $email, 'status' => 'ATIVO']);
        $professional = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$professional) {
            return $this->json($response, $generic);
        }

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $pdo->beginTransaction();
        try {
            // Invalidar solicitações anteriores. Novo token só será persistido
            // depois de confirmar que o serviço SMTP aceitou o envio.
            $clean = $pdo->prepare('DELETE FROM profissional_recuperacoes_senha WHERE profissional_id = :id');
            $clean->execute(['id' => (int) $professional['id']]);
            $insert = $pdo->prepare('INSERT INTO profissional_recuperacoes_senha (profissional_id, token_hash, expira_em) VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
            $insert->execute(['id' => (int) $professional['id'], 'hash' => $hash]);

            $frontend = rtrim((string) ($_ENV['FRONTEND_URL'] ?? ''), '/');
            if (!filter_var($frontend, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('FRONTEND_URL inválida.');
            }
            $link = $frontend . '/profissional/redefinir-senha?token=' . $token;
            $this->mailService->sendProfessionalPasswordReset(
                (string) $professional['email'],
                (string) $professional['nome'],
                $link
            );
            $pdo->commit();
            $this->auditService->recordSafe('SISTEMA', null, 'RECUPERACAO_SENHA_SOLICITADA', 'PROFISSIONAL', (int) $professional['id'], [], $request, (int) $professional['id'], $pdo);
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Falha na recuperação de senha profissional: ' . get_class($exception));
            return $this->json($response, ['error' => 'mail_unavailable', 'message' => 'Não foi possível processar o envio agora. Tente novamente mais tarde.'], 503);
        }

        return $this->json($response, $generic);
    }

    public function reset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $token = (string) ($body['token'] ?? '');
        $password = (string) ($body['nova_senha'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $token) || strlen($password) < 8) {
            return $this->json($response, ['error' => 'validation_error', 'message' => 'Link inválido ou senha com menos de 8 caracteres.'], 422);
        }
        $ip = $this->rateLimitService->requestKey($request);
        $limit = $this->rateLimitService->hit('pwd_reset_use_ip', $ip, 15, 900);
        if (!$limit['allowed']) {
            return $this->json($response, ['error' => 'rate_limit_exceeded', 'message' => 'Muitas tentativas. Aguarde e tente novamente.'], 429)
                ->withHeader('Retry-After', (string) $limit['retry_after']);
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT r.id, r.profissional_id, p.senha_hash
                   FROM profissional_recuperacoes_senha r
                   INNER JOIN profissionais p ON p.id = r.profissional_id
                  WHERE r.token_hash = :hash AND r.utilizado_em IS NULL
                    AND r.expira_em > NOW() AND p.status = :status
                  LIMIT 1 FOR UPDATE'
            );
            $stmt->execute(['hash' => hash('sha256', $token), 'status' => 'ATIVO']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                return $this->json($response, ['error' => 'invalid_reset_token', 'message' => 'Link inválido ou expirado. Solicite outro.'], 422);
            }
            if (!empty($row['senha_hash']) && password_verify($password, (string) $row['senha_hash'])) {
                $pdo->rollBack();
                return $this->json($response, ['error' => 'same_password', 'message' => 'Escolha uma senha diferente da anterior.'], 422);
            }
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash === false) {
                throw new \RuntimeException('Erro ao gerar hash.');
            }
            $update = $pdo->prepare('UPDATE profissionais SET senha_hash = :hash, senha_alterada_em = NOW() WHERE id = :id');
            $update->execute(['hash' => $newHash, 'id' => (int) $row['profissional_id']]);
            $invalidate = $pdo->prepare('UPDATE profissional_recuperacoes_senha SET utilizado_em = NOW() WHERE profissional_id = :id AND utilizado_em IS NULL');
            $invalidate->execute(['id' => (int) $row['profissional_id']]);
            $pdo->commit();
            $this->auditService->recordSafe('PROFISSIONAL', (int) $row['profissional_id'], 'SENHA_REDEFINIDA_EMAIL', 'PROFISSIONAL', (int) $row['profissional_id'], [], $request, (int) $row['profissional_id'], $pdo);
            return $this->json($response, ['message' => 'Senha redefinida com sucesso. Entre novamente.']);
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
    }
}
