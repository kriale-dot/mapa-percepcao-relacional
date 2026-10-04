<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ResultBandController
{
    public function index(
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
            return $this->notFound($response);
        }

        $version = $this->findVersion(
            $professionalId,
            $instrumentId,
            $versionId
        );

        if ($version === null) {
            return $this->notFound($response);
        }

        return $this->json($response, [
            'versao' => $version,
            'editavel' => $version['status'] === 'RASCUNHO',
            'faixas' => $this->listBands($versionId),
        ]);
    }

    public function replace(
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
            return $this->notFound($response);
        }

        $version = $this->findVersion(
            $professionalId,
            $instrumentId,
            $versionId
        );

        if ($version === null) {
            return $this->notFound($response);
        }

        if ($version['status'] !== 'RASCUNHO') {
            return $this->json($response, [
                'error' => 'immutable_version',
                'message' => 'As faixas so podem ser alteradas enquanto a versao estiver em rascunho.',
            ], 409);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $bands = $data['faixas'] ?? null;

        if (!is_array($bands) || count($bands) < 1 || count($bands) > 10) {
            return $this->validation(
                $response,
                'Informe entre 1 e 10 faixas de resultado.'
            );
        }

        $normalized = [];
        $codes = [];

        foreach (array_values($bands) as $index => $band) {
            if (!is_array($band)) {
                return $this->validation(
                    $response,
                    'Formato de faixa invalido.'
                );
            }

            $label = trim((string) ($band['rotulo'] ?? ''));
            $code = strtoupper(trim((string) ($band['codigo'] ?? '')));
            $min = $this->percentage($band['minimo'] ?? null);
            $max = $this->percentage($band['maximo'] ?? null);

            if ($label === '' || strlen($label) > 60) {
                return $this->validation(
                    $response,
                    'Cada faixa precisa de um rotulo com no maximo 60 caracteres.'
                );
            }

            if ($code === '') {
                $code = sprintf('FAIXA_%02d', $index + 1);
            }

            if (
                strlen($code) > 30
                || preg_match('/^[A-Z0-9_]+$/', $code) !== 1
            ) {
                return $this->validation(
                    $response,
                    'Codigo de faixa invalido.'
                );
            }

            if (isset($codes[$code])) {
                return $this->validation(
                    $response,
                    'Os codigos das faixas devem ser unicos.'
                );
            }

            if ($min === null || $max === null || $min > $max) {
                return $this->validation(
                    $response,
                    'Cada faixa precisa de limites validos entre 0 e 100.'
                );
            }

            $codes[$code] = true;
            $normalized[] = [
                'codigo' => $code,
                'rotulo' => $label,
                'minimo' => $min,
                'maximo' => $max,
                'ordem' => $index + 1,
            ];
        }

        usort(
            $normalized,
            static fn (array $a, array $b): int =>
                $a['minimo'] <=> $b['minimo']
        );

        foreach ($normalized as $index => &$band) {
            $band['ordem'] = $index + 1;
        }
        unset($band);

        if (abs($normalized[0]['minimo'] - 0.00) > 0.001) {
            return $this->validation(
                $response,
                'A primeira faixa deve comecar em 0,00.'
            );
        }

        $last = $normalized[count($normalized) - 1];

        if (abs($last['maximo'] - 100.00) > 0.001) {
            return $this->validation(
                $response,
                'A ultima faixa deve terminar em 100,00.'
            );
        }

        for ($i = 1; $i < count($normalized); $i++) {
            $expected = round($normalized[$i - 1]['maximo'] + 0.01, 2);

            if (abs($normalized[$i]['minimo'] - $expected) > 0.001) {
                return $this->validation(
                    $response,
                    'As faixas devem cobrir de 0 a 100 sem lacunas nem sobreposicoes.'
                );
            }
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $delete = $pdo->prepare(
                'DELETE FROM resultado_faixas
                 WHERE instrumento_versao_id = :versao_id'
            );
            $delete->execute(['versao_id' => $versionId]);

            $insert = $pdo->prepare(
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

            foreach ($normalized as $band) {
                $insert->execute([
                    'versao_id' => $versionId,
                    'codigo' => $band['codigo'],
                    'rotulo' => $band['rotulo'],
                    'minimo' => $band['minimo'],
                    'maximo' => $band['maximo'],
                    'ordem' => $band['ordem'],
                ]);
            }

            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }

        return $this->json($response, [
            'message' => 'Faixas de resultado atualizadas com sucesso.',
            'versao' => $version,
            'editavel' => true,
            'faixas' => $this->listBands($versionId),
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function listBands(int $versionId): array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'SELECT
                id,
                codigo,
                rotulo,
                minimo,
                maximo,
                ordem
             FROM resultado_faixas
             WHERE instrumento_versao_id = :versao_id
             ORDER BY ordem ASC, minimo ASC, id ASC'
        );
        $stmt->execute(['versao_id' => $versionId]);

        return array_map(
            static function (array $band): array {
                $band['id'] = (int) $band['id'];
                $band['minimo'] = (float) $band['minimo'];
                $band['maximo'] = (float) $band['maximo'];
                $band['ordem'] = (int) $band['ordem'];

                return $band;
            },
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
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
                i.nome AS instrumento_nome
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
            return null;
        }

        $version['id'] = (int) $version['id'];
        $version['instrumento_id'] = (int) $version['instrumento_id'];

        return $version;
    }

    private function percentage(mixed $value): ?float
    {
        if (
            !is_scalar($value)
            || $value === ''
            || !is_numeric((string) $value)
        ) {
            return null;
        }

        $number = round((float) $value, 2);

        if ($number < 0 || $number > 100) {
            return null;
        }

        return $number;
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
            'message' => 'Versao do instrumento nao encontrada.',
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
