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

$stmt = $pdo->query(
    "SELECT id, nome, email, senha_hash, status, senha_alterada_em
       FROM profissionais
      ORDER BY id ASC"
);

$professionals = $stmt->fetchAll();

if ($professionals === []) {
    fwrite(STDERR, "[ERRO] Nenhum profissional cadastrado.\n");
    exit(1);
}

$valid = 0;

foreach ($professionals as $professional) {
    $hash = (string) ($professional['senha_hash'] ?? '');
    $info = $hash !== '' ? password_get_info($hash) : ['algo' => null];

    if ($hash === '' || empty($info['algo'])) {
        fwrite(
            STDERR,
            "[ERRO] Profissional ID {$professional['id']} nao possui hash de senha valido.\n"
        );
        exit(1);
    }

    if ((string) $professional['status'] !== 'ATIVO') {
        fwrite(
            STDERR,
            "[ERRO] Profissional ID {$professional['id']} nao esta ATIVO.\n"
        );
        exit(1);
    }

    if (empty($professional['senha_alterada_em'])) {
        fwrite(
            STDERR,
            "[ERRO] Profissional ID {$professional['id']} nao possui senha_alterada_em.\n"
        );
        exit(1);
    }

    $valid++;
}

echo "[OK] Profissionais com autenticacao configurada: {$valid}\n";

$first = $professionals[0];
echo "Primeiro profissional: {$first['nome']} <{$first['email']}>\n";
echo "Status: OK\n";
