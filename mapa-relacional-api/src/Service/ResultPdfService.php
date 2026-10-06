<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;
use RuntimeException;

final class ResultPdfService
{
    public function __construct(
        private readonly ResultService $resultService
    ) {
    }

    /**
     * @return array{filename:string,content:string}
     */
    public function generateAutomaticResultPdf(
        int $applicationId,
        string $resultTitle,
        string $resultText,
        ?PDO $pdo = null
    ): array {
        $pdo ??= Database::connect();

        $result = $this->resultService->getResults(
            $applicationId,
            $pdo
        );

        $professional = $this->findProfessional(
            $pdo,
            $applicationId
        );

        if ($professional === null) {
            throw new RuntimeException(
                'Profissional responsavel nao encontrado para gerar o PDF.'
            );
        }

        $application = $result['aplicacao'] ?? null;

        if (!is_array($application)) {
            throw new RuntimeException(
                'Dados da aplicacao indisponiveis para gerar o PDF.'
            );
        }

        $general = $result['resultado_geral'] ?? null;

        if (!is_array($general)) {
            throw new RuntimeException(
                'Score geral indisponivel para gerar o PDF.'
            );
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml(
            $this->buildHtml(
                $result,
                $professional,
                $resultTitle,
                $resultText
            ),
            'UTF-8'
        );
        $dompdf->render();

        $content = $dompdf->output();

        if ($content === '') {
            throw new RuntimeException(
                'Falha ao gerar o documento PDF do resultado.'
            );
        }

        return [
            'filename' => 'resultado-avaliacao-conjugal.pdf',
            'content' => $content,
        ];
    }

    /**
     * @param array<string,mixed> $result
     * @param array<string,mixed> $professional
     */
    private function buildHtml(
        array $result,
        array $professional,
        string $resultTitle,
        string $resultText
    ): string {
        $application = $result['aplicacao'];
        $participantA = (string) (
            $application['participante_a']['nome'] ?? 'Participante A'
        );
        $participantB = (string) (
            $application['participante_b']['nome'] ?? 'Participante B'
        );
        $evaluationName = (string) (
            $application['instrumento_nome']
            ?? 'Avaliação de Percepção Relacional'
        );
        $version = (string) ($application['numero_versao'] ?? '');

        $resultsByDirection = [];

        foreach (($result['resultados'] ?? []) as $directional) {
            if (!is_array($directional)) {
                continue;
            }

            $direction = (string) ($directional['sentido'] ?? '');

            if ($direction !== '') {
                $resultsByDirection[$direction] = $directional;
            }
        }

        $general = is_array($result['resultado_geral'] ?? null)
            ? $result['resultado_geral']
            : [];

        $platformUrl = rtrim(
            trim((string) ($_ENV['FRONTEND_URL'] ?? '')),
            '/'
        );

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">';
        $html .= '<style>' . $this->css() . '</style></head><body>';

        $html .= '<footer class="page-footer">';
        $html .= '<strong>Profissional responsável:</strong> '
            . $this->e((string) ($professional['nome'] ?? ''));
        if ($platformUrl !== '') {
            $html .= ' &nbsp;|&nbsp; <a href="'
                . $this->e($platformUrl)
                . '">'
                . $this->e($platformUrl)
                . '</a>';
        }
        $html .= '</footer>';

        $html .= '<div class="header">';
        $html .= '<div class="eyebrow">Resultado automático</div>';
        $html .= '<h1>' . $this->e($evaluationName) . '</h1>';
        $html .= '<div class="subtitle">Avaliação de Percepção Relacional</div>';
        $html .= '</div>';

        $html .= '<section class="card">';
        $html .= '<div class="section-label">Comparação relacional</div>';
        $html .= '<h2>' . $this->e($participantA)
            . ' <span class="pair-arrow">↔</span> '
            . $this->e($participantB) . '</h2>';
        $html .= '<p>O resultado compara a percepção que cada participante tem do outro com a forma como o outro se percebe. Itens marcados como “Não se aplica” ficam fora do cálculo.</p>';
        if ($version !== '') {
            $html .= '<p class="muted"><strong>Versão do instrumento:</strong> '
                . $this->e($version) . '</p>';
        }
        $html .= '</section>';

        $html .= $this->generalScoreHtml($general);

        $html .= '<section class="narrative">';
        $html .= '<div class="section-label">Interpretação do score total</div>';
        $html .= '<h2>' . $this->e($resultTitle) . '</h2>';
        $html .= '<p>' . $this->e($resultText) . '</p>';
        $html .= '</section>';

        $html .= '<table class="two-col"><tr>';
        $html .= '<td>'
            . $this->directionalScoreHtml(
                'A_SOBRE_B',
                $resultsByDirection['A_SOBRE_B'] ?? [],
                $participantA
            )
            . '</td>';
        $html .= '<td>'
            . $this->directionalScoreHtml(
                'B_SOBRE_A',
                $resultsByDirection['B_SOBRE_A'] ?? [],
                $participantB
            )
            . '</td>';
        $html .= '</tr></table>';

        $sections = is_array($result['secoes'] ?? null)
            ? $result['secoes']
            : [];

        if ($sections !== []) {
            $html .= '<section class="card">';
            $html .= '<div class="section-label">Resultado por tópico</div>';
            $html .= '<h2>Seções da avaliação</h2>';

            foreach ($sections as $section) {
                if (!is_array($section)) {
                    continue;
                }

                $html .= '<div class="topic">';
                $html .= '<h3>' . $this->e((string) ($section['titulo'] ?? ''))
                    . '</h3>';
                $html .= '<table class="two-col compact"><tr>';
                $html .= '<td>'
                    . $this->sectionDirectionHtml(
                        $section['A_SOBRE_B'] ?? [],
                        'Score de ' . $participantA
                    )
                    . '</td>';
                $html .= '<td>'
                    . $this->sectionDirectionHtml(
                        $section['B_SOBRE_A'] ?? [],
                        'Score de ' . $participantB
                    )
                    . '</td>';
                $html .= '</tr></table>';
                $html .= '</div>';
            }

            $html .= '</section>';
        }

        $comparisons = is_array($result['comparacoes'] ?? null)
            ? $result['comparacoes']
            : [];

        if ($comparisons !== []) {
            $html .= '<section class="card comparisons">';
            $html .= '<div class="section-label">Coincidências e divergências</div>';
            $html .= '<h2>Comparação item a item</h2>';

            foreach ($comparisons as $comparison) {
                if (!is_array($comparison)) {
                    continue;
                }

                $html .= $this->comparisonHtml(
                    $comparison,
                    $participantA,
                    $participantB
                );
            }

            $html .= '</section>';
        }

        $excluded = is_array($result['itens_excluidos'] ?? null)
            ? $result['itens_excluidos']
            : [];

        if ($excluded !== []) {
            $html .= '<section class="card excluded">';
            $html .= '<div class="section-label">Fora do cálculo</div>';
            $html .= '<h2>Itens marcados como “Não se aplica”</h2>';

            foreach ($excluded as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $html .= '<div class="excluded-item">';
                $html .= '<strong>'
                    . $this->e((string) ($item['item_codigo'] ?? ''))
                    . ' · '
                    . $this->e((string) ($item['item_texto'] ?? ''))
                    . '</strong>';

                if (!empty($item['marcado_por_nome'])) {
                    $html .= '<div class="muted">Marcado por '
                        . $this->e((string) $item['marcado_por_nome'])
                        . '</div>';
                }

                $html .= '</div>';
            }

            $html .= '</section>';
        }

        $html .= '<section class="disclaimer">';
        $html .= 'Este documento apresenta uma comparação técnica das percepções e não constitui diagnóstico clínico automático. O resultado automático não substitui a devolutiva do profissional.';
        $html .= '</section>';

        $html .= '</body></html>';

        return $html;
    }

    /**
     * @param array<string,mixed> $general
     */
    private function generalScoreHtml(array $general): string
    {
        $percentage = $this->floatOrNull($general['percentual'] ?? null);
        $band = (string) ($general['faixa'] ?? '');
        $hits = (int) ($general['acertos_gerais'] ?? 0);
        $valid = (int) ($general['itens_validos'] ?? 0);

        $html = '<section class="card score-card '
            . $this->bandClass($band) . '">';
        $html .= '<div class="section-label">Score geral do casal</div>';
        $html .= '<h2>Acertos completos por item</h2>';
        $html .= '<p>Um item soma 1 ponto geral somente quando as duas comparações coincidem: o que A acredita que B pensa coincide com o que B respondeu, e o que B acredita que A pensa coincide com o que A respondeu.</p>';
        $html .= '<div class="score-row">';
        $html .= '<span class="score-number">'
            . $this->percentage($percentage) . '</span>';
        $html .= '<span class="band-pill">' . $this->e($band ?: 'Sem faixa')
            . '</span>';
        $html .= '</div>';
        $html .= $this->barHtml($percentage, $band);
        $html .= '<p class="score-detail">Score geral: '
            . $hits . ' de ' . $valid . ' item(ns) válido(s)</p>';
        $html .= '</section>';

        return $html;
    }

    /**
     * @param array<string,mixed> $directional
     */
    private function directionalScoreHtml(
        string $direction,
        array $directional,
        string $participantName
    ): string {
        $percentage = $this->floatOrNull($directional['percentual'] ?? null);
        $band = (string) ($directional['faixa'] ?? '');
        $hits = (int) ($directional['coincidencias'] ?? 0);
        $valid = (int) ($directional['comparacoes_validas'] ?? 0);

        $html = '<div class="card inner-score '
            . $this->bandClass($band) . '">';
        $html .= '<div class="direction">'
            . ($direction === 'A_SOBRE_B'
                ? 'A → B × B → B'
                : 'B → A × A → A')
            . '</div>';
        $html .= '<h3>Score de ' . $this->e($participantName) . '</h3>';
        $html .= '<div class="score-row small">';
        $html .= '<span class="score-number small">'
            . $this->percentage($percentage) . '</span>';
        $html .= '<span class="band-pill">' . $this->e($band ?: 'Sem faixa')
            . '</span>';
        $html .= '</div>';
        $html .= $this->barHtml($percentage, $band);
        $html .= '<p class="muted">'
            . $hits . ' coincidência(s) em ' . $valid
            . ' comparação(ões) válida(s)</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string,mixed> $direction
     */
    private function sectionDirectionHtml(
        array $direction,
        string $label
    ): string {
        $percentage = $this->floatOrNull($direction['percentual'] ?? null);
        $band = (string) ($direction['faixa'] ?? '');
        $hits = (int) ($direction['coincidencias'] ?? 0);
        $valid = (int) ($direction['comparacoes_validas'] ?? 0);

        return '<div class="topic-score">'
            . '<strong>' . $this->e($label) . '</strong>'
            . '<div class="topic-number">'
            . $this->percentage($percentage)
            . '</div>'
            . '<div class="muted">'
            . $this->e($band ?: 'Sem faixa')
            . ' · ' . $hits . ' de ' . $valid
            . '</div>'
            . '</div>';
    }

    /**
     * @param array<string,mixed> $comparison
     */
    private function comparisonHtml(
        array $comparison,
        string $participantA,
        string $participantB
    ): string {
        $direction = (string) ($comparison['sentido'] ?? '');
        [$perceivedLabel, $selfLabel] = $this->comparisonLabels(
            $direction,
            $participantA,
            $participantB
        );

        $comparavel = (bool) ($comparison['comparavel'] ?? false);
        $coincide = $comparison['coincide'] ?? null;

        if (!$comparavel) {
            $status = 'Não comparável';
            $statusClass = 'status-neutral';
        } elseif ($coincide === true) {
            $status = 'Coincide';
            $statusClass = 'status-match';
        } else {
            $status = 'Diverge';
            $statusClass = 'status-diverge';
        }

        $html = '<div class="comparison">';
        $html .= '<div class="comparison-head">';
        $html .= '<span class="code">'
            . $this->e((string) ($comparison['item_codigo'] ?? ''))
            . '</span>';
        $html .= '<span class="status ' . $statusClass . '">'
            . $this->e($status) . '</span>';
        $html .= '</div>';
        $html .= '<h3>' . $this->e((string) ($comparison['item_texto'] ?? ''))
            . '</h3>';
        $html .= '<table class="two-col response-table"><tr>';
        $html .= '<td><div class="response-box">';
        $html .= '<div class="response-label">' . $this->e($perceivedLabel)
            . '</div>';
        $html .= '<div class="response-value">'
            . $this->e(
                (string) (
                    $comparison['resposta_percebida']
                    ?? 'Sem resposta'
                )
            )
            . '</div>';
        $html .= '</div></td>';
        $html .= '<td><div class="response-box">';
        $html .= '<div class="response-label">' . $this->e($selfLabel)
            . '</div>';
        $html .= '<div class="response-value">'
            . $this->e(
                (string) (
                    $comparison['resposta_autorreferida']
                    ?? 'Sem resposta'
                )
            )
            . '</div>';
        $html .= '</div></td>';
        $html .= '</tr></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function comparisonLabels(
        string $direction,
        string $participantA,
        string $participantB
    ): array {
        if ($direction === 'A_SOBRE_B') {
            return [
                'O que ' . $participantA . ' percebe sobre ' . $participantB,
                'O que ' . $participantB . ' pensa sobre si',
            ];
        }

        return [
            'O que ' . $participantB . ' percebe sobre ' . $participantA,
            'O que ' . $participantA . ' pensa sobre si',
        ];
    }

    private function barHtml(?float $percentage, string $band): string
    {
        $width = $percentage === null
            ? 0
            : max(0, min(100, $percentage));

        return '<div class="bar"><div class="bar-fill" style="width:'
            . number_format($width, 2, '.', '')
            . '%;background:'
            . $this->barColor($band)
            . '"></div></div>';
    }

    private function bandClass(string $band): string
    {
        return match (strtolower(trim($band))) {
            'bom' => 'band-good',
            'regular' => 'band-regular',
            'ruim' => 'band-bad',
            default => 'band-neutral',
        };
    }

    private function barColor(string $band): string
    {
        return match (strtolower(trim($band))) {
            'bom' => '#22c55e',
            'regular' => '#facc15',
            'ruim' => '#ef4444',
            default => '#385048',
        };
    }

    private function percentage(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, 2, '.', '') . '%';
    }

    private function floatOrNull(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function e(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findProfessional(
        PDO $pdo,
        int $applicationId
    ): ?array {
        $stmt = $pdo->prepare(
            'SELECT
                p.id,
                p.nome,
                p.email
             FROM aplicacoes a
             INNER JOIN profissionais p
               ON p.id = a.profissional_id
             WHERE a.id = :aplicacao_id
             LIMIT 1'
        );
        $stmt->execute([
            'aplicacao_id' => $applicationId,
        ]);

        $professional = $stmt->fetch(PDO::FETCH_ASSOC);

        return $professional === false ? null : $professional;
    }

    private function css(): string
    {
        return <<<'CSS'
@page {
    margin: 16mm 13mm 21mm 13mm;
}
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: "DejaVu Sans", sans-serif;
    font-size: 10.5pt;
    line-height: 1.5;
    color: #385048;
    background: #FEFDFB;
}
h1, h2, h3, p {
    margin-top: 0;
}
h1 {
    margin-bottom: 3px;
    font-size: 24pt;
}
h2 {
    margin-bottom: 9px;
    font-size: 17pt;
}
h3 {
    margin-bottom: 8px;
    font-size: 12.5pt;
}
a {
    color: #385048;
    text-decoration: none;
}
.header {
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #A8C8B8;
}
.eyebrow,
.section-label,
.direction,
.response-label {
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 8pt;
    font-weight: 700;
    color: #6C8178;
}
.subtitle,
.muted {
    color: #6C8178;
}
.card,
.narrative,
.disclaimer {
    margin-bottom: 13px;
    padding: 14px;
    border: 1px solid #C9DBD2;
    border-radius: 10px;
    background: #FFFFFF;
}
.narrative {
    border-color: #D8B078;
    background: #FFF9EF;
}
.disclaimer {
    font-size: 9pt;
    background: #EFF6F7;
    border-color: #A8C8D0;
}
.pair-arrow {
    color: #88B098;
}
.score-card {
    page-break-inside: avoid;
}
.band-good {
    background: #F0FDF4;
    border-color: #86EFAC;
}
.band-regular {
    background: #FEFCE8;
    border-color: #FDE047;
}
.band-bad {
    background: #FEF2F2;
    border-color: #FCA5A5;
}
.band-neutral {
    background: #F8FBFB;
    border-color: #C9DBD2;
}
.score-row {
    margin: 12px 0 8px;
}
.score-number {
    font-size: 31pt;
    font-weight: 700;
    vertical-align: middle;
}
.score-number.small {
    font-size: 24pt;
}
.band-pill {
    display: inline-block;
    margin-left: 10px;
    padding: 4px 8px;
    border-radius: 12px;
    background: rgba(255,255,255,0.78);
    font-size: 9pt;
    font-weight: 700;
    vertical-align: middle;
}
.bar {
    height: 8px;
    margin: 8px 0 9px;
    border-radius: 5px;
    background: #E4ECE8;
    overflow: hidden;
}
.bar-fill {
    height: 8px;
}
.score-detail {
    margin-bottom: 0;
    font-weight: 600;
    color: #61756C;
}
.two-col {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6px 0;
    table-layout: fixed;
    margin: 0 -6px 13px -6px;
}
.two-col td {
    width: 50%;
    vertical-align: top;
}
.two-col.compact {
    margin-bottom: 0;
}
.inner-score {
    margin-bottom: 0;
    min-height: 145px;
}
.topic {
    margin-top: 10px;
    padding: 11px;
    border: 1px solid #DCE8E2;
    border-radius: 8px;
    background: #FEFDFB;
    page-break-inside: avoid;
}
.topic-score {
    padding: 10px;
    border: 1px solid #E5ECE8;
    border-radius: 7px;
    background: #FFFFFF;
}
.topic-number {
    margin: 6px 0 3px;
    font-size: 18pt;
    font-weight: 700;
}
.comparison {
    margin-top: 10px;
    padding: 11px;
    border: 1px solid #DCE8E2;
    border-radius: 8px;
    background: #FEFDFB;
    page-break-inside: avoid;
}
.comparison-head {
    margin-bottom: 7px;
}
.code,
.status {
    display: inline-block;
    padding: 3px 7px;
    border-radius: 10px;
    font-size: 8pt;
    font-weight: 700;
}
.code {
    background: #EAF3F5;
}
.status {
    margin-left: 6px;
}
.status-match {
    background: #E8F5EE;
}
.status-diverge {
    background: #FBEDE8;
}
.status-neutral {
    background: #FFF4DC;
}
.response-table {
    margin-bottom: 0;
}
.response-box {
    padding: 9px;
    border: 1px solid #E4ECE8;
    border-radius: 7px;
    background: #FFFFFF;
}
.response-value {
    margin-top: 6px;
    font-size: 10.5pt;
    font-weight: 600;
}
.excluded {
    background: #FFFCF5;
    border-color: #E7CEA8;
}
.excluded-item {
    margin-top: 8px;
    padding: 10px;
    border-radius: 7px;
    background: #FFF7E8;
    page-break-inside: avoid;
}
.page-footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: -14mm;
    padding-top: 5px;
    border-top: 1px solid #DCE8E2;
    font-size: 8pt;
    color: #6C8178;
    text-align: center;
}
CSS;
    }
}
