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
        "[ERRO] Limpeza bloqueada em producao.\n"
        . "Para executar conscientemente em producao, defina temporariamente "
        . "ALLOW_DATABASE_CLEANUP=true no .env.\n"
    );
    exit(1);
}

$pdo = Database::connect();
$database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

if ($database === '') {
    fwrite(STDERR, "[ERRO] Banco de dados nao identificado.\n");
    exit(1);
}

$tableExists = static function (PDO $pdo, string $database, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
           FROM information_schema.tables
          WHERE table_schema = :database
            AND table_name = :table
            AND table_type = \'BASE TABLE\''
    );
    $stmt->execute([
        'database' => $database,
        'table' => $table,
    ]);

    return (int) $stmt->fetchColumn() > 0;
};

foreach (['profissionais', 'instrumentos', 'instrumento_versoes'] as $requiredTable) {
    if (!$tableExists($pdo, $database, $requiredTable)) {
        fwrite(
            STDERR,
            "[ERRO] Tabela obrigatoria ausente: {$requiredTable}. "
            . "Execute as migrations antes da limpeza.\n"
        );
        exit(1);
    }
}

$professional = $pdo
    ->query(
        'SELECT id, nome, email
           FROM profissionais
          WHERE id = 1
          LIMIT 1'
    )
    ->fetch(PDO::FETCH_ASSOC);

if ($professional === false) {
    fwrite(
        STDERR,
        "[ERRO] O profissional id=1 nao existe. "
        . "A limpeza foi cancelada para evitar perda de dados.\n"
    );
    exit(1);
}

$instrument = $pdo
    ->query(
        'SELECT id, profissional_id, nome, status
           FROM instrumentos
          WHERE id = 1
          LIMIT 1'
    )
    ->fetch(PDO::FETCH_ASSOC);

if ($instrument === false) {
    fwrite(
        STDERR,
        "[ERRO] O instrumento id=1 nao existe. "
        . "A limpeza foi cancelada para evitar perda de dados.\n"
    );
    exit(1);
}

if ((int) $instrument['profissional_id'] !== 1) {
    fwrite(
        STDERR,
        "[ERRO] O instrumento id=1 pertence ao profissional id="
        . (int) $instrument['profissional_id']
        . ", nao ao profissional id=1. "
        . "A limpeza foi cancelada.\n"
    );
    exit(1);
}

$versionIds = $pdo
    ->query(
        'SELECT id
           FROM instrumento_versoes
          WHERE instrumento_id = 1
          ORDER BY id'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if ($versionIds === []) {
    fwrite(
        STDERR,
        "[ERRO] O instrumento id=1 nao possui versoes. "
        . "A limpeza foi cancelada para nao deixar um instrumento incompleto.\n"
    );
    exit(1);
}

$operationalTables = [
    'acessos_aplicacao',
    'aplicacao_itens_excluidos',
    'comparacoes',
    'resultados_gerais',
    'resultados',
    'respostas',
    'devolutivas',
    'aplicacao_participantes',
    'aplicacoes',
    'vinculos',
    'pessoas',
    'auditoria_eventos',
    'rate_limites',
];

$operationalTables = array_values(
    array_filter(
        $operationalTables,
        static fn (string $table): bool => $tableExists($pdo, $database, $table)
    )
);

echo "Banco: {$database}\n";
echo "APP_ENV: {$appEnv}\n\n";
echo "Serao PRESERVADOS:\n";
echo " - profissional id=1: {$professional['nome']} <{$professional['email']}>\n";
echo " - instrumento id=1: {$instrument['nome']} ({$instrument['status']})\n";
echo " - toda a estrutura do instrumento id=1: versoes, secoes, itens, alternativas e faixas\n";
echo " - blocos do site vinculados ao profissional id=1\n";
echo " - schema_migrations\n\n";

echo "Serao REMOVIDOS:\n";
echo " - todos os demais profissionais\n";
echo " - todos os demais instrumentos e suas estruturas\n";
echo " - pessoas, vinculos, aplicacoes, acessos, respostas e resultados de teste\n";
echo " - devolutivas, auditoria e limites temporarios\n\n";

echo "Tabelas operacionais que serao esvaziadas: "
    . count($operationalTables)
    . "\n";
foreach ($operationalTables as $table) {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo " - {$table}: {$count} registro(s)\n";
}

echo "\nRecomendado antes da limpeza: composer backup-db\n\n";

$confirmation = trim((string) ($_ENV['CLEAN_DATABASE_CONFIRM'] ?? ''));

if (
    $confirmation === ''
    && isset($argv[1])
    && is_string($argv[1])
) {
    $confirmation = trim($argv[1]);
}

