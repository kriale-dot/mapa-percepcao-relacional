<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$appEnv = strtolower(trim((string) ($_ENV['APP_ENV'] ?? 'development')));
$allowProduction = filter_var(
    $_ENV['ALLOW_DATABASE_CLEANUP'] ?? false,
    FILTER_VALIDATE_BOOL
);

if ($appEnv === 'production' && !$allowProduction) {
    fwrite(
        STDERR,
        "[ERRO] Exclusao bloqueada em producao.\n"
        . "Se for realmente necessario, defina temporariamente "
        . "ALLOW_DATABASE_CLEANUP=true no .env.\n"
    );
    exit(1);
}

$pdo = Database::connect();

$keepName = 'Avaliação Conjugal';
$targetNames = [
    'Amizade',
    'Avaliação e amizade',
    'Avaliação de amizade',
];

$keepStmt = $pdo->prepare(
    'SELECT id, nome, status
       FROM instrumentos
      WHERE nome = :nome
      ORDER BY id ASC'
);
$keepStmt->execute(['nome' => $keepName]);
$keepRows = $keepStmt->fetchAll(PDO::FETCH_ASSOC);

if ($keepRows === []) {
    fwrite(
        STDERR,
        "[ERRO] A avaliacao '{$keepName}' nao foi encontrada. "
        . "A operacao foi cancelada por seguranca.\n"
    );
    exit(1);
}

$namePlaceholders = [];
$nameParams = [];

foreach ($targetNames as $index => $name) {
    $key = 'nome_' . $index;
    $namePlaceholders[] = ':' . $key;
    $nameParams[$key] = $name;
}

$targetStmt = $pdo->prepare(
    'SELECT id, profissional_id, nome, status
       FROM instrumentos
      WHERE nome IN (' . implode(', ', $namePlaceholders) . ')
      ORDER BY nome ASC, id ASC'
);
$targetStmt->execute($nameParams);
$targets = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

echo "APP_ENV: {$appEnv}\n\n";
echo "Avaliacao que sera PRESERVADA:\n";

foreach ($keepRows as $row) {
    echo " - #{$row['id']} {$row['nome']} ({$row['status']})\n";
}

echo "\nAvaliacoes selecionadas para EXCLUSAO:\n";

if ($targets === []) {
    echo " - nenhuma encontrada\n\n";
    echo "[OK] Nada para excluir. A Avaliacao Conjugal foi preservada.\n";
    exit(0);
}

foreach ($targets as $row) {
    echo " - #{$row['id']} {$row['nome']} ({$row['status']})\n";
}

$instrumentIds = array_map(
    static fn (array $row): int => (int) $row['id'],
    $targets
);

function placeholders(array $values, string $prefix): array
{
    $parts = [];
    $params = [];

    foreach (array_values($values) as $index => $value) {
        $key = $prefix . '_' . $index;
        $parts[] = ':' . $key;
        $params[$key] = $value;
    }

    return [implode(', ', $parts), $params];
}

function fetchIds(
    PDO $pdo,
    string $sql,
    array $params
): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );
}

function deleteByIds(
    PDO $pdo,
    string $table,
    string $column,
    array $ids,
    string $prefix
): int {
    if ($ids === []) {
        return 0;
    }

    [$in, $params] = placeholders($ids, $prefix);

    $stmt = $pdo->prepare(
        "DELETE FROM {$table} WHERE {$column} IN ({$in})"
    );
    $stmt->execute($params);

    return $stmt->rowCount();
}

[$instrumentIn, $instrumentParams] = placeholders(
    $instrumentIds,
    'instrumento'
);

$versionIds = fetchIds(
    $pdo,
    'SELECT id
       FROM instrumento_versoes
      WHERE instrumento_id IN (' . $instrumentIn . ')',
    $instrumentParams
);

$sectionIds = [];

if ($versionIds !== []) {
    [$versionIn, $versionParams] = placeholders(
        $versionIds,
        'versao_secao'
    );

    $sectionIds = fetchIds(
        $pdo,
        'SELECT id
           FROM secoes
          WHERE instrumento_versao_id IN (' . $versionIn . ')',
        $versionParams
    );
}

$itemIds = [];

if ($sectionIds !== []) {
    [$sectionIn, $sectionParams] = placeholders(
        $sectionIds,
        'secao_item'
    );

    $itemIds = fetchIds(
        $pdo,
        'SELECT id
           FROM itens
          WHERE secao_id IN (' . $sectionIn . ')',
        $sectionParams
    );
}

$applicationIds = [];

if ($versionIds !== []) {
    [$versionIn, $versionParams] = placeholders(
        $versionIds,
        'versao_aplicacao'
    );

    $applicationIds = fetchIds(
        $pdo,
        'SELECT id
           FROM aplicacoes
          WHERE instrumento_versao_id IN (' . $versionIn . ')',
        $versionParams
    );
}

echo "\nResumo relacionado:\n";
echo " - instrumentos: " . count($instrumentIds) . "\n";
echo " - versoes: " . count($versionIds) . "\n";
echo " - secoes: " . count($sectionIds) . "\n";
echo " - itens: " . count($itemIds) . "\n";
echo " - aplicacoes: " . count($applicationIds) . "\n";
echo "\nTodos os dados dependentes dessas duas avaliacoes serao removidos.\n";
echo "Profissionais, pessoas, vinculos, site institucional e Avaliacao Conjugal serao preservados.\n";
echo "Recomendado: execute composer backup-db antes.\n\n";

$confirmation = trim(
    (string) ($_ENV['REMOVE_EVALUATIONS_CONFIRM'] ?? '')
);

if (
    $confirmation === ''
    && isset($argv[1])
    && is_string($argv[1])
) {
    $confirmation = trim($argv[1]);
}

