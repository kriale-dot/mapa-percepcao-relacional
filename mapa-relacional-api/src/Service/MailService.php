<?php

declare(strict_types=1);

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class MailService
{
    public function sendProfessionalPasswordReset(string $email, string $name, string $link): void
    {
        $host = trim((string) ($_ENV['SMTP_HOST'] ?? ''));
        $username = trim((string) ($_ENV['SMTP_USERNAME'] ?? ''));
        $password = (string) ($_ENV['SMTP_PASSWORD'] ?? '');
        $sender = trim((string) ($_ENV['MAIL_FROM_EMAIL'] ?? ''));
        $senderName = (string) ($_ENV['MAIL_FROM_NAME'] ?? 'Avaliação de Percepção Relacional');
        if ($host === '' || $username === '' || $password === '' || !filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Configuracao SMTP incompleta.');
        }
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Timeout = max(5, (int) ($_ENV['SMTP_TIMEOUT_SECONDS'] ?? 15));
        $encryption = strtolower(trim((string) ($_ENV['SMTP_ENCRYPTION'] ?? '')));
        if (in_array($encryption, ['ssl', 'smtps'], true)) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (in_array($encryption, ['tls', 'starttls'], true)) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (in_array($encryption, ['', 'auto'], true)) {
            $mail->SMTPSecure = '';
        } elseif ($encryption === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        } else {
            throw new RuntimeException('SMTP_ENCRYPTION invalido.');
        }
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mail->setFrom($sender, $senderName);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de senha - Avaliação de Percepção Relacional';
        $mail->Body = '<div style="font-family:Arial,sans-serif;color:#385048;line-height:1.6">'
            . '<h2>Redefinição de senha</h2><p>Olá, ' . $safeName . '.</p>'
            . '<p>Recebemos um pedido para redefinir a senha da sua área profissional.</p>'
            . '<p><a href="' . $safeLink . '">Definir uma nova senha</a></p>'
            . '<p>O link expira em 30 minutos e só pode ser utilizado uma vez.</p>'
            . '<p>Se você não solicitou a alteração, ignore esta mensagem.</p></div>';
        $mail->AltBody = 'Olá, ' . $name . ".\n\n" . 'Para redefinir sua senha, abra o link: '
            . $link . "\n\n" . 'Validade: 30 minutos. Se não solicitou, ignore este e-mail.';
        if (!$mail->send()) {
            throw new RuntimeException('Falha ao enviar email de redefinicao.');
        }
    }

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

    public function sendSingleParticipantAccessLink(
        string $email,
        string $evaluationName,
        string $side,
        string $participantName,
        string $link
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
            $mail->SMTPSecure = '';
        } elseif ($encryption === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        } else {
            throw new RuntimeException(
                'SMTP_ENCRYPTION invalido.'
            );
        }

        $safeEvaluation = htmlspecialchars(
            $evaluationName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeSide = htmlspecialchars(
            $side,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeName = htmlspecialchars(
            $participantName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeLink = htmlspecialchars(
            $link,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Novo acesso - ' . $evaluationName;

        $mail->Body = <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Novo acesso</title>
</head>
<body style="font-family:Arial,sans-serif;color:#385048;line-height:1.6">
  <h2 style="margin-bottom:8px">{$safeEvaluation}</h2>
  <p>
    Foi gerado um novo link de acesso para o participante {$safeSide}.
    O link anterior desse participante deixou de ser válido.
  </p>

  <div style="margin:24px 0;padding:18px;border:1px solid #A8C8B8;border-radius:12px">
    <strong>Participante {$safeSide} — {$safeName}</strong><br>
    <a href="{$safeLink}">Acessar avaliação</a>
  </div>
</body>
</html>
HTML;

        $mail->AltBody =
            $evaluationName . PHP_EOL . PHP_EOL
            . 'Participante ' . $side . ' - ' . $participantName . PHP_EOL
            . 'Novo acesso: ' . $link . PHP_EOL . PHP_EOL
            . 'O link anterior deste participante deixou de ser valido.';

        if (!$mail->send()) {
            throw new RuntimeException(
                'Falha ao reenviar acesso do participante.'
            );
        }
    }

    public function sendResultRelease(
        string $email,
        string $evaluationName,
        string $participantAName,
        string $participantBName,
        string $link
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
        $mail->Subject = 'Resultado disponível - ' . $evaluationName;

        $safeEvaluation = htmlspecialchars(
            $evaluationName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeParticipantA = htmlspecialchars(
            $participantAName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeParticipantB = htmlspecialchars(
            $participantBName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeLink = htmlspecialchars(
            $link,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->Body = <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Resultado disponível</title>
</head>
<body style="font-family:Arial,sans-serif;color:#385048;line-height:1.6">
  <h2 style="margin-bottom:8px">{$safeEvaluation}</h2>
  <p>
    A devolutiva da avaliação de {$safeParticipantA} e {$safeParticipantB}
    foi liberada pelo profissional responsável.
  </p>

  <div style="margin:24px 0;padding:18px;border:1px solid #A8C8B8;border-radius:12px">
    <strong>Resultado disponível</strong><br>
    <a href="{$safeLink}">Acessar devolutiva da avaliação</a>
  </div>

  <p>
    O link apresenta os resultados comparativos liberados e o conteúdo
    registrado pelo profissional.
  </p>
</body>
</html>
HTML;

        $mail->AltBody =
            $evaluationName . PHP_EOL . PHP_EOL
            . 'A devolutiva da avaliacao de '
            . $participantAName . ' e ' . $participantBName
            . ' foi liberada.' . PHP_EOL . PHP_EOL
            . $link;

        if (!$mail->send()) {
            throw new RuntimeException(
                'Falha ao enviar e-mail da devolutiva.'
            );
        }
    }


    public function sendAutomaticResultSummary(
        string $email,
        string $evaluationName,
        string $participantAName,
        string $participantBName,
        ?float $participantAPercentage,
        ?float $participantBPercentage,
        float $generalPercentage,
        string $resultTitle,
        string $resultText,
        string $pdfContent,
        string $pdfFilename
    ): void {
        if ($pdfContent === '' || trim($pdfFilename) === '') {
            throw new RuntimeException(
                'Documento PDF do resultado automatico indisponivel.'
            );
        }

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

        if (
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new RuntimeException(
                'Endereco de e-mail invalido.'
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
            $mail->SMTPSecure = '';
        } elseif ($encryption === 'none') {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = '';
        } else {
            throw new RuntimeException(
                'SMTP_ENCRYPTION invalido.'
            );
        }

        $safeEvaluation = htmlspecialchars(
            $evaluationName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeParticipantA = htmlspecialchars(
            $participantAName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeParticipantB = htmlspecialchars(
            $participantBName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeScoreA = htmlspecialchars(
            $this->formatPercentage($participantAPercentage),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeScoreB = htmlspecialchars(
            $this->formatPercentage($participantBPercentage),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeGeneralScore = htmlspecialchars(
            $this->formatPercentage($generalPercentage),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeResultTitle = htmlspecialchars(
            $resultTitle,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $safeResultText = htmlspecialchars(
            $resultText,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->addStringAttachment(
            $pdfContent,
            $pdfFilename,
            'base64',
            'application/pdf'
        );
        $mail->isHTML(true);
        $mail->Subject = 'Resultado automático - ' . $evaluationName;

        $mail->Body = <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Resultado automático</title>
</head>
<body style="font-family:Arial,sans-serif;color:#385048;line-height:1.6">
  <h2 style="margin-bottom:8px">{$safeEvaluation}</h2>

  <p>
    Os dois participantes concluíram a avaliação e a plataforma calculou
    automaticamente os scores de percepção.
  </p>

  <div style="margin:24px 0;padding:18px;border:1px solid #A8C8B8;border-radius:12px">
    <p style="margin:0 0 8px">
      <strong>Score de {$safeParticipantA}:</strong> {$safeScoreA}
    </p>
    <p style="margin:0 0 8px">
      <strong>Score de {$safeParticipantB}:</strong> {$safeScoreB}
    </p>
    <p style="margin:0">
      <strong>Score total da avaliação:</strong> {$safeGeneralScore}
    </p>
  </div>

  <div style="margin:24px 0;padding:18px;background:#FEFDFB;border:1px solid #D8B078;border-radius:12px">
    <h3 style="margin-top:0">{$safeResultTitle}</h3>
    <p style="margin-bottom:0">{$safeResultText}</p>
  </div>

  <p>
    O documento PDF anexado apresenta a avaliação completa, incluindo
    score geral, scores individuais, resultados por tópico e a comparação
    item a item.
  </p>

  <p>
    Este é um resultado automático baseado na comparação das respostas.
    Ele não substitui a devolutiva do profissional, que poderá ser enviada
    separadamente após a análise da avaliação.
  </p>
</body>
</html>
HTML;

        $mail->AltBody =
            $evaluationName . PHP_EOL . PHP_EOL
            . 'Os dois participantes concluíram a avaliação.' . PHP_EOL . PHP_EOL
            . 'Score de ' . $participantAName . ': '
            . $this->formatPercentage($participantAPercentage) . PHP_EOL
            . 'Score de ' . $participantBName . ': '
            . $this->formatPercentage($participantBPercentage) . PHP_EOL
            . 'Score total da avaliação: '
            . $this->formatPercentage($generalPercentage) . PHP_EOL . PHP_EOL
            . $resultTitle . PHP_EOL
            . $resultText . PHP_EOL . PHP_EOL
            . 'O PDF anexado apresenta a avaliação completa, incluindo a comparação item a item.' . PHP_EOL . PHP_EOL
            . 'Este é um resultado automático e não substitui a devolutiva do profissional.';

        if (!$mail->send()) {
            throw new RuntimeException(
                'Falha ao enviar e-mail do resultado automatico.'
            );
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    public function automaticResultNarrative(float $percentage): array
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new RuntimeException(
                'Percentual geral invalido para resultado automatico.'
            );
        }

        if ($percentage >= 80) {
            return [
                'Uma percepção compartilhada muito positiva',
                'Parabéns! Vocês demonstram uma ótima sintonia na percepção do relacionamento. Que tal aproveitar essa sintonia para fortalecer ainda mais os aspectos que já fazem bem a vocês e dedicar atenção especial às áreas que merecem mais cuidado?',
            ];
        }

        if ($percentage >= 60) {
            return [
                'Uma boa compreensão, com espaço para aprofundar o diálogo',
                'Vocês apresentam uma boa percepção um do outro. Pequenos investimentos na comunicação verbal e não verbal podem ajudar a esclarecer expectativas, expressar sentimentos e compreender melhor aquilo que nem sempre é dito com palavras.',
            ];
        }

        if ($percentage >= 40) {
            return [
                'Uma oportunidade de se conhecerem melhor',
                'Talvez existam percepções diferentes, dúvidas ou aspectos do cotidiano que ainda não receberam a devida atenção. Este é um convite para observar com mais carinho as reações um do outro e conversar com mais transparência sobre o que cada um sente, pensa e espera.',
            ];
        }

        if ($percentage >= 20) {
            return [
                'Um convite à redescoberta',
                'Vocês podem estar diante de uma oportunidade valiosa de redescobrir um ao outro. Para quem está junto há muitos anos, pode ser o momento de renovar a curiosidade e olhar para o parceiro para além das imagens construídas ao longo do tempo. Para quem está no início da união, é uma oportunidade de continuar descobrindo as particularidades, os valores e as expectativas de cada um.',
            ];
        }

        return [
            'Um caminho para construir maior compreensão',
            'O resultado sugere que vocês ainda podem ampliar bastante o conhecimento sobre a maneira como cada um percebe o relacionamento. Isso não define a qualidade ou o futuro da união. É um ponto de partida para investir no diálogo, na convivência e na descoberta mútua, respeitando o tempo e a história de vocês.',
        ];
    }

    private function formatPercentage(?float $percentage): string
    {
        if ($percentage === null) {
            return 'Não disponível';
        }

        return number_format($percentage, 2, ',', '.') . '%';
    }

}
