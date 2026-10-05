<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Database;
use PDO;
use RuntimeException;

final class ResultService
{
    public const ALGORITHM_VERSION = '2.0';

    /**
     * @return array<string,mixed>
     */
    public function calculate(
        int $applicationId,
        ?PDO $pdo = null
    ): array {
        $pdo ??= Database::connect();

        $application = $this->findApplicationContext(
            $pdo,
            $applicationId
        );

        if ($application === null) {
            throw new RuntimeException('Aplicacao nao encontrada.');
        }

        if (
            (string) $application['participante_a_status'] !== 'CONCLUIDO'
            || (string) $application['participante_b_status'] !== 'CONCLUIDO'
        ) {
            throw new RuntimeException(
                'Os dois participantes precisam concluir antes do calculo.'
            );
        }

        $items = $this->listComparableItems(
            $pdo,
            (int) $application['instrumento_versao_id'],
            $applicationId
        );

        $responses = $this->listResponsesByItem(
            $pdo,
            $applicationId
        );

        $participantAId = (int) $application['participante_a_id'];
        $participantBId = (int) $application['participante_b_id'];

        $directions = [
            'A_SOBRE_B' => [
                'perceived_respondent' => $participantAId,
                'perceived_target' => $participantBId,
                'self_respondent' => $participantBId,
                'self_target' => $participantBId,
            ],
            'B_SOBRE_A' => [
                'perceived_respondent' => $participantBId,
                'perceived_target' => $participantAId,
                'self_respondent' => $participantAId,
                'self_target' => $participantAId,
            ],
        ];

        $deleteComparisons = $pdo->prepare(
            'DELETE FROM comparacoes WHERE aplicacao_id = :aplicacao_id'
        );
        $deleteComparisons->execute([
            'aplicacao_id' => $applicationId,
        ]);

        $insertComparison = $pdo->prepare(
            'INSERT INTO comparacoes (
                aplicacao_id,
                item_id,
                sentido,
                resposta_percebida_id,
                resposta_autorreferida_id,
                comparavel,
                coincide,
                motivo_nao_comparavel
             ) VALUES (
                :aplicacao_id,
                :item_id,
                :sentido,
                :resposta_percebida_id,
                :resposta_autorreferida_id,
                :comparavel,
                :coincide,
                :motivo
             )'
        );

        $summary = [
            'A_SOBRE_B' => [
                'comparacoes_validas' => 0,
                'coincidencias' => 0,
            ],
            'B_SOBRE_A' => [
                'comparacoes_validas' => 0,
                'coincidencias' => 0,
            ],
        ];

        $comparisonsByItem = [];

        foreach ($items as $item) {
            $itemId = (int) $item['id'];

            foreach ($directions as $direction => $pair) {
                $perceived = $this->findResponse(
                    $responses,
                    $itemId,
                    $pair['perceived_respondent'],
                    $pair['perceived_target']
                );
                $self = $this->findResponse(
                    $responses,
                    $itemId,
                    $pair['self_respondent'],
                    $pair['self_target']
                );

                $comparison = $this->compareResponses(
                    $perceived,
                    $self
                );

                $comparisonsByItem[$itemId][$direction] = $comparison;

                $insertComparison->execute([
                    'aplicacao_id' => $applicationId,
                    'item_id' => $itemId,
                    'sentido' => $direction,
                    'resposta_percebida_id' =>
                        $perceived === null
                            ? null
                            : (int) $perceived['id'],
                    'resposta_autorreferida_id' =>
                        $self === null
                            ? null
                            : (int) $self['id'],
                    'comparavel' => $comparison['comparavel'] ? 1 : 0,
                    'coincide' => $comparison['coincide'] === null
                        ? null
                        : ($comparison['coincide'] ? 1 : 0),
                    'motivo' => $comparison['motivo'],
                ]);

                if ($comparison['comparavel']) {
                    $summary[$direction]['comparacoes_validas']++;

                    if ($comparison['coincide']) {
                        $summary[$direction]['coincidencias']++;
                    }
                }
            }
        }