if ($confirmation === '') {
    if (defined('STDIN') && is_resource(STDIN)) {
        echo "Digite LIMPAR_DEPLOY para confirmar: ";
        $input = fgets(STDIN);
        $confirmation = $input === false ? '' : trim($input);
    } else {
        echo "Nao foi possivel ler a confirmacao interativa.\n";
        echo "Execute: composer clean-db-for-deploy -- LIMPAR_DEPLOY\n";
        exit(1);
    }
}

if ($confirmation !== 'LIMPAR_DEPLOY') {
    echo "Operacao cancelada.\n";
    echo "Para confirmar pelo Composer, execute:\n";
    echo "composer clean-db-for-deploy -- LIMPAR_DEPLOY\n";
    exit(0);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    foreach ($operationalTables as $table) {
        $pdo->exec("TRUNCATE TABLE `{$table}`");
        echo "[OK] {$table} esvaziada\n";
    }

    if ($tableExists($pdo, $database, 'site_blocos')) {
        $stmt = $pdo->prepare('DELETE FROM site_blocos WHERE profissional_id <> :id');
        $stmt->execute(['id' => 1]);
        echo "[OK] site_blocos: preservados apenas os do profissional id=1\n";
    }

    if ($tableExists($pdo, $database, 'resultado_faixas')) {
        $pdo->exec(
            'DELETE rf
               FROM resultado_faixas rf
               LEFT JOIN instrumento_versoes iv
                 ON iv.id = rf.instrumento_versao_id
                AND iv.instrumento_id = 1
              WHERE iv.id IS NULL'
        );
        echo "[OK] resultado_faixas: preservadas apenas as do instrumento id=1\n";
    }

    if ($tableExists($pdo, $database, 'alternativas')) {
        $pdo->exec(
            'DELETE a
               FROM alternativas a
               LEFT JOIN itens i ON i.id = a.item_id
               LEFT JOIN secoes s ON s.id = i.secao_id
               LEFT JOIN instrumento_versoes iv
                 ON iv.id = s.instrumento_versao_id
                AND iv.instrumento_id = 1
              WHERE iv.id IS NULL'
        );
        echo "[OK] alternativas: preservadas apenas as do instrumento id=1\n";
    }

    if ($tableExists($pdo, $database, 'itens')) {
        $pdo->exec(
            'DELETE i
               FROM itens i
               LEFT JOIN secoes s ON s.id = i.secao_id
               LEFT JOIN instrumento_versoes iv
                 ON iv.id = s.instrumento_versao_id
                AND iv.instrumento_id = 1
              WHERE iv.id IS NULL'
        );
        echo "[OK] itens: preservados apenas os do instrumento id=1\n";
    }

    if ($tableExists($pdo, $database, 'secoes')) {
        $pdo->exec(
            'DELETE s
               FROM secoes s
               LEFT JOIN instrumento_versoes iv
                 ON iv.id = s.instrumento_versao_id
                AND iv.instrumento_id = 1
              WHERE iv.id IS NULL'
        );
        echo "[OK] secoes: preservadas apenas as do instrumento id=1\n";
    }

    $pdo->exec('DELETE FROM instrumento_versoes WHERE instrumento_id <> 1');
    echo "[OK] instrumento_versoes: preservadas apenas as do instrumento id=1\n";

    $pdo->exec('DELETE FROM instrumentos WHERE id <> 1');
    echo "[OK] instrumentos: preservado apenas id=1\n";

    $pdo->exec('DELETE FROM profissionais WHERE id <> 1');
    echo "[OK] profissionais: preservado apenas id=1\n";

    $pdo->exec('ALTER TABLE instrumentos AUTO_INCREMENT = 2');
    $pdo->exec('ALTER TABLE profissionais AUTO_INCREMENT = 2');
} finally {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$remainingProfessionals = (int) $pdo
    ->query('SELECT COUNT(*) FROM profissionais')
    ->fetchColumn();

$remainingInstruments = (int) $pdo
    ->query('SELECT COUNT(*) FROM instrumentos')
    ->fetchColumn();

$remainingVersions = (int) $pdo
    ->query('SELECT COUNT(*) FROM instrumento_versoes WHERE instrumento_id = 1')
    ->fetchColumn();

echo "\n[OK] Limpeza para deploy concluida.\n";
echo "Profissionais restantes: {$remainingProfessionals} (esperado: 1)\n";
echo "Instrumentos restantes: {$remainingInstruments} (esperado: 1)\n";
echo "Versoes preservadas do instrumento id=1: {$remainingVersions}\n";
echo "schema_migrations foi preservada.\n";
