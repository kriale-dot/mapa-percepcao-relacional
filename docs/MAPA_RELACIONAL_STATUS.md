# Avaliação de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-04  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.2.0-dev`  
**Marco atual:** Etapa 6 em desenvolvimento — autoatendimento público de avaliações  
**Etapa atual:** Etapa 6.2 em validação — acessos individuais e envio SMTP Brevo  
**Próximo passo:** configurar SMTP Brevo no .env e validar geração, envio e abertura dos dois links individuais

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
- tempo de união como snapshot da aplicação;
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

### Validação manual dispensada

A implementação da Etapa 3.4 permanece concluída no código.

Em 2026-10-03, por decisão explícita do usuário, foram dispensados os testes manuais específicos no Postman para:

1. requisição sem token — esperado `401`;
2. requisição com token inválido — esperado `401`;
3. requisição com token válido — esperado `200`.

O fluxo com token válido foi exercitado indiretamente durante a validação da Etapa 3.5, pois o frontend autenticado consultou `GET /api/profissional/me` e exibiu corretamente os dados do profissional.

A ausência dos dois testes negativos específicos fica registrada como validação não executada, sem remoção do middleware nem alteração da proteção implementada.

**Etapa 3.4: implementação concluída; validação manual específica dispensada.**

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

**Próximo passo:** Etapa 3.6 — implementar o perfil profissional conforme RF-003. Depois, implementar a alteração segura de senha (RF-004) antes de encerrar a Etapa 3.

## 17. Etapa 3.6 — perfil profissional

Implementação criada no GitHub em 2026-10-03.

Arquivos principais:

- `mapa-relacional-api/migrations/003_profissional_perfil.sql`;
- `mapa-relacional-api/src/Controller/ProfessionalController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/bin/check-domain.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalProfile.jsx`;
- `mapa-relacional-web/src/App.jsx`.

A migration 003 adiciona ao profissional:

- `descricao`;
- `atuacao`;
- `foto_url`;
- `logo_url`;
- `dados_contato`.

Rotas protegidas adicionadas:

```text
GET /api/profissional/perfil
PUT /api/profissional/perfil
```

No frontend foi criada a rota:

```text
/profissional/perfil
```

O formulário permite editar nome, e-mail, telefone, apresentação, atuação, dados de contato, URL da fotografia e URL do logotipo. O status é exibido como somente leitura.

### Validação local concluída

Em 2026-10-03 a Etapa 3.6 foi validada com sucesso no ambiente local.

Foram confirmados:

- aplicação da migration `003_profissional_perfil.sql`;
- `composer check-domain` sem erros;
- `composer check` sem erros;
- build e execução do frontend;
- acesso ao botão `Meu perfil`;
- carregamento dos dados existentes;
- edição e salvamento do perfil;
- persistência dos dados após recarregar a página;
- retorno à área profissional;
- acesso continuando protegido pela sessão JWT.

**Etapa 3.6 concluída.**

**Próxima subetapa:** Etapa 3.7 — alteração segura de senha (RF-004).

## 18. Etapa 3.7 — alteração segura de senha

Implementação criada no GitHub em 2026-10-03.

Arquivos principais:

- `mapa-relacional-api/src/Controller/AuthController.php`;
- `mapa-relacional-api/src/Service/JwtService.php`;
- `mapa-relacional-api/src/Middleware/ProfessionalAuthMiddleware.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalPassword.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalProfile.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Endpoint protegido:

```text
PUT /api/profissional/senha
```

Corpo:

```json
{
  "senha_atual": "senha-atual",
  "nova_senha": "nova-senha"
}
```

Regras implementadas:

- exige sessão profissional válida;
- exige senha atual correta;
- nova senha com mínimo de 8 caracteres;
- nova senha diferente da atual;
- armazenamento somente com `password_hash(PASSWORD_DEFAULT)`;
- atualização de `senha_alterada_em`;
- JWT vinculado ao hash atual por impressão digital;
- tokens anteriores à alteração deixam de ser aceitos após o hash mudar;
- frontend encerra a sessão depois da troca e solicita novo login.

No frontend foi criada:

```text
/profissional/senha
```

A tela também exige confirmação da nova senha antes do envio.

### Validação local concluída

Em 2026-10-03 a Etapa 3.7 foi validada com sucesso no ambiente local.

Foram confirmados:

- `composer check` sem erros;
- build e execução do frontend;
- novo login após atualização do formato do JWT;
- acesso a `Meu perfil → Alterar senha`;
- rejeição de senha atual incorreta;
- rejeição de nova senha com menos de 8 caracteres;
- alteração para nova senha válida;
- encerramento automático da sessão após a troca;
- rejeição da senha antiga no login;
- autenticação bem-sucedida com a nova senha.

**Etapa 3.7 concluída.**

## 19. Encerramento da Etapa 3 — autenticação profissional

A Etapa 3 foi concluída em 2026-10-03.

Entregas concluídas:

- estrutura de autenticação no banco;
- profissional inicial provisionado e validado;
- login da API;
- emissão de JWT;
- middleware de proteção das rotas profissionais;
- sessão autenticada no frontend;
- perfil profissional;
- alteração segura de senha;
- invalidação de tokens antigos após troca de senha.

A validação manual específica da Etapa 3.4 para token ausente/inválido foi dispensada por decisão do usuário, mas o fluxo autenticado com token válido foi exercitado pelo frontend e permaneceu funcional ao longo das etapas seguintes.

**Etapa 3 concluída.**

**Próxima etapa:** Etapa 4 — CRUD de instrumentos, versões, seções e itens.

## 20. Etapa 4.1 — CRUD de instrumentos

Implementação criada no GitHub em 2026-10-03.

Arquivos principais:

- `mapa-relacional-api/src/Controller/InstrumentController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalInstruments.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/instrumentos
POST   /api/profissional/instrumentos
GET    /api/profissional/instrumentos/{id}
PUT    /api/profissional/instrumentos/{id}
DELETE /api/profissional/instrumentos/{id}
```

Regras implementadas:

- todo instrumento é filtrado pelo profissional autenticado;
- nome obrigatório;
- descrição opcional;
- estados permitidos: `RASCUNHO`, `ATIVO`, `ARQUIVADO`;
- listagem informa também o total de versões;
- exclusão física permitida apenas quando o instrumento ainda não possui versões;
- instrumento com versão deve ser preservado e pode ser arquivado.

No frontend foi criada:

```text
/profissional/instrumentos
```

A área profissional agora permite abrir o módulo pelo card `Instrumentos`.

### Validação local concluída

Em 2026-10-03 a Etapa 4.1 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao módulo `Instrumentos` pela área profissional;
- criação de instrumento em rascunho;
- exibição correta na listagem;
- edição de nome, descrição e status;
- persistência dos dados após recarregar a página;
- exclusão de instrumento sem versões;
- manutenção da proteção por sessão profissional.

Durante a validação ocorreu `ERR_CONNECTION_REFUSED` ao tentar excluir um instrumento, causado pela API local não estar em execução. Após iniciar novamente `composer serve`, a exclusão funcionou normalmente.

**Etapa 4.1 concluída.**

**Próxima subetapa:** Etapa 4.2 — versões do instrumento.

## 21. Etapa 4.2 — versões do instrumento

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/InstrumentVersionController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalInstrumentVersions.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalInstruments.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/instrumentos/{instrumentId}/versoes
POST   /api/profissional/instrumentos/{instrumentId}/versoes
PUT    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}
POST   /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/publicar
POST   /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/arquivar
DELETE /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}
```

Regras implementadas:

- novas versões são criadas como `RASCUNHO`;
- número da versão é obrigatório e único dentro do instrumento;
- somente rascunhos podem ser editados;
- publicação muda o estado para `PUBLICADA` e registra `publicado_em`;
- versões publicadas ficam imutáveis;
- versões publicadas podem ser arquivadas;
- rascunhos só podem ser excluídos se não tiverem seções nem aplicações;
- todas as operações conferem se o instrumento pertence ao profissional autenticado.

No frontend foi criada a rota dinâmica:

```text
/profissional/instrumentos/{id}/versoes
```

O catálogo de instrumentos agora possui o botão `Versões`.

### Validação local concluída

Em 2026-10-04 a Etapa 4.2 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao gerenciamento de versões pelo catálogo de instrumentos;
- criação da versão `1.0` em `RASCUNHO`;
- edição do número de uma versão em rascunho;
- criação e exclusão de rascunho de teste;
- publicação de versão descartável;
- estado `PUBLICADA` após publicação;
- bloqueio de edição e exclusão após publicação;
- arquivamento da versão publicada;
- estado `ARQUIVADA` após arquivamento;
- persistência dos dados após recarregar a página;
- atualização do total de versões no catálogo.

