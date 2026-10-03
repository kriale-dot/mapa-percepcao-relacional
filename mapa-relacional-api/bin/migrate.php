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

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        checksum CHAR(64) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$files = glob($root . '/migrations/*.sql') ?: [];
sort($files, SORT_NATURAL);

$select = $pdo->prepare(
    'SELECT checksum FROM schema_migrations WHERE filename = :filename LIMIT 1'
);

$insert = $pdo->prepare(
    'INSERT INTO schema_migrations (filename, checksum) VALUES (:filename, :checksum)'
);

$applied = 0;

foreach ($files as $file) {
    $filename = basename($file);
    $sql = file_get_contents($file);

    if ($sql === false) {
        throw new RuntimeException("Nao foi possivel ler {$filename}.");
    }

    $checksum = hash('sha256', $sql);

    $select->execute(['filename' => $filename]);
    $existing = $select->fetchColumn();

    if ($existing !== false) {
        if (!hash_equals((string) $existing, $checksum)) {
            throw new RuntimeException(
                "A migracao {$filename} foi alterada depois de aplicada."
            );
        }

        echo "[skip] {$filename}\n";
        continue;
    }

    try {
        foreach (splitSqlStatements($sql) as $statement) {
            $pdo->exec($statement);
        }

        $insert->execute([
            'filename' => $filename,
            'checksum' => $checksum,
        ]);

        $applied++;

        echo "[ok] {$filename}\n";
    } catch (Throwable $e) {
        throw new RuntimeException(
            "Falha ao aplicar {$filename}. A migracao nao foi registrada em schema_migrations. "
            . "Como MySQL faz commit implicito em DDL, confirme o estado do banco antes de repetir. "
            . "Erro original: {$e->getMessage()}",
            0,
            $e
        );
    }
}

echo "Migracoes aplicadas nesta execucao: {$applied}\n";

/**
 * Divide um arquivo SQL em comandos sem depender de multi-statements do PDO.
 * Respeita strings simples/duplas, identificadores com crase e comentarios SQL.
 *
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null;
    $lineComment = false;
    $blockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($lineComment) {
            if ($char === "\n") {
                $lineComment = false;
                $buffer .= $char;
            }
            continue;
        }

        if ($blockComment) {
            if ($char === '*' && $next === '/') {
                $blockComment = false;
                $i++;
            }
            continue;
        }

        if ($quote === null) {
            if ($char === '-' && $next === '-') {
                $lineComment = true;
                $i++;
                continue;
            }

            if ($char === '#') {
                $lineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $blockComment = true;
                $i++;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $char;
            continue;
        }

        $buffer .= $char;

        if ($char === '\\') {
            if ($next !== '') {
                $buffer .= $next;
                $i++;
            }
            continue;
        }

        if ($char === $quote) {
            if ($next === $quote && $quote !== '`') {
                $buffer .= $next;
                $i++;
                continue;
            }

            $quote = null;
        }
    }

    $statement = trim($buffer);

    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}