        $general = [
            'itens_validos' => 0,
            'acertos_gerais' => 0,
        ];

        foreach ($items as $item) {
            $itemId = (int) $item['id'];
            $comparisonA = $comparisonsByItem[$itemId]['A_SOBRE_B'] ?? null;
            $comparisonB = $comparisonsByItem[$itemId]['B_SOBRE_A'] ?? null;

            if (
                $comparisonA === null
                || $comparisonB === null
                || !$comparisonA['comparavel']
                || !$comparisonB['comparavel']
            ) {
                continue;
            }

            $general['itens_validos']++;

            if (
                $comparisonA['coincide'] === true
                && $comparisonB['coincide'] === true
            ) {
                $general['acertos_gerais']++;
            }
        }

        $generalPercentage = $general['itens_validos'] > 0
            ? round(
                (
                    $general['acertos_gerais']
                    / $general['itens_validos']
                ) * 100,
                2
            )
            : null;

        $generalBand = $generalPercentage === null
            ? null
            : $this->findBand(
                $pdo,
                (int) $application['instrumento_versao_id'],
                $generalPercentage
            );

        $general['percentual'] = $generalPercentage;
        $general['faixa'] = $generalBand['rotulo'] ?? null;
        $general['faixa_id'] = isset($generalBand['id'])
            ? (int) $generalBand['id']
            : null;

        $upsertGeneral = $pdo->prepare(
            'INSERT INTO resultados_gerais (
                aplicacao_id,
                itens_validos,
                acertos_gerais,
                percentual,
                faixa_id,
                faixa,
                algoritmo_versao,
                calculado_em
             ) VALUES (
                :aplicacao_id,
                :itens_validos,
                :acertos_gerais,
                :percentual,
                :faixa_id,
                :faixa,
                :algoritmo_versao,
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                itens_validos = :u_itens_validos,
                acertos_gerais = :u_acertos_gerais,
                percentual = :u_percentual,
                faixa_id = :u_faixa_id,
                faixa = :u_faixa,
                algoritmo_versao = :u_algoritmo_versao,
                calculado_em = NOW()'
        );
        $upsertGeneral->execute([
            'aplicacao_id' => $applicationId,
            'itens_validos' => $general['itens_validos'],
            'acertos_gerais' => $general['acertos_gerais'],
            'percentual' => $generalPercentage,
            'faixa_id' => $general['faixa_id'],
            'faixa' => $general['faixa'],
            'algoritmo_versao' => self::ALGORITHM_VERSION,
            'u_itens_validos' => $general['itens_validos'],
            'u_acertos_gerais' => $general['acertos_gerais'],
            'u_percentual' => $generalPercentage,
            'u_faixa_id' => $general['faixa_id'],
            'u_faixa' => $general['faixa'],
            'u_algoritmo_versao' => self::ALGORITHM_VERSION,
        ]);

