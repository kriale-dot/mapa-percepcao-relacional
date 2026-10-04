<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$pdo = Database::connect();

$database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

$requiredTables = [
    'schema_migrations',
    'profissionais',
    'pessoas',
    'vinculos',
    'instrumentos',
    'instrumento_versoes',
    'secoes',
    'itens',
    'alternativas',
    'aplicacoes',
    'aplicacao_participantes',
    'acessos_aplicacao',
    'aplicacao_itens_excluidos',
    'respostas',
    'resultado_faixas',
    'comparacoes',
    'resultados',
    'devolutivas',
    'auditoria_eventos',
];

$stmt = $pdo->prepare(
    'SELECT table_name
       FROM information_schema.tables
      WHERE table_schema = :database'
);
$stmt->execute(['database' => $database]);

$existingTables = array_map(
    static fn ($table): string => (string) $table,
    $stmt->fetchAll(\PDO::FETCH_COLUMN)
);

$missingTables = array_values(array_diff($requiredTables, $existingTables));

echo "Banco: {$database}\n";
echo "Tabelas esperadas: " . count($requiredTables) . "\n";
echo "Tabelas encontradas: " . (count($requiredTables) - count($missingTables)) . "\n";

if ($missingTables !== []) {
    echo "[ERRO] Tabelas ausentes:\n";

    foreach ($missingTables as $table) {
        echo "  - {$table}\n";
    }

    exit(1);
}

echo "[OK] Estrutura base do dominio presente.\n";

$requiredMigrations = [
    '001_base_dominio.sql',
    '002_profissional_autenticacao.sql',
    '003_profissional_perfil.sql',
    '004_resultados_comparacoes.sql',
    '005_ajustar_faixas_percentuais.sql',
    '006_devolutivas.sql',
    '007_auditoria.sql',
];

$migrationStmt = $pdo->query(
    'SELECT filename
       FROM schema_migrations'
);

$appliedMigrations = array_map(
    static fn ($filename): string => (string) $filename,
    $migrationStmt->fetchAll(\PDO::FETCH_COLUMN)
);

$missingMigrations = array_values(array_diff($requiredMigrations, $appliedMigrations));

if ($missingMigrations !== []) {
    echo "[ERRO] Migracoes obrigatorias nao registradas:\n";

    foreach ($missingMigrations as $migration) {
        echo "  - {$migration}\n";
    }

    exit(1);
}

foreach ($requiredMigrations as $migration) {
    echo "[OK] Migracao registrada: {$migration}\n";
}

$requiredProfessionalColumns = [
    'senha_hash',
    'senha_alterada_em',
    'ultimo_login_em',
    'descricao',
    'atuacao',
    'foto_url',
    'logo_url',
    'dados_contato',
];

$columnStmt = $pdo->prepare(
    'SELECT column_name
       FROM information_schema.columns
      WHERE table_schema = :database
        AND table_name = :table'
);
$columnStmt->execute([
    'database' => $database,
    'table' => 'profissionais',
]);

$existingProfessionalColumns = array_map(
    static fn ($column): string => (string) $column,
    $columnStmt->fetchAll(\PDO::FETCH_COLUMN)
);

$missingProfessionalColumns = array_values(
    array_diff($requiredProfessionalColumns, $existingProfessionalColumns)
);

if ($missingProfessionalColumns !== []) {
    echo "[ERRO] Colunas obrigatorias ausentes em profissionais:\n";

    foreach ($missingProfessionalColumns as $column) {
        echo "  - {$column}\n";
    }

    exit(1);
}

echo "[OK] Estrutura de autenticacao e perfil do profissional presente.\n";
echo "Status: OK\n";
