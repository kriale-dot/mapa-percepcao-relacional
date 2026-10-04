<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

if (!function_exists('proc_open')) {
    fwrite(
        STDERR,
        "[ERRO] proc_open nao esta disponivel neste ambiente.\n"
    );
    exit(1);
}

$database = trim((string) ($_ENV['DB_DATABASE'] ?? ''));
$username = trim((string) ($_ENV['DB_USERNAME'] ?? ''));
$password = (string) ($_ENV['DB_PASSWORD'] ?? '');
$host = trim((string) ($_ENV['DB_HOST'] ?? '127.0.0.1'));
$port = trim((string) ($_ENV['DB_PORT'] ?? '3306'));
$configuredBinary = trim(
    (string) ($_ENV['MYSQLDUMP_BIN'] ?? 'mysqldump')
);
$binary = resolveMysqldumpBinary($configuredBinary);

if ($database === '' || $username === '') {
    fwrite(
        STDERR,
        "[ERRO] DB_DATABASE e DB_USERNAME sao obrigatorios.\n"
    );
    exit(1);
}

if ($binary === null) {
    fwrite(
        STDERR,
        "[ERRO] mysqldump nao foi encontrado.\n"
    );
    fwrite(
        STDERR,
        "No Windows, localize o arquivo mysqldump.exe e configure no .env, por exemplo:\n"
    );
    fwrite(
        STDERR,
        "MYSQLDUMP_BIN=\"C:\\\\Program Files\\\\MySQL\\\\MySQL Server 8.0\\\\bin\\\\mysqldump.exe\"\n"
    );
    exit(1);
}

echo "[OK] mysqldump localizado: {$binary}\n";

$backupDir = $root . '/storage/backups';

if (!is_dir($backupDir) && !@mkdir($backupDir, 0775, true)) {
    fwrite(
        STDERR,
        "[ERRO] Nao foi possivel criar storage/backups.\n"
    );
    exit(1);
}

if (!canWriteDirectory($backupDir)) {
    fwrite(
        STDERR,
        "[ERRO] Nao foi possivel gravar em storage/backups.\n"
    );
    fwrite(
        STDERR,
        "Caminho testado: {$backupDir}\n"
    );
    exit(1);
}

$timestamp = date('Ymd-His');
$path = $backupDir . '/mapa-relacional-' . $timestamp . '.sql';

$command = [
    $binary,
    '--single-transaction',
    '--routines',
    '--triggers',
    '--events',
    '--default-character-set=utf8mb4',
    '--host=' . $host,
    '--port=' . $port,
    '--user=' . $username,
    $database,
];

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $path, 'w'],
    2 => ['pipe', 'w'],
];

$previousMysqlPwd = getenv('MYSQL_PWD');
putenv('MYSQL_PWD=' . $password);

$process = proc_open(
    $command,
    $descriptors,
    $pipes,
    $root
);

if (!is_resource($process)) {
    if ($previousMysqlPwd === false) {
        putenv('MYSQL_PWD');
    } else {
        putenv('MYSQL_PWD=' . $previousMysqlPwd);
    }

    fwrite(
        STDERR,
        "[ERRO] Nao foi possivel iniciar mysqldump.\n"
    );
    exit(1);
}

fclose($pipes[0]);
$stderr = stream_get_contents($pipes[2]) ?: '';
fclose($pipes[2]);

$exitCode = proc_close($process);

if ($previousMysqlPwd === false) {
    putenv('MYSQL_PWD');
} else {
    putenv('MYSQL_PWD=' . $previousMysqlPwd);
}

if ($exitCode !== 0) {
    @unlink($path);

    fwrite(
        STDERR,
        "[ERRO] mysqldump falhou com codigo {$exitCode}.\n"
    );

    if (trim($stderr) !== '') {
        fwrite(
            STDERR,
            trim($stderr) . "\n"
        );
    }

    exit(1);
}

if (!is_file($path) || filesize($path) === 0) {
    @unlink($path);

    fwrite(
        STDERR,
        "[ERRO] O backup foi criado vazio.\n"
    );
    exit(1);
}

$hash = hash_file('sha256', $path);
$size = filesize($path);

echo "[OK] Backup criado.\n";
echo "Arquivo: {$path}\n";
echo "Tamanho: {$size} bytes\n";
echo "SHA-256: {$hash}\n";


/**
 * Testa gravacao real no diretorio.
 *
 * No Windows, is_writable() pode retornar falso mesmo quando a pasta
 * permite gravacao. O teste abaixo cria e remove um arquivo temporario.
 */
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

/**
 * Localiza o executavel mysqldump no PATH, no valor configurado
 * ou em instalacoes comuns do Windows.
 */
function resolveMysqldumpBinary(string $configured): ?string
{
    $configured = trim($configured, " \t\n\r\0\x0B\"'");

    if ($configured === '') {
        return null;
    }

    $hasDirectory = str_contains($configured, '/')
        || str_contains($configured, '\\');

    if ($hasDirectory) {
        return is_file($configured) ? $configured : null;
    }

    $isWindows = PHP_OS_FAMILY === 'Windows';
    $executableName = $configured;

    if ($isWindows && !str_ends_with(strtolower($executableName), '.exe')) {
        $executableName .= '.exe';
    }

    $pathValue = (string) (
        getenv('PATH')
        ?: ($_SERVER['PATH'] ?? '')
    );

    foreach (explode(PATH_SEPARATOR, $pathValue) as $directory) {
        $directory = trim($directory, " \t\n\r\0\x0B\"");

        if ($directory === '') {
            continue;
        }

        $candidate = rtrim($directory, '/\\')
            . DIRECTORY_SEPARATOR
            . $executableName;

        if (is_file($candidate)) {
            return $candidate;
        }
    }

    if (!$isWindows) {
        foreach ([
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    $patterns = [
        'C:/Program Files/MySQL/MySQL Server */bin/mysqldump.exe',
        'C:/Program Files (x86)/MySQL/MySQL Server */bin/mysqldump.exe',
        'C:/Program Files/MariaDB */bin/mysqldump.exe',
        'C:/xampp/mysql/bin/mysqldump.exe',
        'C:/laragon/bin/mysql/*/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/*/bin/mysqldump.exe',
    ];

    foreach ($patterns as $pattern) {
        $matches = glob($pattern) ?: [];

        if ($matches === []) {
            continue;
        }

        rsort($matches, SORT_NATURAL);

        foreach ($matches as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
    }

    return null;
}