A versão `1.0` foi mantida em `RASCUNHO` para receber a estrutura nas próximas subetapas.

Observação: a V1 adota imutabilidade a partir da publicação, regra deliberadamente mais conservadora que o mínimo do RF-033.

**Etapa 4.2 concluída.**

**Próxima subetapa:** Etapa 4.3 — seções da versão.

## 22. Etapa 4.3 — seções da versão

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/SectionController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalSections.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalInstrumentVersions.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes
POST   /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes
PUT    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}
DELETE /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}
```

Regras implementadas:

- somente versões em `RASCUNHO` permitem alteração estrutural;
- título obrigatório;
- descrição opcional;
- ordem inteira igual ou maior que zero;
- na criação, ordem omitida posiciona a seção automaticamente ao final;
- seção pode ser ativa ou inativa;
- listagem é ordenada por `ordem` e depois por ID;
- exclusão é bloqueada quando a seção já possui itens;
- versões publicadas/arquivadas exibem as seções somente para leitura;
- toda operação valida a propriedade do instrumento pelo profissional autenticado.

No frontend foi criada a rota:

```text
/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes
```

Cada versão agora possui o botão `Seções`.

### Validação local concluída

Em 2026-10-04 a Etapa 4.3 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao módulo de seções pela versão do instrumento;
- criação de múltiplas seções;
- posicionamento automático quando a ordem é omitida;
- edição de título, descrição, ordem e estado ativo/inativo;
- ordenação da listagem conforme o campo `ordem`;
- exclusão de seção sem itens vinculados;
- persistência dos dados após recarregar a página;
- modo somente leitura em versões publicadas ou arquivadas.

Não houve necessidade de migration nova nesta etapa, pois a tabela `secoes` já existe na estrutura base.

**Etapa 4.3 concluída.**

**Próxima subetapa:** Etapa 4.4 — itens/perguntas das seções.

## 23. Etapa 4.4 — itens/perguntas das seções

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/ItemController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalItems.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalSections.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens
POST   /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens
PUT    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}
DELETE /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}
```

Regras implementadas:

- somente versões em `RASCUNHO` permitem alteração dos itens;
- código obrigatório e único dentro da seção;
- texto/pergunta obrigatório;
- tipo de resposta obrigatório, mantido como identificador textual extensível;
- ordem inteira igual ou maior que zero;
- ordem omitida na criação posiciona o item automaticamente ao final;
- configuração individual para permitir ou não “Não se aplica”;
- item pode ser ativo ou inativo;
- listagem ordenada por `ordem` e depois por ID;
- exclusão bloqueada se o item já possuir alternativas ou respostas;
- versões publicadas/arquivadas exibem itens somente para leitura;
- toda operação valida profissional → instrumento → versão → seção.

No frontend foi criada a rota:

```text
/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens
```

Cada seção agora possui o botão `Itens`.

### Validação local concluída

Em 2026-10-04 a Etapa 4.4 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao módulo de itens pela seção;
- criação de múltiplos itens;
- posicionamento automático quando a ordem é omitida;
- edição de código, texto/pergunta, tipo de resposta, ordem, opção “Não se aplica” e estado ativo/inativo;
- rejeição de código duplicado dentro da mesma seção;
- ordenação da listagem conforme o campo `ordem`;
- exclusão de item sem alternativas ou respostas vinculadas;
- persistência dos dados após recarregar a página;
- modo somente leitura em versões publicadas ou arquivadas.

Não houve necessidade de migration nova nesta etapa, pois a tabela `itens` já existe na estrutura base.

**Etapa 4.4 concluída.**

**Próxima subetapa:** Etapa 4.5 — alternativas dos itens.

