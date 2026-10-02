<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;
use PDO;

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
];

$stmt = $pdo->prepare(
    'SELECT table_name
       FROM information_schema.tables
      WHERE table_schema = :database'
);
$stmt->execute(['database' => $database]);

$existing = array_map(
    static fn (array $row): string => (string) $row['TABLE_NAME'],
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);

$missing = array_values(array_diff($requiredTables, $existing));

$migrationStmt = $pdo->prepare(
    'SELECT filename, checksum, applied_at
       FROM schema_migrations
      WHERE filename = :filename
      LIMIT 1'
);
$migrationStmt->execute(['filename' => '001_base_dominio.sql']);
$migration = $migrationStmt->fetch(PDO::FETCH_ASSOC);

echo "Banco: {$database}\n";
echo "Tabelas esperadas: " . count($requiredTables) . "\n";
echo "Tabelas encontradas: " . (count($requiredTables) - count($missing)) . "\n";

if ($missing !== []) {
    echo "[ERRO] Tabelas ausentes:\n";

    foreach ($missing as $table) {
        echo "  - {$table}\n";
    }

    exit(1);
}

echo "[OK] Estrutura base do dominio presente.\n";

if ($migration === false) {
    echo "[ERRO] 001_base_dominio.sql nao esta registrada em schema_migrations.\n";
    exit(1);
}

echo "[OK] Migracao registrada: {$migration['filename']} em {$migration['applied_at']}\n";
echo "Status: OK\n";
