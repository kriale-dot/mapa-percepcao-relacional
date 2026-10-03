# Avaliação de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-03  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.2.0-dev`  
**Marco atual:** estrutura de autenticação profissional criada e validada localmente com sucesso  
**Etapa atual:** Etapa 3.5 concluída — login e sessão no frontend profissional  
**Próximo passo:** retomar a validação adiada da Etapa 3.4 e, em seguida, encerrar a Etapa 3 de autenticação

## 1. Situação atual

A fundação técnica está concluída. A modelagem funcional foi revisada, a primeira migração do domínio foi aplicada em banco recriado do zero e a estrutura foi validada localmente com `composer check-domain`.

Documentos principais:

```text
docs/MAPA_RELACIONAL_CONTEXT.md
docs/MAPA_RELACIONAL_STATUS.md
docs/DECISOES.md
docs/REQUISITOS_SISTEMA.md
docs/MODELO_DOMINIO_V1.md
```

## 1.1 Padronização estrutural no GitHub

Em 2026-10-03 a estrutura do repositório foi alinhada ao padrão de continuidade usado no desenvolvimento:

- criada `docs/DECISOES.md`;
- migrações movidas de `mapa-relacional-api/database/` para `mapa-relacional-api/migrations/`;
- runner `bin/migrate.php` atualizado para o novo diretório;
- criado `mapa-relacional-web/public/`;
- `.gitignore` corrigido para ignorar dependências e artefatos dentro dos subprojetos;
- README raiz atualizado com a estrutura oficial.

`.env` e `vendor/` continuam intencionalmente fora do GitHub. O primeiro é local e pode conter configuração sensível; o segundo é gerado por `composer install`.

A mudança de pasta da migração mantém o mesmo arquivo `001_base_dominio.sql`, portanto o nome registrado em `schema_migrations` continua válido. A validação local foi concluída com sucesso em 2026-10-03.

## 1.2 Documento de requisitos

Em 2026-10-03 foi criado:

```text
docs/REQUISITOS_SISTEMA.md
```

O documento consolida:

- escopo do produto;
- atores;
- requisitos funcionais;
- regras de negócio;
- requisitos não funcionais;
- fluxos principais;
- critérios gerais de aceitação da V1;
- itens fora de escopo;
- decisões ainda abertas;
- priorização sugerida das próximas etapas.

A Etapa 3.1 foi concluída. A Etapa 3.2 está em validação com o primeiro profissional já existente no banco de desenvolvimento.

## 2. Etapa 1 — concluída

Validado localmente:

- Slim 4 / PHP 8.2+;
- MySQL/PDO;
- runner de migrações;
- React 19;
- Vite 8;
- Tailwind CSS v4;
- frontend ↔ API;
- `composer check`;
- `npm run build`.

## 3. Requisitos operacionais incorporados na Etapa 2

O modelo agora contempla explicitamente:

- e-mail de contato por aplicação;
- dois acessos individuais, um por participante;
- tokens/códigos armazenados somente em hash;
- identificação do participante no preenchimento;
- duração do vínculo como snapshot da aplicação;
- duas perspectivas por item: sobre si e sobre o outro;
- exclusão global do item quando marcado “Não se aplica”;
- item excluído fora do cálculo e oculto do outro participante quando ainda não respondido;
- possibilidade de vários instrumentos/avaliações criados pelo profissional;
- resultado e comentário profissional em etapas posteriores.

## 4. Primeira migração criada

Arquivo:

```text
mapa-relacional-api/migrations/001_base_dominio.sql
```

Tabelas previstas pela migração:

1. `profissionais`;
2. `pessoas`;
3. `vinculos`;
4. `instrumentos`;
5. `instrumento_versoes`;
6. `secoes`;
7. `itens`;
8. `alternativas`;
9. `aplicacoes`;
10. `aplicacao_participantes`;
11. `acessos_aplicacao`;
12. `aplicacao_itens_excluidos`;
13. `respostas`.

O `vinculo_id` da aplicação pode ser nulo inicialmente, permitindo gerar os dois acessos antes de os participantes terem preenchido sua identificação.

## 5. Correção do runner de migrações

O arquivo:

```text
mapa-relacional-api/bin/migrate.php
```

foi ajustado antes da primeira migração DDL real.

Motivo:

- MySQL executa commit implícito em comandos DDL;
- a versão anterior envolvia o arquivo SQL em transação PDO;
- isso poderia causar comportamento incorreto ao criar tabelas.