## 24. Etapa 4.5 — alternativas dos itens

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/AlternativeController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalAlternatives.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalItems.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}/alternativas
POST   /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}/alternativas
PUT    /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}/alternativas/{alternativeId}
DELETE /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}/alternativas/{alternativeId}
```

Regras implementadas:

- somente versões em `RASCUNHO` permitem alteração das alternativas;
- valor obrigatório e único dentro do item;
- rótulo obrigatório;
- ordem inteira igual ou maior que zero;
- ordem omitida na criação posiciona a alternativa automaticamente ao final;
- alternativa pode ser ativa ou inativa;
- listagem ordenada por `ordem` e depois por ID;
- exclusão bloqueada quando a alternativa possui respostas vinculadas;
- versões publicadas/arquivadas exibem alternativas somente para leitura;
- toda operação valida profissional → instrumento → versão → seção → item.

No frontend foi criada a rota:

```text
/profissional/instrumentos/{instrumentId}/versoes/{versionId}/secoes/{sectionId}/itens/{itemId}/alternativas
```

Cada item agora possui o botão `Alternativas`.

### Validação local concluída

Em 2026-10-04 a Etapa 4.5 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao módulo de alternativas pelo item;
- criação de múltiplas alternativas;
- valores `1`, `2` e `3` com rótulos de resposta;
- posicionamento automático quando a ordem é omitida;
- edição de valor, rótulo, ordem e estado ativo/inativo;
- rejeição de valor duplicado dentro do mesmo item;
- ordenação da listagem conforme o campo `ordem`;
- exclusão de alternativa sem respostas vinculadas;
- persistência dos dados após recarregar a página;
- modo somente leitura em versões publicadas ou arquivadas.

Não houve necessidade de migration nova nesta etapa, pois a tabela `alternativas` já existe na estrutura base.

**Etapa 4.5 concluída.**

**Próximo passo:** revisar e fechar a Etapa 4 — estrutura completa de instrumentos.

## 25. Fechamento da Etapa 4

Revisão final realizada em 2026-10-04 com base nos requisitos RF-030 a RF-044 e no modelo de domínio V1.

A Etapa 4 foi concluída com os seguintes blocos funcionais validados:

- instrumentos com nome, descrição, status e isolamento por profissional;
- versões com estados `RASCUNHO`, `PUBLICADA` e `ARQUIVADA`;
- imutabilidade estrutural a partir da publicação;
- seções com título, descrição, ordem e estado ativo/inativo;
- itens com código, texto, tipo de resposta extensível, ordem, “Não se aplica” e estado ativo/inativo;
- alternativas com valor, rótulo, ordem e estado ativo/inativo;
- ordenação automática quando a ordem é omitida na criação;
- preservação histórica por bloqueio de exclusões quando há dependências;
- modo somente leitura para estrutura de versões publicadas/arquivadas;
- validação de propriedade em toda a cadeia profissional → instrumento → versão → seção → item.

Correspondência com requisitos:

- `RF-030` — atendido;
- `RF-031` — versionamento do instrumento atendido; o vínculo da aplicação a uma versão específica será exercitado no módulo de aplicações;
- `RF-032` — atendido;
- `RF-033` — atendido com regra mais conservadora: imutabilidade começa na publicação;
- `RF-040` — atendido;
- `RF-041` — atendido;
- `RF-042` — atendido;
- `RF-043` — atendido por `tipo_resposta` textual extensível;
- `RF-044` — atendido no cadastro do item; o efeito de “Não se aplica” sobre respostas e denominadores será implementado no fluxo de aplicação/cálculo.

Todos os sub-blocos 4.1 a 4.5 foram validados localmente pelo usuário.

### Próxima etapa

A próxima etapa será **Etapa 5 — Pessoas e vínculos**, necessária antes da criação completa de aplicações.

A sequência prevista é:

1. **Etapa 5.1 — CRUD de pessoas** — RF-020 e RF-021;
2. **Etapa 5.2 — CRUD de vínculos** — RF-022 a RF-025;
3. depois avançar para o módulo de aplicações — RF-050 em diante.

As tabelas `pessoas` e `vinculos` já existem na migration base; portanto, a Etapa 5 deve começar pela API e frontend, sem presumir migration nova.

**Etapa 4 oficialmente concluída.**

## 26. Etapa 5.1 — CRUD de pessoas

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/PersonController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalPeople.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/pessoas
POST   /api/profissional/pessoas
GET    /api/profissional/pessoas/{id}
PUT    /api/profissional/pessoas/{id}
DELETE /api/profissional/pessoas/{id}
```

Regras implementadas:

- toda pessoa pertence ao profissional autenticado;
- nome obrigatório;
- e-mail opcional e validado quando informado;
- telefone opcional;
- data de nascimento opcional em formato válido;
- observação administrativa opcional;
- estados permitidos: `ATIVO` e `INATIVO`;
- edição administrativa não altera snapshots de avaliações anteriores;
- listagem informa também total de vínculos e aplicações associadas;
- exclusão física bloqueada quando a pessoa possui vínculos ou aplicações;
- pessoa com histórico deve ser preservada e pode ser marcada como inativa.

No frontend foi criada:

```text
/profissional/pessoas
```

A área profissional agora possui o card `Pessoas`.

### Validação local concluída

Em 2026-10-04 a Etapa 5.1 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao módulo `Pessoas` pela área profissional;
- cadastro de pessoa apenas com nome;
- cadastro de pessoa com os campos opcionais preenchidos;
- edição de nome, e-mail, telefone, data de nascimento e observação administrativa;
- alteração do status para `INATIVO`;
- persistência dos dados após recarregar a página;
- exclusão de pessoa sem histórico;
- manutenção do escopo por profissional autenticado.

Não houve necessidade de migration nova nesta etapa, pois a tabela `pessoas` já existe na migration base.

**Etapa 5.1 concluída.**

**Próxima subetapa:** Etapa 5.2 — vínculos entre pessoas.