if ($confirmation === '') {
    echo "Digite EXCLUIR para confirmar: ";

    $input = defined('STDIN') && is_resource(STDIN)
        ? fgets(STDIN)
        : false;

    $confirmation = $input === false ? '' : trim($input);
}

if ($confirmation !== 'EXCLUIR') {
    echo "Operacao cancelada.\n";
    echo "Para confirmar pelo Composer, execute:\n";
    echo "composer remove-extra-evaluations -- EXCLUIR\n";
    exit(0);
}

$counts = [];

$pdo->beginTransaction();

try {
    if ($applicationIds !== []) {
        [$auditAppIn, $auditAppParams] = placeholders(
            $applicationIds,
            'audit_app'
        );

        $auditAppStmt = $pdo->prepare(
            "DELETE FROM auditoria_eventos
              WHERE entidade_tipo = 'APLICACAO'
                AND entidade_id IN ({$auditAppIn})"
        );
        $auditAppStmt->execute($auditAppParams);
        $counts['auditoria_aplicacoes'] = $auditAppStmt->rowCount();

        $counts['devolutivas'] = deleteByIds(
            $pdo,
            'devolutivas',
            'aplicacao_id',
            $applicationIds,
            'devolutiva'
        );

        $counts['resultados_gerais'] = deleteByIds(
            $pdo,
            'resultados_gerais',
            'aplicacao_id',
            $applicationIds,
            'resultado_geral'
        );

        $counts['resultados'] = deleteByIds(
            $pdo,
            'resultados',
            'aplicacao_id',
            $applicationIds,
            'resultado'
        );

        $counts['comparacoes'] = deleteByIds(
            $pdo,
            'comparacoes',
            'aplicacao_id',
            $applicationIds,
            'comparacao'
        );

        $counts['respostas'] = deleteByIds(
            $pdo,
            'respostas',
            'aplicacao_id',
            $applicationIds,
            'resposta'
        );

        $counts['itens_excluidos'] = deleteByIds(
            $pdo,
            'aplicacao_itens_excluidos',
            'aplicacao_id',
            $applicationIds,
            'item_excluido'
        );

        [$appIn, $appParams] = placeholders(
            $applicationIds,
            'app_participante'
        );

        $participantIds = fetchIds(
            $pdo,
            'SELECT id
               FROM aplicacao_participantes
              WHERE aplicacao_id IN (' . $appIn . ')',
            $appParams
        );

        if ($participantIds !== []) {
            $counts['acessos'] = deleteByIds(
                $pdo,
                'acessos_aplicacao',
                'aplicacao_participante_id',
                $participantIds,
                'acesso'
            );
        }

        $counts['participantes'] = deleteByIds(
            $pdo,
            'aplicacao_participantes',
            'aplicacao_id',
            $applicationIds,
            'participante'
        );

        $counts['aplicacoes'] = deleteByIds(
            $pdo,
            'aplicacoes',
            'id',
            $applicationIds,
            'aplicacao'
        );
    }

    if ($itemIds !== []) {
        $counts['alternativas'] = deleteByIds(
            $pdo,
            'alternativas',
            'item_id',
            $itemIds,
            'alternativa'
        );

        $counts['itens'] = deleteByIds(
            $pdo,
            'itens',
            'id',
            $itemIds,
            'item'
        );
    }

    if ($sectionIds !== []) {
        $counts['secoes'] = deleteByIds(
            $pdo,
            'secoes',
            'id',
            $sectionIds,
            'secao'
        );
    }

    if ($versionIds !== []) {
        $counts['faixas'] = deleteByIds(
            $pdo,
            'resultado_faixas',
            'instrumento_versao_id',
            $versionIds,
            'faixa'
        );

        [$versionAuditIn, $versionAuditParams] = placeholders(
            $versionIds,
            'audit_versao'
        );

        $auditVersionStmt = $pdo->prepare(
            "DELETE FROM auditoria_eventos
              WHERE entidade_tipo = 'INSTRUMENTO_VERSAO'
                AND entidade_id IN ({$versionAuditIn})"
        );
        $auditVersionStmt->execute($versionAuditParams);
        $counts['auditoria_versoes'] = $auditVersionStmt->rowCount();

        $counts['versoes'] = deleteByIds(
            $pdo,
            'instrumento_versoes',
            'id',
            $versionIds,
            'versao'
        );
    }

    [$instrumentAuditIn, $instrumentAuditParams] = placeholders(
        $instrumentIds,
        'audit_instrumento'
    );

    $auditInstrumentStmt = $pdo->prepare(
        "DELETE FROM auditoria_eventos
          WHERE entidade_tipo = 'INSTRUMENTO'
            AND entidade_id IN ({$instrumentAuditIn})"
    );
    $auditInstrumentStmt->execute($instrumentAuditParams);
    $counts['auditoria_instrumentos'] =
        $auditInstrumentStmt->rowCount();

    $counts['instrumentos'] = deleteByIds(
        $pdo,
        'instrumentos',
        'id',
        $instrumentIds,
        'instrumento_delete'
    );

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(
        STDERR,
        "[ERRO] A exclusao falhou e foi revertida: "
        . $error->getMessage()
        . "\n"
    );
    exit(1);
}

echo "\n[OK] Exclusao concluida.\n";

foreach ($counts as $label => $count) {
    echo " - {$label}: {$count}\n";
}

$remainingStmt = $pdo->query(
    'SELECT id, nome, status
       FROM instrumentos
      ORDER BY nome ASC, id ASC'
);

echo "\nInstrumentos que permaneceram:\n";

foreach ($remainingStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo " - #{$row['id']} {$row['nome']} ({$row['status']})\n";
}
