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

$files = glob($root . '/database/*.sql') ?: [];
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

    $pdo->beginTransaction();

    try {
        if (trim($sql) !== '') {
            $pdo->exec($sql);
        }

        $insert->execute([
            'filename' => $filename,
            'checksum' => $checksum,
        ]);

        $pdo->commit();
        $applied++;

        echo "[ok] {$filename}\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

echo "Migracoes aplicadas nesta execucao: {$applied}\n";