## 27. Etapa 5.2 — vínculos entre pessoas

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/RelationshipController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalRelationships.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET    /api/profissional/vinculos
POST   /api/profissional/vinculos
GET    /api/profissional/vinculos/{id}
PUT    /api/profissional/vinculos/{id}
DELETE /api/profissional/vinculos/{id}
```

Regras implementadas:

- vínculo pertence ao profissional autenticado;
- vínculo possui exatamente duas pessoas;
- backend impede `pessoa_a_id = pessoa_b_id`;
- ambas as pessoas precisam pertencer ao profissional autenticado;
- lados A/B permanecem estáveis após a criação;
- tipo do vínculo é obrigatório e textual;
- `OUTRO` exige descrição personalizada;
- descrição do tipo de vínculo e tempo de união textual são opcionais nos demais casos;
- estados permitidos: `ATIVO` e `INATIVO`;
- listagem informa o total de aplicações relacionadas;
- exclusão física é bloqueada quando já existem aplicações;
- vínculo com histórico deve ser preservado e pode ser marcado como inativo.

No frontend foi criada:

```text
/profissional/vinculos
```

A área profissional agora possui o card `Vínculos`.

### Validação local concluída

Em 2026-10-04 a Etapa 5.2 foi validada com sucesso no ambiente local.

Foram confirmados:

- acesso ao módulo `Vínculos` pela área profissional;
- criação de vínculo entre duas pessoas distintas;
- preenchimento de tipo de vínculo e tempo de união;
- rejeição da mesma pessoa nos lados A e B;
- validação de `OUTRO` exigindo descrição personalizada;
- edição de tipo, descrição, duração e status;
- preservação dos lados A/B durante a edição;
- alteração do vínculo para `INATIVO`;
- persistência dos dados após recarregar a página;
- exclusão de vínculo sem aplicações associadas.

Não houve necessidade de migration nova nesta etapa, pois a tabela `vinculos` já existe na migration base.

**Etapa 5.2 concluída.**

## 28. Fechamento da Etapa 5

A Etapa 5 — Pessoas e vínculos foi encerrada em 2026-10-04 após validação local das subetapas 5.1 e 5.2.

Requisitos atendidos:

- `RF-020` — cadastro de pessoa;
- `RF-021` — edição administrativa de pessoa sem alterar snapshots históricos;
- `RF-022` — vínculo entre exatamente duas pessoas;
- `RF-023` — tipo de vínculo com suporte a `OUTRO`;
- `RF-024` — backend impede a mesma pessoa nos lados A e B;
- `RF-025` — estrutura de vínculo pronta para múltiplas aplicações ao longo do tempo.

Regras históricas preservadas:

- pessoas com vínculos/aplicações não são excluídas fisicamente;
- vínculos com aplicações não são excluídos fisicamente;
- pessoas e vínculos podem ser inativados;
- lados A/B permanecem estáveis dentro do vínculo;
- todas as operações permanecem isoladas por profissional autenticado.

**Etapa 5 oficialmente concluída.**

### Próxima etapa — Etapa 6: Aplicações

A Etapa 6 começará pelo núcleo de criação da aplicação, baseado em `RF-050` a `RF-054`.

Subetapa inicial prevista:

**Etapa 6.1 — criação de aplicações com versão e participantes A/B**

Objetivos iniciais:

- criar aplicação vinculada ao profissional autenticado;
- selecionar uma versão específica do instrumento;
- vincular um vínculo existente quando aplicável;
- registrar e-mail de contato;
- preservar tipo de vínculo e tempo de união como snapshot;
- criar exatamente dois participantes operacionais, lados A e B;
- preservar dados da aplicação independentemente de alterações futuras nos cadastros permanentes;
- iniciar o ciclo de estados da aplicação em `RASCUNHO`.

As tabelas `aplicacoes` e `aplicacao_participantes` já existem na migration base. A próxima implementação deve primeiro usar essa estrutura existente antes de considerar qualquer migration adicional.

## 29. Etapa 6.1 — criação de aplicações com participantes A/B

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/ApplicationController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalApplications.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas protegidas adicionadas:

```text
GET  /api/profissional/aplicacoes
GET  /api/profissional/aplicacoes/opcoes
POST /api/profissional/aplicacoes
GET  /api/profissional/aplicacoes/{id}
```

Regras implementadas:

- somente versões `PUBLICADA` aparecem como opção para nova aplicação;
- backend também rejeita versão que não esteja publicada ou não pertença ao profissional;
- vínculo é opcional;
- quando informado, o vínculo precisa estar `ATIVO` e pertencer ao profissional;
- e-mail de contato é obrigatório e validado;
- com vínculo, tipo de vínculo e tempo de união são copiados como snapshot;
- sem vínculo, tipo é informado manualmente e duração permanece opcional;
- aplicação nasce com status `RASCUNHO`;
- exatamente dois participantes são criados, lados `A` e `B`;
- os dois participantes nascem com status `PENDENTE`;
- com vínculo, `pessoa_id` e `nome_snapshot` são preenchidos a partir dos lados do vínculo;
- sem vínculo, os dois slots são criados com `pessoa_id = NULL`;
- `idade_snapshot` e `genero_snapshot` permanecem pendentes para identificação do participante;
- aplicação + participantes são gravados dentro de uma única transação.

No frontend, o card `Avaliações` agora abre:

```text
/profissional/avaliacoes
```

A tela lista as aplicações existentes, mostra versão utilizada, snapshot do vínculo e os estados dos participantes A e B.

### Validação local pendente

Após `git pull`, executar na API:

```powershell
cd mapa-relacional-api
composer check
composer serve
```

No frontend:

```powershell
cd ..\mapa-relacional-web
npm run build
npm run dev
```

Validar:

1. abrir `Avaliações` pela área profissional;
2. confirmar que somente versões publicadas aparecem no seletor;
3. selecionar uma versão publicada e um vínculo ativo;
4. informar um e-mail válido e criar a avaliação;
5. confirmar status `RASCUNHO`;
6. confirmar que aparecem exatamente os participantes A e B com status `PENDENTE`;
7. confirmar que os nomes correspondem aos lados do vínculo;
8. confirmar que tipo de vínculo e tempo de união aparecem como snapshot;
9. editar depois o tipo ou duração no cadastro do vínculo e confirmar que a aplicação já criada mantém o snapshot anterior;
10. criar uma segunda avaliação sem vínculo prévio, informando tipo manual e e-mail;
11. confirmar que ela também possui A e B, mas com identificação pendente;
12. recarregar a página e confirmar persistência.

Não há migration nova nesta etapa; `aplicacoes` e `aplicacao_participantes` já existem na migration base.

**Próxima subetapa prevista:** Etapa 6.2 — preparação dos acessos individuais seguros para A e B.

### Correção funcional — autoatendimento público

Em 2026-10-04 foi identificado que a implementação inicial da Etapa 6.1 estava excessivamente centrada na criação da aplicação pelo profissional.

O objetivo correto do produto é que uma pessoa possa entrar no site público, conhecer as avaliações disponíveis, escolher uma delas e iniciar por conta própria, sem que o profissional tenha conhecimento prévio ou precise criar a avaliação manualmente.

A área profissional continuará exibindo e acompanhando todas as aplicações, inclusive as iniciadas publicamente, e poderá manter a criação manual como recurso secundário.

A Etapa 6.1 foi, portanto, **reaberta** antes de avançar para 6.2.

Refatoração necessária:

1. criar catálogo/entrada pública de avaliações;
2. exibir apenas avaliações elegíveis baseadas em versões publicadas;
3. permitir que o visitante informe participante A e participante B;
4. coletar e-mail de contato;
5. coletar tipo de vínculo e tempo de união;
6. criar a aplicação associada automaticamente ao profissional dono do instrumento;
7. criar os dois participantes A/B usando snapshots, sem exigir registros prévios em `pessoas` ou `vinculos`;
8. fazer a nova aplicação aparecer automaticamente em `/profissional/avaliacoes`;
9. manter o fluxo profissional atual apenas como criação assistida opcional;
10. somente depois dessa correção avançar para acessos individuais seguros.

A validação anterior do fluxo profissional continua útil, mas não encerra a Etapa 6.1 porque não representa o fluxo principal desejado.

### Implementação do autoatendimento público

A refatoração principal da Etapa 6.1 foi implementada em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/PublicEvaluationController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/PublicEvaluations.jsx`;
- `mapa-relacional-web/src/pages/PublicEvaluationStart.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Rotas públicas adicionadas:

```text
GET  /api/public/avaliacoes
GET  /api/public/avaliacoes/{versionId}
POST /api/public/avaliacoes/{versionId}/iniciar
```

Rotas públicas do frontend:

```text
/avaliacoes
/avaliacao/{versionId}/iniciar
```

Fluxo implementado:

1. a página inicial oferece o botão `Fazer uma avaliação`;
2. o visitante acessa o catálogo público;
3. o catálogo mostra instrumentos `ATIVO` com versão `PUBLICADA`, usando a versão publicada mais recente;
4. o visitante escolhe a avaliação sem precisar conhecer o número da versão;
5. informa participante A, participante B, e-mail, tipo de vínculo e tempo de união;
6. o backend identifica automaticamente o profissional responsável pela avaliação;
7. a aplicação é criada em `RASCUNHO` com `vinculo_id = NULL`;
8. são criados atomicamente os participantes A e B com `pessoa_id = NULL`, nomes em snapshot e status `PENDENTE`;
9. a aplicação passa a aparecer automaticamente em `/profissional/avaliacoes`;
10. nenhum cadastro permanente em `pessoas` ou `vinculos` é criado pelo autoatendimento.

A criação assistida na área profissional continua disponível como fluxo secundário.

### Validação local pendente — autoatendimento público

Após `git pull`, executar:

```powershell
cd mapa-relacional-api
composer check
composer serve
```

Em outro terminal:

```powershell
cd ..\mapa-relacional-web
npm run build
npm run dev
```

Antes do teste, o instrumento que deve aparecer publicamente precisa estar com status `ATIVO` e possuir uma versão `PUBLICADA`.

Validar:

1. abrir a página inicial e clicar em `Fazer uma avaliação`;
2. confirmar que o catálogo público lista a avaliação ativa;
3. confirmar que o número técnico da versão não aparece ao visitante;
4. clicar em `Fazer avaliação`;
5. preencher nomes dos participantes A e B;
6. informar um e-mail válido;
7. informar tipo de vínculo e tempo de união;
8. iniciar a avaliação;
9. confirmar a tela de sucesso com os dois participantes;
10. entrar na área profissional e confirmar que a nova aplicação apareceu automaticamente;
11. confirmar que ela está em `RASCUNHO`, com A e B em `PENDENTE`;
12. confirmar que os nomes, tipo de vínculo e tempo de união foram preservados;
13. confirmar que nenhum registro novo foi criado automaticamente em `Pessoas` ou `Vínculos`.

Não há migration nova nesta correção.

**Próximo passo após a validação:** concluir a Etapa 6.1 e implementar a geração dos dois acessos individuais seguros.

Na Etapa 6.2, além de gerar os dois acessos individuais, o backend deverá enviar os links ao e-mail cadastrado. A tela de confirmação pública só poderá mostrar a mensagem de que os links foram enviados depois de receber confirmação de sucesso desse envio.

## 30. Etapa 6.2 — acessos individuais e SMTP Brevo

Implementação criada no GitHub em 2026-10-04.

A Etapa 6.1 pública foi aceita como base funcional e a Etapa 6.2 passou a implementar o envio real dos acessos.

Arquivos principais:

- `mapa-relacional-api/src/Service/AccessTokenService.php`;
- `mapa-relacional-api/src/Service/MailService.php`;
- `mapa-relacional-api/src/Controller/ParticipantAccessController.php`;
- `mapa-relacional-api/src/Controller/PublicEvaluationController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`;
- `mapa-relacional-api/.env.example`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/PublicEvaluationStart.jsx`;
- `mapa-relacional-web/src/pages/PublicParticipantAccess.jsx`;
- `mapa-relacional-web/src/App.jsx`.

