<?php

declare(strict_types=1);

use App\Service\MailService;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$recipient = trim((string) ($argv[1] ?? ''));

if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Uso: php bin/check-smtp.php seu-email@exemplo.com\n");
    exit(1);
}

$required = [
    'SMTP_HOST',
    'SMTP_PORT',
    'SMTP_USERNAME',
    'SMTP_PASSWORD',
    'MAIL_FROM_EMAIL',
];

foreach ($required as $key) {
    if (trim((string) ($_ENV[$key] ?? '')) === '') {
        fwrite(STDERR, "[ERRO] {$key} nao foi configurado no .env.\n");
        exit(1);
    }
}

echo "Host: " . ($_ENV['SMTP_HOST'] ?? '') . "\n";
echo "Porta: " . ($_ENV['SMTP_PORT'] ?? '') . "\n";
echo "Login SMTP: configurado\n";
echo "Chave SMTP: configurada\n";
echo "Remetente: " . ($_ENV['MAIL_FROM_EMAIL'] ?? '') . "\n";
echo "Criptografia explicita: "
    . (trim((string) ($_ENV['SMTP_ENCRYPTION'] ?? '')) ?: '[automatica]')
    . "\n";
echo "Destino de teste: {$recipient}\n";
echo "Enviando...\n";

try {
    (new MailService())->sendParticipantAccessLinks(
        $recipient,
        'Teste SMTP - Avaliacao de Percepcao Relacional',
        [
            'name' => 'Participante A',
            'link' => 'http://localhost:5173/avaliacao/acesso/teste-a',
        ],
        [
            'name' => 'Participante B',
            'link' => 'http://localhost:5173/avaliacao/acesso/teste-b',
        ]
    );

    echo "[OK] E-mail enviado com sucesso pelo SMTP.\n";
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, "[ERRO] Falha SMTP: " . $error->getMessage() . "\n");
    exit(1);
}
