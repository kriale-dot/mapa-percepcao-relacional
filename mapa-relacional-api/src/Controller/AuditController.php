<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuditController
{
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $professional = $request->getAttribute('auth.professional');

        if (!is_array($professional)) {
            return $this->json($response, [
                'error' => 'unauthorized',
                'message' => 'Autenticacao profissional obrigatoria.',
            ], 401);
        }

        $professionalId = (int) ($professional['id'] ?? 0);

        if ($professionalId <= 0) {
            return $this->json($response, [
                'error' => 'unauthorized',
                'message' => 'Autenticacao profissional obrigatoria.',
            ], 401);
        }

        $query = $request->getQueryParams();
        $action = trim((string) ($query['acao'] ?? ''));
        $entityType = trim((string) ($query['entidade_tipo'] ?? ''));
        $limit = (int) ($query['limite'] ?? 100);
        $limit = max(1, min(200, $limit));

        $where = ['profissional_id = :profissional_id'];
        $params = ['profissional_id' => $professionalId];

        if ($action !== '') {
            $where[] = 'acao = :acao';
            $params['acao'] = $action;
        }

        if ($entityType !== '') {
            $where[] = 'entidade_tipo = :entidade_tipo';
            $params['entidade_tipo'] = $entityType;
        }

        $pdo = Database::connect();

        $sql = 'SELECT
                    id,
                    ator_tipo,
                    ator_id,
                    acao,
                    entidade_tipo,
                    entidade_id,
                    contexto_json,
                    ip,
                    user_agent,
                    created_at
                FROM auditoria_eventos
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY created_at DESC, id DESC
                LIMIT ' . $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $events = array_map(
            static function (array $event): array {
                $event['id'] = (int) $event['id'];
                $event['ator_id'] = $event['ator_id'] === null
                    ? null
                    : (int) $event['ator_id'];
                $event['entidade_id'] = $event['entidade_id'] === null
                    ? null
                    : (int) $event['entidade_id'];
                $event['contexto'] = $event['contexto_json'] === null
                    ? null
                    : json_decode(
                        (string) $event['contexto_json'],
                        true
                    );
                unset($event['contexto_json']);

                return $event;
            },
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'eventos' => $events,
            'limite' => $limit,
        ]);
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