Dependência adicionada:

```text
phpmailer/phpmailer
```

Configuração SMTP esperada no `.env`:

```text
SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_USERNAME=...
SMTP_PASSWORD=...
SMTP_ENCRYPTION=tls
SMTP_TIMEOUT_SECONDS=15
MAIL_FROM_EMAIL=...
MAIL_FROM_NAME="Avaliação de Percepção Relacional"
```

Regras implementadas:

- geração de um token aleatório independente para A e outro para B;
- token bruto não é persistido;
- somente SHA-256 é armazenado em `acessos_aplicacao.token_hash`;
- dois registros de acesso são criados com status `ATIVO`;
- um único e-mail Brevo contém os dois links individuais, identificados como A e B;
- links usam `FRONTEND_URL/avaliacao/acesso/{token}`;
- envio bem-sucedido registra `enviado_em` nos dois acessos e na aplicação;
- após o envio, a aplicação passa para `PRONTA`;
- falha de SMTP reverte a transação e retorna erro sem afirmar que o e-mail foi enviado;
- a tela pública de sucesso agora confirma explicitamente o envio dos links;
- o endpoint público de acesso valida o token pelo hash;
- abertura válida registra primeiro e último acesso;
- tokens inválidos ou acessos indisponíveis não revelam dados da aplicação.

