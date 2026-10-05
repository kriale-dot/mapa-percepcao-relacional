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

$appEnv = strtolower(trim((string) ($_ENV['APP_ENV'] ?? 'development')));
$allowProduction = filter_var(
    $_ENV['ALLOW_DATABASE_CLEANUP'] ?? false,
    FILTER_VALIDATE_BOOL
);

if ($appEnv === 'production' && !$allowProduction) {
    fwrite(
        STDERR,
        "[ERRO] Limpeza bloqueada em producao.\n"
        . "Se for realmente necessario, defina temporariamente "
        . "ALLOW_DATABASE_CLEANUP=true no .env e execute novamente.\n"
    );
    exit(1);
}

$pdo = Database::connect();
$database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

if ($database === '') {
    fwrite(STDERR, "[ERRO] Banco de dados nao identificado.\n");
    exit(1);
}

$preservedTables = [
    'profissionais',
    'schema_migrations',
];

$tableStmt = $pdo->prepare(
    'SELECT table_name
       FROM information_schema.tables
      WHERE table_schema = :database
        AND table_type = \'BASE TABLE\'
      ORDER BY table_name ASC'
);
$tableStmt->execute(['database' => $database]);

$allTables = array_map(
    'strval',
    $tableStmt->fetchAll(PDO::FETCH_COLUMN)
);

$tablesToClean = array_values(
    array_filter(
        $allTables,
        static fn (string $table): bool =>
            !in_array($table, $preservedTables, true)
    )
);

if (!in_array('profissionais', $allTables, true)) {
    fwrite(
        STDERR,
        "[ERRO] A tabela profissionais nao existe neste banco.\n"
    );
    exit(1);
}

$professionalCount = (int) $pdo
    ->query('SELECT COUNT(*) FROM profissionais')
    ->fetchColumn();

echo "Banco: {$database}\n";
echo "APP_ENV: {$appEnv}\n";
echo "Profissionais preservados: {$professionalCount}\n";
echo "Tabelas que terao TODOS os dados removidos: "
    . count($tablesToClean)
    . "\n\n";

foreach ($tablesToClean as $table) {
    echo " - {$table}\n";
}

echo "\n";
echo "A estrutura das tabelas e o historico de migrations serao preservados.\n";
echo "Somente os registros de profissionais permanecerao como dados de negocio.\n";
echo "Recomendado: execute composer backup-db antes desta operacao.\n\n";

$confirmation = trim(
    (string) ($_ENV['CLEAN_DATABASE_CONFIRM'] ?? '')
);

if ($confirmation === '') {
    echo "Digite LIMPAR para confirmar: ";

    $input = fgets(STDIN);
    $confirmation = $input === false ? '' : trim($input);
}

if ($confirmation !== 'LIMPAR') {
    echo "Operacao cancelada.\n";
    exit(0);
}

if ($tablesToClean === []) {
    echo "[OK] Nenhuma tabela adicional para limpar.\n";
    exit(0);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    $tick = chr(96);

    foreach ($tablesToClean as $table) {
        $quoted = $tick
            . str_replace($tick, $tick . $tick, $table)
            . $tick;

        $pdo->exec("TRUNCATE TABLE {$quoted}");
        echo "[OK] {$table}\n";
    }
} finally {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$remainingProfessionals = (int) $pdo
    ->query('SELECT COUNT(*) FROM profissionais')
    ->fetchColumn();

echo "\n";
echo "[OK] Limpeza concluida.\n";
echo "Profissionais preservados: {$remainingProfessionals}\n";
echo "schema_migrations preservada para manter o controle das migrations.\n";
