<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$password = envValue('SETUP_PROFESSIONAL_PASSWORD');

if ($password === '') {
    fwrite(STDERR, "[ERRO] SETUP_PROFESSIONAL_PASSWORD nao foi informada.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "[ERRO] A senha inicial deve ter pelo menos 8 caracteres.\n");
    exit(1);
}

$nome = envValue('SETUP_PROFESSIONAL_NAME');
$email = envValue('SETUP_PROFESSIONAL_EMAIL');
$telefone = envValue('SETUP_PROFESSIONAL_PHONE');

if ($nome === '') {
    fwrite(STDERR, "[ERRO] SETUP_PROFESSIONAL_NAME nao foi informada.\n");
    exit(1);
}

if ($email === '') {
    fwrite(STDERR, "[ERRO] SETUP_PROFESSIONAL_EMAIL nao foi informado.\n");
    exit(1);
}

$email = strtolower(trim($email));

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "[ERRO] E-mail invalido.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($hash === false) {
    fwrite(STDERR, "[ERRO] Nao foi possivel gerar o hash da senha.\n");
    exit(1);
}

// Remove a senha do ambiente deste processo assim que o hash for criado.
putenv('SETUP_PROFESSIONAL_PASSWORD');
unset($_ENV['SETUP_PROFESSIONAL_PASSWORD'], $_SERVER['SETUP_PROFESSIONAL_PASSWORD'], $password);

$pdo = Database::connect();

$count = (int) $pdo->query('SELECT COUNT(*) FROM profissionais')->fetchColumn();

if ($count > 0) {
    fwrite(
        STDERR,
        "[ERRO] A configuracao inicial foi bloqueada porque ja existe profissional cadastrado.\n"
    );
    exit(1);
}

$stmt = $pdo->prepare(
    'INSERT INTO profissionais (
        nome,
        email,
        senha_hash,
        telefone,
        status,
        senha_alterada_em
    ) VALUES (
        :nome,
        :email,
        :senha_hash,
        :telefone,
        :status,
        NOW()
    )'
);

$stmt->execute([
    'nome' => trim($nome),
    'email' => $email,
    'senha_hash' => $hash,
    'telefone' => $telefone !== '' ? trim($telefone) : null,
    'status' => 'ATIVO',
]);

$id = (int) $pdo->lastInsertId();

echo "[OK] Profissional inicial cadastrado com sucesso.\n";
echo "ID: {$id}\n";
echo "Nome: " . trim($nome) . "\n";
echo "E-mail: {$email}\n";
echo "Status: ATIVO\n";
echo "A senha foi armazenada somente como hash.\n";

function envValue(string $name): string
{
    $value = $_SERVER[$name]
        ?? $_ENV[$name]
        ?? getenv($name);

    if ($value === false || $value === null) {
        return '';
    }

    return trim((string) $value);
}