        foreach ($summary as $direction => &$result) {
            $valid = $result['comparacoes_validas'];
            $hits = $result['coincidencias'];
            $percentage = $valid > 0
                ? round(($hits / $valid) * 100, 2)
                : null;
            $band = $percentage === null
                ? null
                : $this->findBand(
                    $pdo,
                    (int) $application['instrumento_versao_id'],
                    $percentage
                );

            $result['percentual'] = $percentage;
            $result['faixa'] = $band['rotulo'] ?? null;
            $result['faixa_id'] = isset($band['id'])
                ? (int) $band['id']
                : null;

            $upsertResult = $pdo->prepare(
                'INSERT INTO resultados (
                    aplicacao_id,
                    sentido,
                    comparacoes_validas,
                    coincidencias,
                    percentual,
                    faixa_id,
                    faixa,
                    algoritmo_versao,
                    calculado_em
                 ) VALUES (
                    :aplicacao_id,
                    :sentido,
                    :comparacoes_validas,
                    :coincidencias,
                    :percentual,
                    :faixa_id,
                    :faixa,
                    :algoritmo_versao,
                    NOW()
                 )
                 ON DUPLICATE KEY UPDATE
                    comparacoes_validas = :u_comparacoes_validas,
                    coincidencias = :u_coincidencias,
                    percentual = :u_percentual,
                    faixa_id = :u_faixa_id,
                    faixa = :u_faixa,
                    algoritmo_versao = :u_algoritmo_versao,
                    calculado_em = NOW()'
            );
            $upsertResult->execute([
                'aplicacao_id' => $applicationId,
                'sentido' => $direction,
                'comparacoes_validas' => $valid,
                'coincidencias' => $hits,
                'percentual' => $percentage,
                'faixa_id' => $result['faixa_id'],
                'faixa' => $result['faixa'],
                'algoritmo_versao' => self::ALGORITHM_VERSION,
                'u_comparacoes_validas' => $valid,
                'u_coincidencias' => $hits,
                'u_percentual' => $percentage,
                'u_faixa_id' => $result['faixa_id'],
                'u_faixa' => $result['faixa'],
                'u_algoritmo_versao' => self::ALGORITHM_VERSION,
            ]);
        }
        unset($result);

        return [
            'aplicacao_id' => $applicationId,
            'algoritmo_versao' => self::ALGORITHM_VERSION,
            'resultados' => $summary,
            'resultado_geral' => $general,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getResults(
        int $applicationId,
        ?PDO $pdo = null
    ): array {
        $pdo ??= Database::connect();

        $application = $this->findApplicationContext(
            $pdo,
            $applicationId
        );

        if ($application === null) {
            throw new RuntimeException('Aplicacao nao encontrada.');
        }

        $resultsStmt = $pdo->prepare(
            'SELECT
                r.id,
                r.sentido,
                r.comparacoes_validas,
                r.coincidencias,
                r.percentual,
                r.faixa,
                r.algoritmo_versao,
                r.calculado_em
             FROM resultados r
             WHERE r.aplicacao_id = :aplicacao_id
             ORDER BY FIELD(r.sentido, \'A_SOBRE_B\', \'B_SOBRE_A\')'
        );
        $resultsStmt->execute(['aplicacao_id' => $applicationId]);

        $generalStmt = $pdo->prepare(
            'SELECT
                id,
                itens_validos,
                acertos_gerais,
                percentual,
                faixa,
                algoritmo_versao,
                calculado_em
             FROM resultados_gerais
             WHERE aplicacao_id = :aplicacao_id
             LIMIT 1'
        );
        $generalStmt->execute(['aplicacao_id' => $applicationId]);

        $generalResult = $generalStmt->fetch(PDO::FETCH_ASSOC);

        if ($generalResult !== false) {
            $generalResult['id'] = (int) $generalResult['id'];
            $generalResult['itens_validos'] =
                (int) $generalResult['itens_validos'];
            $generalResult['acertos_gerais'] =
                (int) $generalResult['acertos_gerais'];
            $generalResult['percentual'] =
                $generalResult['percentual'] === null
                    ? null
                    : (float) $generalResult['percentual'];
        } else {
            $generalResult = null;
        }

        $comparisonStmt = $pdo->prepare(
            'SELECT
                c.id,
                c.item_id,
                c.sentido,
                c.comparavel,
                c.coincide,
                c.motivo_nao_comparavel,
                s.id AS secao_id,
                s.titulo AS secao_titulo,
                i.codigo AS item_codigo,
                i.texto AS item_texto,
                rp.alternativa_id AS percebida_alternativa_id,
                ap.rotulo AS percebida_rotulo,
                rp.valor_texto AS percebida_texto,
                rp.valor_numero AS percebida_numero,
                ra.alternativa_id AS autorreferida_alternativa_id,
                aa.rotulo AS autorreferida_rotulo,
                ra.valor_texto AS autorreferida_texto,
                ra.valor_numero AS autorreferida_numero
             FROM comparacoes c
             INNER JOIN itens i
               ON i.id = c.item_id
             INNER JOIN secoes s
               ON s.id = i.secao_id
             LEFT JOIN respostas rp
               ON rp.id = c.resposta_percebida_id
             LEFT JOIN alternativas ap
               ON ap.id = rp.alternativa_id
             LEFT JOIN respostas ra
               ON ra.id = c.resposta_autorreferida_id
             LEFT JOIN alternativas aa
               ON aa.id = ra.alternativa_id
             WHERE c.aplicacao_id = :aplicacao_id
             ORDER BY
                s.ordem ASC,
                s.id ASC,
                i.ordem ASC,
                i.id ASC,
                FIELD(c.sentido, \'A_SOBRE_B\', \'B_SOBRE_A\')'
        );
        $comparisonStmt->execute([
            'aplicacao_id' => $applicationId,
        ]);

        $comparisons = array_map(
            [$this, 'normalizeComparison'],
            $comparisonStmt->fetchAll(PDO::FETCH_ASSOC)
        );

        $excludedStmt = $pdo->prepare(
            'SELECT
                ex.item_id,
                ex.motivo,
                ex.created_at,
                i.codigo AS item_codigo,
                i.texto AS item_texto,
                s.id AS secao_id,
                s.titulo AS secao_titulo,
                ap.lado AS marcado_por_lado,
                ap.nome_snapshot AS marcado_por_nome
             FROM aplicacao_itens_excluidos ex
             INNER JOIN itens i
               ON i.id = ex.item_id
             INNER JOIN secoes s
               ON s.id = i.secao_id
             INNER JOIN aplicacao_participantes ap
               ON ap.id = ex.marcado_por_participante_id
             WHERE ex.aplicacao_id = :aplicacao_id
             ORDER BY s.ordem ASC, s.id ASC, i.ordem ASC, i.id ASC'
        );
        $excludedStmt->execute([
            'aplicacao_id' => $applicationId,
        ]);

        $excludedItems = array_map(
            static function (array $item): array {
                $item['item_id'] = (int) $item['item_id'];
                $item['secao_id'] = (int) $item['secao_id'];

                return $item;
            },
            $excludedStmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return [
            'aplicacao' => [
                'id' => (int) $application['id'],
                'status' => (string) $application['status'],
                'instrumento_versao_id' =>
                    (int) $application['instrumento_versao_id'],
                'instrumento_id' => (int) $application['instrumento_id'],
                'instrumento_nome' => (string) $application['instrumento_nome'],
                'numero_versao' => (string) $application['numero_versao'],
                'participante_a' => [
                    'id' => (int) $application['participante_a_id'],
                    'nome' => $application['participante_a_nome'],
                ],
                'participante_b' => [
                    'id' => (int) $application['participante_b_id'],
                    'nome' => $application['participante_b_nome'],
                ],
            ],
            'resultados' => array_map(
                static function (array $result): array {
                    $result['id'] = (int) $result['id'];
                    $result['comparacoes_validas'] =
                        (int) $result['comparacoes_validas'];
                    $result['coincidencias'] =
                        (int) $result['coincidencias'];
                    $result['percentual'] = $result['percentual'] === null
                        ? null
                        : (float) $result['percentual'];

                    return $result;
                },
                $resultsStmt->fetchAll(PDO::FETCH_ASSOC)
            ),
            'resultado_geral' => $generalResult,
            'comparacoes' => $comparisons,
            'itens_excluidos' => $excludedItems,
            'secoes' => $this->buildSectionResults(
                $pdo,
                (int) $application['instrumento_versao_id'],
                $comparisons
            ),
        ];
    }

    private function findApplicationContext(
        PDO $pdo,
        int $applicationId
    ): ?array {
        $stmt = $pdo->prepare(
            'SELECT
                a.id,
                a.status,
                a.instrumento_versao_id,
                i.id AS instrumento_id,
                i.nome AS instrumento_nome,
                v.numero_versao,
                pa.id AS participante_a_id,
                pa.nome_snapshot AS participante_a_nome,
                pa.status AS participante_a_status,
                pb.id AS participante_b_id,
                pb.nome_snapshot AS participante_b_nome,
                pb.status AS participante_b_status
             FROM aplicacoes a
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
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $applicationId]);

        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        return $application === false ? null : $application;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function listComparableItems(
        PDO $pdo,
        int $versionId,
        int $applicationId
    ): array {
        $stmt = $pdo->prepare(
            'SELECT i.id
             FROM itens i
             INNER JOIN secoes s
               ON s.id = i.secao_id
             WHERE s.instrumento_versao_id = :versao_id
               AND s.ativo = 1
               AND i.ativo = 1
               AND NOT EXISTS (
                    SELECT 1
                    FROM aplicacao_itens_excluidos ex
                    WHERE ex.aplicacao_id = :aplicacao_id
                      AND ex.item_id = i.id
               )
             ORDER BY s.ordem, s.id, i.ordem, i.id'
        );
        $stmt->execute([
            'versao_id' => $versionId,
            'aplicacao_id' => $applicationId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function listResponsesByItem(
        PDO $pdo,
        int $applicationId
    ): array {
        $stmt = $pdo->prepare(
            'SELECT
                id,
                respondente_id,
                alvo_id,
                item_id,
                alternativa_id,
                valor_texto,
                valor_numero,
                nao_se_aplica
             FROM respostas
             WHERE aplicacao_id = :aplicacao_id'
        );
        $stmt->execute(['aplicacao_id' => $applicationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int,array<string,mixed>> $responses
     */
    private function findResponse(
        array $responses,
        int $itemId,
        int $respondentId,
        int $targetId
    ): ?array {
        foreach ($responses as $response) {
            if (
                (int) $response['item_id'] === $itemId
                && (int) $response['respondente_id'] === $respondentId
                && (int) $response['alvo_id'] === $targetId
                && !(bool) $response['nao_se_aplica']
            ) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @return array{comparavel:bool,coincide:?bool,motivo:?string}
     */
    private function compareResponses(
        ?array $perceived,
        ?array $self
    ): array {
        if ($perceived === null || $self === null) {
            return [
                'comparavel' => false,
                'coincide' => null,
                'motivo' => 'RESPOSTA_AUSENTE',
            ];
        }

        if (
            $perceived['alternativa_id'] !== null
            && $self['alternativa_id'] !== null
        ) {
            return [
                'comparavel' => true,
                'coincide' =>
                    (int) $perceived['alternativa_id']
                    === (int) $self['alternativa_id'],
                'motivo' => null,
            ];
        }

        if (
            $perceived['valor_numero'] !== null
            && $self['valor_numero'] !== null
        ) {
            return [
                'comparavel' => true,
                'coincide' =>
                    (float) $perceived['valor_numero']
                    === (float) $self['valor_numero'],
                'motivo' => null,
            ];
        }

        if (
            $perceived['valor_texto'] !== null
            && $self['valor_texto'] !== null
        ) {
            return [
                'comparavel' => true,
                'coincide' =>
                    trim((string) $perceived['valor_texto'])
                    === trim((string) $self['valor_texto']),
                'motivo' => null,
            ];
        }

        return [
            'comparavel' => false,
            'coincide' => null,
            'motivo' => 'FORMATO_INCOMPATIVEL',
        ];
    }

    private function findBand(
        PDO $pdo,
        int $versionId,
        float $percentage
    ): ?array {
        $stmt = $pdo->prepare(
            'SELECT id, codigo, rotulo, minimo, maximo
             FROM resultado_faixas
             WHERE instrumento_versao_id = :versao_id
               AND :percentual BETWEEN minimo AND maximo
             ORDER BY ordem ASC, id ASC
             LIMIT 1'
        );
        $stmt->execute([
            'versao_id' => $versionId,
            'percentual' => $percentage,
        ]);

        $band = $stmt->fetch(PDO::FETCH_ASSOC);

        return $band === false ? null : $band;
    }

    private function normalizeComparison(array $comparison): array
    {
        foreach (['id', 'item_id', 'secao_id'] as $key) {
            $comparison[$key] = (int) $comparison[$key];
        }

        $comparison['comparavel'] = (bool) $comparison['comparavel'];
        $comparison['coincide'] = $comparison['coincide'] === null
            ? null
            : (bool) $comparison['coincide'];

        foreach ([
            'percebida_alternativa_id',
            'autorreferida_alternativa_id',
        ] as $key) {
            $comparison[$key] = $comparison[$key] === null
                ? null
                : (int) $comparison[$key];
        }

        foreach ([
            'percebida_numero',
            'autorreferida_numero',
        ] as $key) {
            $comparison[$key] = $comparison[$key] === null
                ? null
                : (float) $comparison[$key];
        }

        $comparison['resposta_percebida'] =
            $this->displayResponse(
                $comparison['percebida_rotulo'],
                $comparison['percebida_texto'],
                $comparison['percebida_numero']
            );
        $comparison['resposta_autorreferida'] =
            $this->displayResponse(
                $comparison['autorreferida_rotulo'],
                $comparison['autorreferida_texto'],
                $comparison['autorreferida_numero']
            );

        foreach ([
            'percebida_alternativa_id',
            'percebida_rotulo',
            'percebida_texto',
            'percebida_numero',
            'autorreferida_alternativa_id',
            'autorreferida_rotulo',
            'autorreferida_texto',
            'autorreferida_numero',
        ] as $key) {
            unset($comparison[$key]);
        }

        return $comparison;
    }

    private function displayResponse(
        mixed $label,
        mixed $text,
        mixed $number
    ): ?string {
        if ($label !== null) {
            return (string) $label;
        }

        if ($number !== null) {
            return (string) $number;
        }

        if ($text !== null) {
            return (string) $text;
        }

        return null;
    }

    /**
     * @param array<int,array<string,mixed>> $comparisons
     * @return array<int,array<string,mixed>>
     */
    private function buildSectionResults(
        PDO $pdo,
        int $versionId,
        array $comparisons
    ): array {
        $sections = [];

        foreach ($comparisons as $comparison) {
            $sectionId = (int) $comparison['secao_id'];

            if (!isset($sections[$sectionId])) {
                $sections[$sectionId] = [
                    'id' => $sectionId,
                    'titulo' => (string) $comparison['secao_titulo'],
                    'A_SOBRE_B' => [
                        'comparacoes_validas' => 0,
                        'coincidencias' => 0,
                    ],
                    'B_SOBRE_A' => [
                        'comparacoes_validas' => 0,
                        'coincidencias' => 0,
                    ],
                ];
            }

            $direction = (string) $comparison['sentido'];

            if ($comparison['comparavel']) {
                $sections[$sectionId][$direction]['comparacoes_validas']++;

                if ($comparison['coincide']) {
                    $sections[$sectionId][$direction]['coincidencias']++;
                }
            }
        }

        foreach ($sections as &$section) {
            foreach (['A_SOBRE_B', 'B_SOBRE_A'] as $direction) {
                $valid = $section[$direction]['comparacoes_validas'];
                $hits = $section[$direction]['coincidencias'];
                $percentage = $valid > 0
                    ? round(($hits / $valid) * 100, 2)
                    : null;
                $band = $percentage === null
                    ? null
                    : $this->findBand(
                        $pdo,
                        $versionId,
                        $percentage
                    );

                $section[$direction]['percentual'] = $percentage;
                $section[$direction]['faixa'] =
                    $band['rotulo'] ?? null;
            }
        }
        unset($section);

        return array_values($sections);
    }
}
