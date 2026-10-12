<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SiteAnalyticsController
{
    private function professionalId(ServerRequestInterface $request): ?int
    {
        $auth = $request->getAttribute('auth.professional');
        return is_array($auth) && isset($auth['id']) ? (int) $auth['id'] : null;
    }

    private function json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function activeProfessional(PDO $pdo): ?int
    {
        $id = $pdo->query("SELECT id FROM profissionais WHERE status = 'ATIVO' ORDER BY id ASC LIMIT 1")->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function settings(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare('SELECT registrar_visitas, mostrar_contador FROM site_analytics_config WHERE profissional_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'registrar_visitas' => (bool) ($row['registrar_visitas'] ?? true),
            'mostrar_contador' => (bool) ($row['mostrar_contador'] ?? false),
        ];
    }

    private function approximateLocation(ServerRequestInterface $request): array
    {
        $unknown = ['pais' => null, 'regiao' => null, 'cidade' => null];
        $database = (string) ($_ENV['SITE_GEOIP_DB'] ?? '');
        if ($database === '' || !is_readable($database) || !class_exists(\\GeoIp2\\Database\\Reader::class)) {
            return $unknown;
        }
        // REMOTE_ADDR e o unico endereco considerado: nao confiar em headers enviados pelo cliente.
        $server = $request->getServerParams();
        $ip = (string) ($server['REMOTE_ADDR'] ?? '');
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $unknown;
        }
        try {
            $reader = new \\GeoIp2\\Database\\Reader($database);
            try {
                $city = $reader->city($ip);
                return [
                    'pais' => mb_substr((string) ($city->country->name ?? ''), 0, 100) ?: null,
                    'regiao' => mb_substr((string) ($city->mostSpecificSubdivision->name ?? ''), 0, 120) ?: null,
                    'cidade' => mb_substr((string) ($city->city->name ?? ''), 0, 120) ?: null,
                ];
            } finally {
                $reader->close();
            }
        } catch (\\Throwable) {
            return $unknown;
        }
    }

    public function track(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $token = (string) ($body['sessao'] ?? '');
        $page = (string) ($body['pagina'] ?? '');
        // Nunca registrar URLs com identificadores de pacientes, tokens ou consultas.
        if (!preg_match('/^[a-f0-9]{32}$/D', $token) || !in_array($page, ['/', '/biblioteca'], true)) {
            return $this->json($response, ['message' => 'Dados inválidos.'], 422);
        }
        $pdo = Database::connect();
        $id = $this->activeProfessional($pdo);
        if ($id === null) {
            return $this->json($response, ['message' => 'Site indisponível.'], 404);
        }
        $config = $this->settings($pdo, $id);
        if (!$config['registrar_visitas']) {
            return $this->json($response, ['registrada' => false]);
        }

        $origin = (string) ($body['origem'] ?? '');
        $host = parse_url($origin, PHP_URL_HOST);
        $host = is_string($host) ? mb_substr(strtolower($host), 0, 190) : null;
        $device = (string) ($body['dispositivo'] ?? 'outro');
        if (!in_array($device, ['celular', 'tablet', 'desktop', 'outro'], true)) $device = 'outro';

        $location = $this->approximateLocation($request);
        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO site_analytics_visitas
            (profissional_id, sessao_hash, pagina, origem_dominio, dispositivo, pais, regiao, cidade)
            VALUES (:id, :hash, :pagina, :origem, :dispositivo, :pais, :regiao, :cidade)'
        );
        $stmt->execute(['id' => $id, 'hash' => $hash, 'pagina' => $page, 'origem' => $host, 'dispositivo' => $device, ...$location]);
        $new = $stmt->rowCount() === 1;
        if ($new) {
            $visitId = (int) $pdo->lastInsertId();
            $pageStmt = $pdo->prepare('INSERT INTO site_analytics_paginas (visita_id, pagina) VALUES (?, ?)');
            $pageStmt->execute([$visitId, $page]);
        }
        return $this->json($response, ['registrada' => $new]);
    }

    public function publicCounter(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = Database::connect();
        $id = $this->activeProfessional($pdo);
        if ($id === null) return $this->json($response, ['mostrar' => false]);
        $settings = $this->settings($pdo, $id);
        if (!$settings['mostrar_contador']) return $this->json($response, ['mostrar' => false]);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM site_analytics_visitas WHERE profissional_id = ?');
        $stmt->execute([$id]);
        return $this->json($response, ['mostrar' => true, 'total' => (int) $stmt->fetchColumn()]);
    }

    public function showSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $this->professionalId($request);
        if (!$id) return $this->json($response, ['message' => 'Não autorizado.'], 401);
        return $this->json($response, $this->settings(Database::connect(), $id));
    }

    public function saveSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $this->professionalId($request);
        if (!$id) return $this->json($response, ['message' => 'Não autorizado.'], 401);
        $body = $request->getParsedBody();
        if (!is_array($body) || !isset($body['registrar_visitas'], $body['mostrar_contador'])
            || !is_bool($body['registrar_visitas']) || !is_bool($body['mostrar_contador'])) {
            return $this->json($response, ['message' => 'Configurações inválidas.'], 422);
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO site_analytics_config (profissional_id, registrar_visitas, mostrar_contador)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
             registrar_visitas = VALUES(registrar_visitas), mostrar_contador = VALUES(mostrar_contador)'
        );
        $stmt->execute([$id, (int) $body['registrar_visitas'], (int) $body['mostrar_contador']]);
        return $this->json($response, $this->settings($pdo, $id));
    }

    public function report(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $this->professionalId($request);
        if (!$id) return $this->json($response, ['message' => 'Não autorizado.'], 401);
        $days = (int) ($request->getQueryParams()['dias'] ?? 30);
        if (!in_array($days, [7, 30, 90, 365], true)) {
            return $this->json($response, ['message' => 'Período inválido.'], 422);
        }
        $pdo = Database::connect();
        $cutoff = (new \DateTimeImmutable('today', new \DateTimeZone('UTC')))
            ->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM site_analytics_visitas WHERE profissional_id = ?');
        $stmt->execute([$id]);
        $total = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM site_analytics_visitas WHERE profissional_id = ? AND iniciada_em >= ?');
        $stmt->execute([$id, $cutoff]);
        $periodTotal = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT DATE(iniciada_em) dia, COUNT(*) total FROM site_analytics_visitas WHERE profissional_id = ? AND iniciada_em >= ? GROUP BY DATE(iniciada_em) ORDER BY dia');
        $stmt->execute([$id, $cutoff]);
        $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT dispositivo, COUNT(*) total FROM site_analytics_visitas WHERE profissional_id = ? AND iniciada_em >= ? GROUP BY dispositivo ORDER BY total DESC');
        $stmt->execute([$id, $cutoff]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT COALESCE(origem_dominio, \'Acesso direto\') origem, COUNT(*) total FROM site_analytics_visitas WHERE profissional_id = ? AND iniciada_em >= ? GROUP BY origem ORDER BY total DESC LIMIT 10');
        $stmt->execute([$id, $cutoff]);
        $origins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT p.pagina, COUNT(*) total FROM site_analytics_paginas p INNER JOIN site_analytics_visitas v ON v.id = p.visita_id WHERE v.profissional_id = ? AND p.visualizada_em >= ? GROUP BY p.pagina ORDER BY total DESC LIMIT 10');
        $stmt->execute([$id, $cutoff]);
        $locations = [];
        foreach (['pais', 'regiao', 'cidade'] as $dimension) {
            $sql = "SELECT COALESCE(NULLIF($dimension, ''), 'Não identificado') local, COUNT(*) total
                    FROM site_analytics_visitas
                    WHERE profissional_id = ? AND iniciada_em >= ?
                    GROUP BY local ORDER BY total DESC LIMIT 15";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $cutoff]);
            $locations[$dimension] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->json($response, [
            'localizacoes' => $locations,
            'dias' => $days, 'total' => $total, 'periodo' => $periodTotal,
            'evolucao' => $daily, 'dispositivos' => $devices,
            'origens' => $origins, 'paginas' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }
}
