# Produção V1 — Avaliação de Percepção Relacional

Este documento reúne o checklist técnico mínimo para publicar a V1 da plataforma com segurança e rastreabilidade.

## 1. Atualização do código

No servidor de produção:

```bash
git pull
cd mapa-relacional-api
composer install --no-dev --optimize-autoloader
```

No frontend:

```bash
cd mapa-relacional-web
npm ci
npm run build
```

## 2. Banco de dados

Aplicar todas as migrations antes de liberar tráfego:

```bash
cd mapa-relacional-api
composer migrate
composer check-domain
```

A versão candidata atual exige as migrations 001 a 011, incluindo:

- resultados e comparações;
- devolutivas;
- auditoria;
- rate limit;
- site institucional configurável;
- score geral do casal/par;
- biblioteca pública de documentos PDF e PNG.

## 3. Configuração de ambiente

No `.env` de produção:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.taniasantiago.com.br
FRONTEND_URL=https://taniasantiago.com.br
```

O `JWT_SECRET` deve ser longo, aleatório e exclusivo do ambiente de produção.

Credenciais de banco e SMTP ficam somente no `.env`.

Domínios oficiais de produção:

```text
Site público: https://taniasantiago.com.br
API: https://api.taniasantiago.com.br
```

No build do frontend, configurar `VITE_API_URL=https://api.taniasantiago.com.br`.

## 4. SMTP Brevo

Confirmar:

- `SMTP_HOST=smtp-relay.brevo.com`;
- porta configurada;
- login SMTP válido;
- chave SMTP válida;
- remetente verificado;
- envio real para endereço externo.

Nunca usar senha da conta Brevo no lugar da chave SMTP.

## 5. Rate limit

A V1 possui limitação básica persistida em banco para:

- tentativas inválidas de login profissional;
- criação pública de novas avaliações.

Variáveis configuráveis:

```text
LOGIN_RATE_LIMIT_MAX=10
LOGIN_IP_RATE_LIMIT_MAX=30
LOGIN_RATE_LIMIT_WINDOW_SECONDS=900
PUBLIC_START_RATE_LIMIT_MAX=10
PUBLIC_START_EMAIL_RATE_LIMIT_MAX=5
PUBLIC_START_RATE_LIMIT_WINDOW_SECONDS=900
```

Ajustar os números somente depois de observar o uso real.

## 6. Auditoria

A tabela `auditoria_eventos` registra ações relevantes com:

- ator;
- ação;
- entidade;
- data/hora;
- contexto não sensível;
- IP;
- user agent.

Senhas, tokens, JWT e credenciais SMTP não devem ser gravados na auditoria.

A consulta profissional está disponível em:

```text
/profissional/auditoria
```

## 7. Cabeçalhos de segurança

A API adiciona:

- `X-Content-Type-Options: nosniff`;
- `X-Frame-Options: DENY`;
- `Referrer-Policy: no-referrer`;
- `Cache-Control: no-store`.

O CORS continua restrito ao `FRONTEND_URL` configurado.

## 8. Backup

Antes de deploy relevante e antes de migrations:

```bash
composer backup-db
```

O script usa `mysqldump`, grava o arquivo em:

```text
mapa-relacional-api/storage/backups/
```

e informa tamanho e SHA-256.

Se `mysqldump` não estiver no PATH, configurar:

```text
MYSQLDUMP_BIN=/caminho/para/mysqldump
```

Os backups estão ignorados pelo Git e não devem ser versionados.

Além de criar o backup, fazer periodicamente um teste de restauração em banco separado antes de considerar a política de backup validada.

## 8.1 Uploads do site institucional

O servidor precisa permitir gravação em:

```text
mapa-relacional-api/public/uploads/
```

Configuração padrão:

```text
SITE_IMAGE_MAX_MB=5
LIBRARY_FILE_MAX_MB=20
```

A extensão PHP `fileinfo` deve estar habilitada.

Os arquivos enviados pelo usuário ficam fora do Git. Em produção, a pasta de uploads deve fazer parte da estratégia de backup de arquivos, separadamente do backup SQL do banco. Isso inclui tanto imagens institucionais quanto documentos da biblioteca pública.

## 9. Checklist automatizado

Executar:

```bash
composer check-v1
```

Possíveis estados:

- `PRONTO`;
- `PRONTO_COM_AVISOS`;
- `NAO_PRONTO`.

O checklist verifica, entre outros:

- ambiente;
- modo debug;
- URLs;
- HTTPS em produção;
- JWT secret;
- extensões PHP;
- SMTP;
- diretórios de logs e backups;
- conexão MySQL;
- tabelas V1;
- migrations V1.

## 10. Verificação funcional mínima antes de produção

Confirmar pelo menos um fluxo completo:

1. visitante abre o site institucional público e vê os blocos configurados;
2. visitante escolhe uma avaliação pública;
3. informa participantes, e-mail, tipo de vínculo e tempo de união;
4. Brevo envia os dois acessos;
5. A e B abrem seus links;
6. ambos se identificam;
7. respostas são salvas e retomadas;
8. “Não se aplica” funciona globalmente;
9. A e B concluem;
10. resultados são calculados;
11. profissional consulta resultados;
12. profissional prepara e libera devolutiva;
13. e-mail da devolutiva é recebido;
14. link público abre somente o conteúdo liberado;
15. auditoria registra os eventos relevantes;
16. reenvio de acesso invalida o link anterior;
17. reenvio da devolutiva invalida o link anterior;
18. editor do site permite criar, editar, ocultar e reordenar blocos;
19. site institucional responde adequadamente em desktop, tablet e smartphone;
20. visitante abre `/biblioteca` sem autenticação;
21. busca da biblioteca localiza documentos ativos por título/descrição/nome do arquivo;
22. PDF e PNG ativos podem ser abertos pelo visitante;
23. documento INATIVO não aparece publicamente;
24. área profissional publica, edita e exclui documentos da biblioteca.

## 11. Observação de escopo

SMS e WhatsApp permanecem fora da V1. A V1 usa e-mail SMTP Brevo para os fluxos transacionais implementados.