O runner agora divide e executa os comandos SQL individualmente, registra a migração somente após sucesso completo e mantém verificação por checksum SHA-256.

## 6. Validação local da migração

A migração `001_base_dominio.sql` foi executada com sucesso em 2026-10-02 após a correção de compatibilidade dos `CHECK`.

Resultado confirmado:

```text
[ok] 001_base_dominio.sql
Migracoes aplicadas nesta execucao: 1
```

Foi adicionado também:

```text
bin/check-domain.php
composer check-domain
```

Esse comando verifica automaticamente:

- conexão com o banco configurado;
- presença de `schema_migrations`;
- presença das 13 tabelas funcionais;
- registro de `001_base_dominio.sql` em `schema_migrations`.

Executar após `git pull`:

```powershell
composer check-domain
```

A primeira migração funcional é considerada validada.


## 6.1 Correção da primeira execução da migração

Na primeira tentativa local de `001_base_dominio.sql`, o banco retornou:

```text
SQLSTATE[HY000]: General error: 1901
Function or expression 'pessoa_a_id' cannot be used in the CHECK clause
```

A migração não foi registrada em `schema_migrations`.

Correção aplicada em 2026-10-02:

- removido `CHECK (pessoa_a_id <> pessoa_b_id)`;
- removido o `CHECK` de limite de idade do participante;
- essas regras passam a ser validadas no backend para manter compatibilidade com o banco local;
- como as tabelas usam `CREATE TABLE IF NOT EXISTS`, uma nova execução preserva tabelas já criadas antes da falha e continua a partir das ausentes.

## 6.2 Recriação e validação limpa do banco

Em 2026-10-03 o banco `mapa_relacional` foi recriado para validar o fluxo desde zero.

Fluxo adotado:

```text
criar banco vazio
→ composer migrate
→ schema_migrations criada pelo runner
→ 001_base_dominio.sql aplicada
→ composer check-domain
```

A validação final foi confirmada com sucesso.

Estrutura esperada e validada:

- `schema_migrations`;
- 13 tabelas funcionais do domínio;
- registro de `001_base_dominio.sql` em `schema_migrations`.

Durante a validação foi corrigido `bin/check-domain.php` para usar `PDO::FETCH_COLUMN`, evitando dependência da capitalização de `table_name` retornada pelo driver MySQL/PDO.

Commit da correção final do validador:

```text
dcdcf340b78c58a41ddc90a67c191acc90b35425
```

A Etapa 2 é considerada concluída.

## 7. Próximos passos após validar a migração

1. implementar autenticação do profissional;
2. criar endpoint de configuração/cadastro inicial do profissional;
3. criar CRUD de instrumentos, versões, seções e itens;
4. criar fluxo de nova aplicação com e-mail de contato;
5. gerar os dois slots A/B e os dois acessos;
6. iniciar o formulário digital;
7. implementar comparação e resultado em migração posterior.

## 8. Ainda não implementado

- autenticação profissional;
- CRUD funcional;
- envio real de e-mail;
- geração/entrega dos códigos de acesso;
- formulário de avaliação;
- comparação automática;
- resultados;
- barras de resultado;
- comentários profissionais;
- envio de resultado aos participantes;
- relatórios;
- auditoria;
- deploy.

## 9. Regra para atualizar este STATUS

Ao concluir um marco ou correção, registrar:

- data;
- versão;
- etapa;
- objetivo;
- arquivos principais alterados;
- migração, se houver;
- variáveis de ambiente novas/alteradas;
- testes executados;
- resultado;
- pendências;
- próximo passo.

O STATUS deve continuar curto o suficiente para ser lido rapidamente no início de uma nova conversa.


## Correções do check-domain em 2026-10-03

O validador apresentou incompatibilidade de capitalização no retorno de `information_schema.tables`.

Primeira correção:
- troca de `TABLE_NAME` para `table_name`.

Correção definitiva:
- uso de `PDO::FETCH_COLUMN` para ler diretamente os nomes das tabelas;
- remoção do aviso `use PDO has no effect`;
- validação final concluída com sucesso.

Commit final da correção:
`dcdcf340b78c58a41ddc90a67c191acc90b35425`.

## 10. Nomenclatura oficial atualizada em 2026-10-03

A denominação oficial do produto e do instrumento passou a ser **Avaliação de Percepção Relacional**.

