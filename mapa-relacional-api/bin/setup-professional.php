<?php

declare(strict_types=1);

use App\Config\Database;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$password = (string) ($_SERVER['SETUP_PROFESSIONAL_PASSWORD']
    ?? $_ENV['SETUP_PROFESSIONAL_PASSWORD']
    ?? getenv('SETUP_PROFESSIONAL_PASSWORD')
    ?: '');

if ($password === '') {
    fwrite(STDERR, "[ERRO] SETUP_PROFESSIONAL_PASSWORD nao foi informada.\n");
    fwrite(STDERR, "Defina a senha somente na sessao atual do terminal e execute novamente.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "[ERRO] A senha inicial deve ter pelo menos 8 caracteres.\n");
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

$nome = promptRequired('Nome da profissional: ');
$email = promptRequired('E-mail: ');

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "[ERRO] E-mail invalido.\n");
    exit(1);
}

$telefone = promptOptional('Telefone (opcional): ');

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
    'nome' => $nome,
    'email' => strtolower(trim($email)),
    'senha_hash' => $hash,
    'telefone' => $telefone !== '' ? $telefone : null,
    'status' => 'ATIVO',
]);

$id = (int) $pdo->lastInsertId();

echo "[OK] Profissional inicial cadastrado com sucesso.\n";
echo "ID: {$id}\n";
echo "Nome: {$nome}\n";
echo "E-mail: " . strtolower(trim($email)) . "\n";
echo "Status: ATIVO\n";
echo "A senha foi armazenada somente como hash.\n";

/**
 * @return non-empty-string
 */
function promptRequired(string $label): string
{
    $value = promptOptional($label);

    if ($value === '') {
        fwrite(STDERR, "[ERRO] Campo obrigatorio nao informado.\n");
        exit(1);
    }

    return $value;
}

function promptOptional(string $label): string
{
    fwrite(STDOUT, $label);

    $line = fgets(STDIN);

    if ($line === false) {
        fwrite(STDERR, "[ERRO] Nao foi possivel ler a entrada.\n");
        exit(1);
    }

    return trim($line);
}
