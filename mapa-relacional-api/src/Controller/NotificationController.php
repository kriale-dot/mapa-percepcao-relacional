<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Service\AccessTokenService;
use App\Service\AuditService;
use App\Service\MailService;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class NotificationController
{
    public function __construct(
        private readonly AccessTokenService $tokenService,
        private readonly MailService $mailService,
        private readonly AuditService $auditService
    ) {
    }

    public function resendParticipantAccess(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $applicationId = $this->positiveId($args['id'] ?? null);
        $side = strtoupper(trim((string) ($args['lado'] ?? '')));

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if (
            $applicationId === null
            || !in_array($side, ['A', 'B'], true)
        ) {
            return $this->notFound($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                a.id AS aplicacao_id,
                a.email_contato,
                a.status AS aplicacao_status,
                i.nome AS instrumento_nome,
                ap.id AS participante_id,
                ap.nome_snapshot,
                ap.status AS participante_status,
                aa.id AS acesso_id,
                aa.token_hash,
                aa.status AS acesso_status,
                aa.revogado_em
             FROM aplicacoes a
             INNER JOIN instrumento_versoes v
               ON v.id = a.instrumento_versao_id
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             INNER JOIN aplicacao_participantes ap
               ON ap.aplicacao_id = a.id
              AND ap.lado = :lado
             INNER JOIN acessos_aplicacao aa
               ON aa.aplicacao_participante_id = ap.id
             WHERE a.id = :aplicacao_id
               AND a.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'lado' => $side,
            'aplicacao_id' => $applicationId,
            'profissional_id' => $professionalId,
        ]);

        $context = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($context === false) {
            return $this->notFound($response);
        }

        if (
            (string) $context['participante_status'] === 'CONCLUIDO'
            || (string) $context['acesso_status'] !== 'ATIVO'
            || $context['revogado_em'] !== null
            || (string) $context['aplicacao_status'] === 'CONCLUIDA'
            || (string) $context['aplicacao_status'] === 'CANCELADA'
        ) {
            return $this->json($response, [
                'error' => 'access_cannot_be_resent',
                'message' => 'Este acesso nao pode mais ser reenviado.',
            ], 409);
        }

        $oldHash = (string) $context['token_hash'];
        $tokenData = $this->tokenService->generate();
        $link = $this->buildParticipantLink($tokenData['token']);

        $update = $pdo->prepare(
            'UPDATE acessos_aplicacao
                SET token_hash = :token_hash
              WHERE id = :id
                AND status = :status
                AND revogado_em IS NULL'
        );
        $update->execute([
            'token_hash' => $tokenData['hash'],
            'id' => (int) $context['acesso_id'],
            'status' => 'ATIVO',
        ]);

        if ($update->rowCount() !== 1) {
            return $this->json($response, [
                'error' => 'access_cannot_be_resent',
                'message' => 'Este acesso nao pode mais ser reenviado.',
            ], 409);
        }

        try {
            $this->mailService->sendSingleParticipantAccessLink(
                (string) $context['email_contato'],
                (string) $context['instrumento_nome'],
                $side,
                (string) $context['nome_snapshot'],
                $link
            );
        } catch (Throwable $error) {
            $restore = $pdo->prepare(
                'UPDATE acessos_aplicacao
                    SET token_hash = :token_hash
                  WHERE id = :id'
            );
            $restore->execute([
                'token_hash' => $oldHash,
                'id' => (int) $context['acesso_id'],
            ]);

            return $this->json($response, [
                'error' => 'email_delivery_failed',
                'message' => 'Nao foi possivel reenviar o acesso por e-mail.',
            ], 502);
        }

        $sent = $pdo->prepare(
            'UPDATE acessos_aplicacao
                SET enviado_em = NOW()
              WHERE id = :id'
        );
        $sent->execute([
            'id' => (int) $context['acesso_id'],
        ]);

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'ACESSO_PARTICIPANTE_REENVIADO',
            'APLICACAO',
            $applicationId,
            [
                'lado' => $side,
                'participante_id' => (int) $context['participante_id'],
            ],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Novo link enviado para o e-mail cadastrado.',
            'lado' => $side,
        ]);
    }

    public function resendFeedback(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $professionalId = $this->professionalId($request);
        $applicationId = $this->positiveId($args['id'] ?? null);

        if ($professionalId === null) {
            return $this->unauthorized($response);
        }

        if ($applicationId === null) {
            return $this->notFound($response);
        }

        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                d.id AS devolutiva_id,
                d.token_hash,
                d.status AS devolutiva_status,
                a.email_contato,
                a.status AS aplicacao_status,
                i.nome AS instrumento_nome,
                pa.nome_snapshot AS participante_a_nome,
                pb.nome_snapshot AS participante_b_nome
             FROM devolutivas d
             INNER JOIN aplicacoes a
               ON a.id = d.aplicacao_id
             INNER JOIN instrumento_versoes v
               ON v.id = a.instrumento_versao_id
             INNER JOIN instrumentos i
               ON i.id = v.instrumento_id
             INNER JOIN aplicacao_participantes pa
               ON pa.aplicacao_id = a.id
              AND pa.lado = \'A\'
             INNER JOIN aplicacao_participantes pb
               ON pb.aplicacao_id = a.id
              AND pb.lado = \'B\'
             WHERE d.aplicacao_id = :aplicacao_id
               AND d.profissional_id = :profissional_id
             LIMIT 1'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
            'profissional_id' => $professionalId,
        ]);

        $context = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($context === false) {
            return $this->notFound($response);
        }

        if (
            (string) $context['devolutiva_status'] !== 'LIBERADA'
            || (string) $context['aplicacao_status'] !== 'CONCLUIDA'
        ) {
            return $this->json($response, [
                'error' => 'feedback_not_released',
                'message' => 'A devolutiva precisa estar liberada antes do reenvio.',
            ], 409);
        }

        $oldHash = $context['token_hash'];
        $tokenData = $this->tokenService->generate();
        $link = $this->buildFeedbackLink($tokenData['token']);

        $update = $pdo->prepare(
            'UPDATE devolutivas
                SET token_hash = :token_hash
              WHERE id = :id'
        );
        $update->execute([
            'token_hash' => $tokenData['hash'],
            'id' => (int) $context['devolutiva_id'],
        ]);

        try {
            $this->mailService->sendResultRelease(
                (string) $context['email_contato'],
                (string) $context['instrumento_nome'],
                (string) $context['participante_a_nome'],
                (string) $context['participante_b_nome'],
                $link
            );
        } catch (Throwable $error) {
            $restore = $pdo->prepare(
                'UPDATE devolutivas
                    SET token_hash = :token_hash
                  WHERE id = :id'
            );
            $restore->execute([
                'token_hash' => $oldHash,
                'id' => (int) $context['devolutiva_id'],
            ]);

            return $this->json($response, [
                'error' => 'email_delivery_failed',
                'message' => 'Nao foi possivel reenviar a devolutiva por e-mail.',
            ], 502);
        }

        $sent = $pdo->prepare(
            'UPDATE devolutivas
                SET enviado_em = NOW()
              WHERE id = :id'
        );
        $sent->execute([
            'id' => (int) $context['devolutiva_id'],
        ]);

        $this->auditService->recordSafe(
            'PROFISSIONAL',
            $professionalId,
            'DEVOLUTIVA_REENVIADA',
            'APLICACAO',
            $applicationId,
            [],
            $request,
            $professionalId,
            $pdo
        );

        return $this->json($response, [
            'message' => 'Novo link da devolutiva enviado para o e-mail cadastrado.',
        ]);
    }

    private function buildParticipantLink(string $token): string
    {
        return $this->frontendUrl() . '/avaliacao/acesso/' . $token;
    }

    private function buildFeedbackLink(string $token): string
    {
        return $this->frontendUrl() . '/resultado/' . $token;
    }

    private function frontendUrl(): string
    {
        $url = rtrim(
            trim((string) ($_ENV['FRONTEND_URL'] ?? '')),
            '/'
        );

        if (
            $url === ''
            || filter_var($url, FILTER_VALIDATE_URL) === false
        ) {
            throw new RuntimeException(
                'FRONTEND_URL invalida para gerar link.'
            );
        }

        return $url;
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

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'error' => 'not_found',
            'message' => 'Recurso nao encontrado.',
        ], 404);
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