A atualização foi refletida na documentação principal e nos textos de identificação atualmente existentes no frontend e na API. Os nomes técnicos do repositório, pastas e componentes internos permanecem inalterados neste momento para evitar renomeações sem benefício funcional.

A Etapa 3 ainda não foi iniciada. O próximo trabalho de implementação é a **Etapa 3.1 — estrutura de autenticação do profissional no banco de dados**.

## 11. Ambiente local sincronizado

Em 2026-10-03 o ambiente de desenvolvimento local foi alinhado ao repositório oficial do GitHub.

Raiz local oficial:

```text
E:\\Compartilhar\\Kriale\\Tânia - plataforma digital\\Desenvolvimento
```

Estado confirmado antes desta atualização:

- branch local: `main`;
- remoto: `origin = https://github.com/kriale-dot/mapa-percepcao-relacional.git`;
- árvore de trabalho: limpa;
- branch local sincronizada com `origin/main`;
- o repositório está clonado diretamente na pasta `Desenvolvimento`, sem subpasta intermediária do projeto.

Após qualquer alteração feita diretamente no GitHub durante o desenvolvimento assistido, o ambiente local deve ser atualizado com `git pull` antes de continuar modificações locais.

## 12. Etapa 3.1 — autenticação profissional

Implementação criada no GitHub em 2026-10-03.

Arquivos principais alterados:

- `mapa-relacional-api/migrations/002_profissional_autenticacao.sql`;
- `mapa-relacional-api/bin/check-domain.php`;
- `docs/MODELO_DOMINIO_V1.md`;
- `docs/MAPA_RELACIONAL_CONTEXT.md`;
- `docs/DECISOES.md`.

A migration 002 adiciona à tabela `profissionais`:

- `senha_hash`;
- `senha_alterada_em`;
- `ultimo_login_em`.

O `check-domain.php` passou a validar também:

- registro de `001_base_dominio.sql`;
- registro de `002_profissional_autenticacao.sql`;
- presença das três colunas de autenticação em `profissionais`.

### Validação local concluída

Em 2026-10-03 a estrutura foi validada com sucesso no ambiente local.

Foram executados sem erros:

```powershell
composer migrate
composer check-domain
composer check
```

Resultado confirmado:

- banco `mapa_relacional` criado;
- `001_base_dominio.sql` aplicada;
- `002_profissional_autenticacao.sql` aplicada;
- estrutura base do domínio validada;
- colunas de autenticação da tabela `profissionais` validadas;
- verificação sintática da API concluída sem erros.

**Etapa 3.1 concluída.**

**Próxima subetapa:** Etapa 3.2 — configuração/cadastro inicial do profissional.

## 13. Etapa 3.2 — configuração inicial do profissional

Implementação de suporte criada no GitHub em 2026-10-03.

Arquivos principais:

- `mapa-relacional-api/bin/setup-professional.php`;
- `mapa-relacional-api/bin/check-professional.php`;
- `mapa-relacional-api/composer.json`.

Comandos disponíveis:

```text
composer setup-professional
composer check-professional
```

### Estado local atual

O primeiro profissional **já está cadastrado diretamente na tabela `profissionais` do banco de desenvolvimento**. Portanto:

- não deve ser executado novo cadastro inicial enquanto esse registro existir;
- o utilitário `setup-professional` permanece como ferramenta auxiliar, mas não é necessário para o estado local atual;
- não existe endpoint público de cadastro do profissional;
- a validação deve ser feita sobre o registro existente.

Para estar apto ao login, o registro existente deve possuir:

- e-mail válido;
- `senha_hash` compatível com `password_verify()`;
- `status = ATIVO`;
- `senha_alterada_em` preenchido;
- `ultimo_login_em` pode permanecer `NULL` até o primeiro login válido.

### Validação local concluída

O profissional existente foi validado com sucesso por `composer check-professional` em 2026-10-03.

**Etapa 3.2 concluída.**

**Próxima subetapa:** Etapa 3.3 — login da API e emissão de JWT.

## 14. Etapa 3.3 — login da API e emissão de JWT

Implementação criada no GitHub em 2026-10-03, consultando somente os arquivos necessários ao fluxo de autenticação.

Arquivos principais:

- `mapa-relacional-api/src/Controller/AuthController.php`;
- `mapa-relacional-api/src/Service/JwtService.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`.

Endpoint criado:

```text
POST /api/auth/login
```

Corpo aceito:

