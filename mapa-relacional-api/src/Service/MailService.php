<?php

declare(strict_types=1);

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class MailService
{
    /**
     * @param array{name:string,link:string} $participantA
     * @param array{name:string,link:string} $participantB
     */
    public function sendParticipantAccessLinks(
        string $email,
        string $evaluationName,
        array $participantA,
        array $participantB
    ): void {
        $host = trim((string) ($_ENV['SMTP_HOST'] ?? ''));
        $port = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $username = trim((string) ($_ENV['SMTP_USERNAME'] ?? ''));
        $password = (string) ($_ENV['SMTP_PASSWORD'] ?? '');
        $encryption = strtolower(trim(
            (string) ($_ENV['SMTP_ENCRYPTION'] ?? '')
        ));
        $fromEmail = trim((string) ($_ENV['MAIL_FROM_EMAIL'] ?? ''));
        $fromName = trim((string) (
            $_ENV['MAIL_FROM_NAME']
                ?? 'Avaliação de Percepção Relacional'
        ));
        $timeout = max(
            5,
            (int) ($_ENV['SMTP_TIMEOUT_SECONDS'] ?? 15)
        );

        if (
            $host === ''
            || $port <= 0
            || $username === ''
            || $password === ''
            || $fromEmail === ''
        ) {
            throw new RuntimeException(
                'Configuracao SMTP incompleta.'
            );
        }

        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException(
                'MAIL_FROM_EMAIL invalido.'
            );
        }

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Timeout = $timeout;

        if ($encryption === 'ssl' || $encryption === 'smtps') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === '' || $encryption === 'auto') {
            // Para Brevo na porta 587, deixe o PHPMailer negociar STARTTLS
            // automaticamente quando o servidor anunciar suporte.
            $mail->SMTPSecure = '';
        } elseif ($encryption === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        } else {
            throw new RuntimeException(
                'SMTP_ENCRYPTION invalido.'
            );
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Seus acessos - ' . $evaluationName;

        $safeEvaluation = htmlspecialchars(
            $evaluationName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeNameA = htmlspecialchars(
            $participantA['name'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeNameB = htmlspecialchars(
            $participantB['name'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeLinkA = htmlspecialchars(
            $participantA['link'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeLinkB = htmlspecialchars(
            $participantB['link'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->Body = <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Seus acessos</title>
</head>
<body style="font-family:Arial,sans-serif;color:#385048;line-height:1.6">
  <h2 style="margin-bottom:8px">{$safeEvaluation}</h2>
  <p>
    A avaliação foi registrada com dois acessos individuais.
    Cada participante deve usar apenas o seu próprio link.
  </p>

  <div style="margin:24px 0;padding:18px;border:1px solid #A8C8B8;border-radius:12px">
    <strong>Participante A — {$safeNameA}</strong><br>
    <a href="{$safeLinkA}">Acessar avaliação como participante A</a>
  </div>

  <div style="margin:24px 0;padding:18px;border:1px solid #A8C8B8;border-radius:12px">
    <strong>Participante B — {$safeNameB}</strong><br>
    <a href="{$safeLinkB}">Acessar avaliação como participante B</a>
  </div>

  <p>
    Guarde este e-mail até que os dois participantes concluam suas respostas.
  </p>
</body>
</html>
HTML;

        $mail->AltBody =
            $evaluationName . PHP_EOL . PHP_EOL
            . 'Participante A - ' . $participantA['name'] . PHP_EOL
            . $participantA['link'] . PHP_EOL . PHP_EOL
            . 'Participante B - ' . $participantB['name'] . PHP_EOL
            . $participantB['link'] . PHP_EOL . PHP_EOL
            . 'Cada participante deve usar apenas o seu proprio link.';

        if (!$mail->send()) {
            throw new RuntimeException(
                'Falha ao enviar e-mail de acesso.'
            );
        }
    }
}