Nova rota pública da API:

```text
GET /api/public/acessos/{token}
```

Nova rota pública do frontend:

```text
/avaliacao/acesso/{token}
```

### Validação local pendente

Após `git pull`, executar `composer install` porque foi adicionada a dependência PHPMailer.

Preencher no arquivo local `.env` as credenciais SMTP do Brevo. Não versionar nem enviar as credenciais ao GitHub.

Validar:

1. `composer check`;
2. iniciar uma nova avaliação pelo fluxo público;
3. confirmar recebimento de um e-mail no endereço cadastrado;
4. confirmar que o e-mail contém dois links diferentes, um para A e outro para B;
5. confirmar que a tela pública informa que os links foram enviados;
6. abrir o link de A e confirmar o nome e lado A;
7. abrir o link de B e confirmar o nome e lado B;
8. confirmar que os dois links são diferentes;
9. confirmar na área profissional que a aplicação aparece como `PRONTA`;
10. confirmar no banco que `acessos_aplicacao.token_hash` contém hashes e não os tokens brutos;
11. confirmar que `enviado_em` foi preenchido na aplicação e nos dois acessos;
12. testar temporariamente uma senha SMTP inválida e confirmar que a interface mostra erro de envio sem criar uma nova aplicação.

Não há migration nova; `acessos_aplicacao` e os campos `enviado_em` já existem na migration base.

**Próxima subetapa prevista:** Etapa 6.3 — identificação inicial e início do preenchimento individual.

### Ajuste de diagnóstico SMTP — 2026-10-04

Durante a validação local, o fluxo público retornou `502 Bad Gateway` em `POST /api/public/avaliacoes/{versionId}/iniciar`, indicando falha real no envio SMTP. Como a criação pública usa transação, a aplicação foi revertida corretamente.

Ajustes aplicados:

- `SMTP_ENCRYPTION` passa a ficar vazio por padrão na porta 587, permitindo negociação automática do STARTTLS pelo PHPMailer;
- falhas SMTP são registradas no log da aplicação;
- quando `APP_DEBUG=true`, a resposta 502 inclui o detalhe técnico da exceção para diagnóstico local;
- criado `bin/check-smtp.php` para testar o Brevo isoladamente do fluxo da avaliação;
- adicionado script `composer check-smtp -- <email>`.

Próximo teste: validar primeiro o SMTP isoladamente e somente depois repetir a criação pública da avaliação.