```json
{
  "email": "profissional@exemplo.com",
  "senha": "senha-do-profissional"
}
```

O backend:

- valida e-mail e senha;
- busca o profissional pelo e-mail;
- exige `status = ATIVO`;
- valida a senha com `password_verify()`;
- atualiza `ultimo_login_em` após autenticação válida;
- refaz o hash automaticamente quando `password_needs_rehash()` indicar necessidade;
- emite JWT HS256 usando `JWT_SECRET` e `JWT_TTL_SECONDS`;
- retorna o token sem expor `senha_hash`;
- usa resposta genérica para credenciais inválidas.

Claims atuais do JWT profissional:

- `iss`;
- `sub` = ID do profissional;
- `iat`;
- `nbf`;
- `exp`;
- `type = professional`;
- `email`.

### Validação local concluída

Em 2026-10-03 a Etapa 3.3 foi validada com sucesso no ambiente local.

Foram confirmados sem erros:

- `composer check-professional`;
- `composer check`;
- inicialização da API;
- autenticação com credenciais válidas;
- emissão do JWT profissional;
- retorno dos dados do profissional sem exposição de `senha_hash`;
- comportamento de credenciais inválidas.

**Etapa 3.3 concluída.**

**Próxima subetapa:** Etapa 3.4 — middleware JWT e proteção das rotas profissionais.

## 15. Etapa 3.4 — middleware JWT e proteção das rotas profissionais

Implementação criada no GitHub em 2026-10-03.

Arquivos principais:

- `mapa-relacional-api/src/Middleware/ProfessionalAuthMiddleware.php`;
- `mapa-relacional-api/src/Service/JwtService.php`;
- `mapa-relacional-api/src/Controller/AuthController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`.

Foi criado o grupo protegido:

```text
/api/profissional/*
```

Endpoint inicial de validação da sessão:

```text
GET /api/profissional/me
```

O middleware:

- exige cabeçalho `Authorization: Bearer <token>`;
- valida assinatura HS256 e expiração pelo `firebase/php-jwt`;
- valida `iss`, `type = professional` e `sub`;
- consulta o profissional no banco em cada requisição protegida;
- exige `status = ATIVO`;
- injeta os dados autenticados no atributo `auth.professional` da requisição;
- responde `401` quando o token está ausente, inválido ou expirado.

### Validação local adiada

A implementação da Etapa 3.4 está concluída no código, mas a validação completa foi adiada temporariamente a pedido do usuário.

Ainda precisam ser confirmados posteriormente em `GET /api/profissional/me`:

1. sem token — retorno `401`;
2. com token inválido — retorno `401`;
3. com token válido — retorno `200` com os dados do profissional autenticado.

O `JWT_SECRET` local já foi configurado com valor válido e o login com emissão de JWT foi confirmado. Permanecem adiados apenas os três testes específicos da rota protegida.

**Próxima subetapa em andamento:** Etapa 3.5 — sessão/autenticação no frontend profissional.

## 16. Etapa 3.5 — login e sessão no frontend profissional

Implementação criada no GitHub em 2026-10-03, consultando somente os arquivos necessários do frontend.

Arquivos principais:

- `mapa-relacional-web/src/App.jsx`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/services/auth.js`.

Implementado:

- tela de login em `/profissional/login`;
- envio de e-mail e senha para `POST /api/auth/login`;
- armazenamento do JWT em `sessionStorage`;
- inclusão automática de `Authorization: Bearer <token>` nas chamadas autenticadas;
- validação da sessão por `GET /api/profissional/me`;
- redirecionamento ao login quando não há token ou quando a API retorna `401`;
- área profissional inicial em `/profissional`;
- logout local com remoção do JWT;
- botão da página pública direcionando para a área profissional.

### Validação local concluída

Em 2026-10-03 a Etapa 3.5 foi validada com sucesso no ambiente local.

Foram confirmados:

- frontend iniciado com sucesso;
- acesso à tela profissional;
- login com credenciais válidas;
- entrada em `/profissional`;
- sessão autenticada validada pela API;
- dados do profissional exibidos corretamente;
- recarga da página mantendo a sessão;
- logout retornando ao login;
- credenciais inválidas exibindo erro sem liberar acesso.

**Etapa 3.5 concluída.**

**Próximo passo:** retomar os três testes adiados da Etapa 3.4 e, depois, encerrar oficialmente a Etapa 3 de autenticação.
