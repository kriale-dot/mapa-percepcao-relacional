<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

function canWriteDirectory(string $directory): bool
{
    if (!is_dir($directory)) {
        return false;
    }

    $probe = rtrim($directory, '/\\')
        . DIRECTORY_SEPARATOR
        . '.write-test-'
        . bin2hex(random_bytes(8))
        . '.tmp';

    $written = @file_put_contents($probe, 'ok');

    if ($written === false) {
        return false;
    }

    @unlink($probe);

    return true;
}

$errors = [];
$warnings = [];
$ok = [];

$check = static function (
    bool $condition,
    string $label,
    bool $warningOnly = false
) use (&$errors, &$warnings, &$ok): void {
    if ($condition) {
        $ok[] = $label;
        return;
    }

    if ($warningOnly) {
        $warnings[] = $label;
        return;
    }

    $errors[] = $label;
};

$appEnv = strtolower(trim((string) ($_ENV['APP_ENV'] ?? '')));
$appDebug = filter_var(
    $_ENV['APP_DEBUG'] ?? false,
    FILTER_VALIDATE_BOOL
);
$appUrl = trim((string) ($_ENV['APP_URL'] ?? ''));
$frontendUrl = trim((string) ($_ENV['FRONTEND_URL'] ?? ''));
$jwtSecret = (string) ($_ENV['JWT_SECRET'] ?? '');

$check(
    $appEnv === 'production',
    'APP_ENV=production',
    true
);
$check(
    !$appDebug,
    'APP_DEBUG=false',
    true
);
$check(
    filter_var($appUrl, FILTER_VALIDATE_URL) !== false,
    'APP_URL valida'
);
$check(
    filter_var($frontendUrl, FILTER_VALIDATE_URL) !== false,
    'FRONTEND_URL valida'
);

if ($appEnv === 'production') {
    $check(
        str_starts_with($appUrl, 'https://'),
        'APP_URL usa HTTPS em producao'
    );
    $check(
        str_starts_with($frontendUrl, 'https://'),
        'FRONTEND_URL usa HTTPS em producao'
    );
}

$check(
    strlen($jwtSecret) >= 32
        && !str_contains(
            strtolower($jwtSecret),
            'troque-por-um-segredo'
        ),
    'JWT_SECRET forte e configurado'
);

$check(
    extension_loaded('pdo_mysql'),
    'Extensao pdo_mysql carregada'
);
$check(
    extension_loaded('openssl'),
    'Extensao openssl carregada'
);
$check(
    extension_loaded('json'),
    'Extensao json carregada'
);

$check(
    trim((string) ($_ENV['SMTP_HOST'] ?? '')) !== '',
    'SMTP_HOST configurado'
);
$check(
    trim((string) ($_ENV['SMTP_USERNAME'] ?? '')) !== '',
    'SMTP_USERNAME configurado'
);
$check(
    (string) ($_ENV['SMTP_PASSWORD'] ?? '') !== '',
    'SMTP_PASSWORD configurado'
);
$check(
    filter_var(
        trim((string) ($_ENV['MAIL_FROM_EMAIL'] ?? '')),
        FILTER_VALIDATE_EMAIL
    ) !== false,
    'MAIL_FROM_EMAIL valido'
);

$logDir = $root . '/storage/logs';

if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

$check(
    canWriteDirectory($logDir),
    'storage/logs gravavel'
);

$backupDir = $root . '/storage/backups';

if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0775, true);
}

$check(
    canWriteDirectory($backupDir),
    'storage/backups gravavel'
);

$backupFiles = glob($backupDir . '/mapa-relacional-*.sql') ?: [];
$latestBackupAt = 0;

foreach ($backupFiles as $backupFile) {
    if (!is_file($backupFile) || filesize($backupFile) <= 0) {
        continue;
    }

    $mtime = filemtime($backupFile);

    if ($mtime !== false) {
        $latestBackupAt = max($latestBackupAt, $mtime);
    }
}

$check(
    $latestBackupAt > 0
        && $latestBackupAt >= time() - 86400,
    'Backup SQL valido criado nas ultimas 24 horas',
    true
);

try {
    $pdo = Database::connect();
    $pdo->query('SELECT 1')->fetchColumn();
    $ok[] = 'Conexao MySQL';

    $requiredTables = [
        'profissionais',
        'instrumentos',
        'instrumento_versoes',
        'aplicacoes',
        'aplicacao_participantes',
        'acessos_aplicacao',
        'respostas',
        'resultado_faixas',
        'comparacoes',
        'resultados',
        'devolutivas',
        'auditoria_eventos',
        'rate_limites',
        'site_blocos',
    ];

    $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $stmt = $pdo->prepare(
        'SELECT table_name
           FROM information_schema.tables
          WHERE table_schema = :database'
    );
    $stmt->execute(['database' => $database]);
    $tables = array_map(
        'strval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    $missing = array_values(array_diff($requiredTables, $tables));
    $check(
        $missing === [],
        $missing === []
            ? 'Estrutura V1 presente'
            : 'Tabelas ausentes: ' . implode(', ', $missing)
    );

    $migrationStmt = $pdo->query(
        'SELECT filename FROM schema_migrations'
    );
    $applied = array_map(
        'strval',
        $migrationStmt->fetchAll(PDO::FETCH_COLUMN)
    );

    $requiredMigrations = [
        '001_base_dominio.sql',
        '002_profissional_autenticacao.sql',
        '003_profissional_perfil.sql',
        '004_resultados_comparacoes.sql',
        '005_ajustar_faixas_percentuais.sql',
        '006_devolutivas.sql',
        '007_auditoria.sql',
        '008_rate_limites.sql',
        '009_site_institucional.sql',
    ];

    $missingMigrations = array_values(
        array_diff($requiredMigrations, $applied)
    );
    $check(
        $missingMigrations === [],
        $missingMigrations === []
            ? 'Migracoes V1 registradas'
            : 'Migracoes ausentes: ' . implode(', ', $missingMigrations)
    );
} catch (Throwable $error) {
    $errors[] = 'Banco de dados: ' . $error->getMessage();
}

foreach ($ok as $item) {
    echo "[OK] {$item}\n";
}

foreach ($warnings as $item) {
    echo "[AVISO] {$item}\n";
}

foreach ($errors as $item) {
    echo "[ERRO] {$item}\n";
}

echo "\n";
echo "Erros: " . count($errors) . "\n";
echo "Avisos: " . count($warnings) . "\n";

if ($errors !== []) {
    echo "Status: NAO_PRONTO\n";
    exit(1);
}

if ($warnings !== []) {
    echo "Status: PRONTO_COM_AVISOS\n";
    exit(0);
}

echo "Status: PRONTO\n";
exit(0);
