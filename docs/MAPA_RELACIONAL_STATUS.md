# Avaliação de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-10  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `1.0.0-rc.1`  
**Marco atual:** V1 reaberta para correção de escopo obrigatório — site institucional  
**Etapa atual:** API implantada; homologação e endurecimento de produção pendentes  
**Próximo passo:** conferir configurações seguras de produção, migrations, checklists e homologação ponta a ponta

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

### Validação SMTP Brevo isolada concluída

Em 2026-10-04 o envio SMTP pelo Brevo foi validado com sucesso pelo script `bin/check-smtp.php`.

Foi confirmado que:

- PHPMailer está instalado e carregado corretamente;
- a conexão com `smtp-relay.brevo.com` funciona;
- autenticação SMTP funciona;
- o remetente configurado foi aceito;
- o e-mail de teste foi entregue ao destinatário informado.

A próxima validação da Etapa 6.2 é o fluxo completo da avaliação pública: criar uma nova aplicação, gerar os dois tokens, enviar os dois links no mesmo e-mail, abrir os links de A e B e confirmar o estado `PRONTA`.

### Validação completa da Etapa 6.2 concluída

Em 2026-10-04 a Etapa 6.2 foi validada com sucesso no fluxo completo.

Foram confirmados:

- criação pública da avaliação sem erro;
- geração de dois tokens individuais diferentes;
- envio real pelo SMTP Brevo para o e-mail cadastrado;
- tela pública confirmando o envio somente após sucesso do SMTP;
- e-mail contendo um link para o participante A e outro para o participante B;
- abertura do link A identificando corretamente o participante e o lado A;
- abertura do link B identificando corretamente o participante e o lado B;
- aplicação exibida na área profissional com status `PRONTA`;
- participantes permanecendo em `PENDENTE` antes do início do preenchimento;
- armazenamento somente do hash do token em `acessos_aplicacao`.

**Etapa 6.2 concluída.**

**Próxima subetapa:** Etapa 6.3 — identificação inicial e início do preenchimento individual.

## 31. Etapa 6.3 — identificação inicial e carregamento do questionário

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/ParticipantAccessController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/PublicParticipantAccess.jsx`.

Rotas públicas adicionadas:

```text
POST /api/public/acessos/{token}/identificacao
GET  /api/public/acessos/{token}/questionario
```

Regras implementadas:

- link individual continua sendo a única credencial do participante;
- antes do questionário, participante confirma nome, idade e gênero;
- nome pode corrigir o snapshot inicial informado na abertura pública;
- idade obrigatória entre 1 e 120;
- gênero obrigatório e textual, sem enumeração fechada nesta etapa;
- tempo de união é apenas exibido a partir do snapshot da aplicação;
- identificação atualiza `nome_snapshot`, `idade_snapshot` e `genero_snapshot`;
- participante muda de `PENDENTE` para `EM_ANDAMENTO`;
- `participante.iniciou_em` é preenchido somente no primeiro início;
- aplicação muda de `PRONTA` para `EM_ANDAMENTO` no primeiro participante que inicia;
- `aplicacao.iniciada_em` preserva o primeiro início;
- questionário não é entregue enquanto a identificação estiver pendente;
- questionário usa exatamente a versão congelada da aplicação;
- apenas seções, itens e alternativas ativos são retornados;
- itens já excluídos globalmente por “Não se aplica” não são retornados;
- frontend mostra as seções, itens, alternativas e as duas perspectivas esperadas por item.

A persistência das respostas ainda não foi habilitada nesta subetapa. O frontend deixa isso explícito para não simular um salvamento inexistente.

### Validação local pendente

Após `git pull`:

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

Usar um dos links individuais recebidos por e-mail e validar:

1. abrir o link de A;
2. confirmar que aparecem nome, tipo de vínculo e tempo de união corretos;
3. informar idade e gênero e, se desejar, corrigir o nome;
4. clicar em `Confirmar e iniciar`;
5. confirmar que o questionário da versão publicada é carregado;
6. confirmar a quantidade de seções e itens;
7. confirmar que aparecem as alternativas cadastradas;
8. confirmar que cada item mostra as duas perspectivas: “sobre mim” e “sobre a outra pessoa”;
9. recarregar a página e confirmar que a identificação não é solicitada novamente;
10. na área profissional, confirmar que a aplicação passou para `EM_ANDAMENTO`;
11. confirmar que o participante A passou para `EM_ANDAMENTO` e B continua `PENDENTE`;
12. abrir o link B e repetir o processo, confirmando depois os dois participantes em `EM_ANDAMENTO`.

Não há migration nova nesta etapa.

**Próxima subetapa prevista:** Etapa 6.4 — respostas individuais e persistência progressiva das duas perspectivas.

### Correção de resposta nula na tela de acesso

Durante a validação da Etapa 6.3, a tela do participante exibiu `Cannot read properties of null (reading 'acesso')`.

A causa imediata era o frontend assumir que toda resposta HTTP 2xx da API sempre continha JSON válido com a propriedade `acesso`.

Correções aplicadas:

- o cliente HTTP agora lê primeiro o corpo bruto;
- resposta vazia em HTTP 2xx gera erro explícito;
- resposta não-JSON gera erro explícito;
- a tela do participante valida a presença de `acesso.participante` antes de continuar;
- a mesma proteção foi adicionada à resposta de identificação e ao carregamento do questionário.

Essa correção evita o erro JavaScript genérico e permitirá identificar claramente se houver uma resposta vazia ou inválida originada no backend.

### Correção — resposta HTTP 200 com corpo não-JSON

Na validação da Etapa 6.3, o link individual retornou HTTP 200, mas o frontend identificou corpo não-JSON.

Como a API deve responder exclusivamente JSON, o bootstrap foi ajustado para não imprimir warnings/notices PHP no corpo HTTP. Erros PHP passam a ser registrados em:

```text
mapa-relacional-api/storage/logs/php-error.log
```

Em desenvolvimento, caso a API ainda retorne conteúdo não-JSON, o frontend mostra até os primeiros 500 caracteres do corpo recebido para permitir diagnóstico imediato sem depender apenas do erro genérico.

### Validação completa da Etapa 6.3 concluída

Em 2026-10-04 a Etapa 6.3 foi validada com sucesso no fluxo local.

Foram confirmados:

- abertura válida do link individual do participante;
- carregamento correto dos dados do acesso;
- identificação inicial com nome, idade e gênero;
- preservação do tempo de união como dado da aplicação;
- atualização do participante de `PENDENTE` para `EM_ANDAMENTO`;
- atualização da aplicação de `PRONTA` para `EM_ANDAMENTO` no primeiro início;
- carregamento da estrutura do questionário da versão congelada da aplicação;
- exibição de seções, itens, alternativas e das duas perspectivas por item;
- correção do problema de resposta HTTP 200 com corpo não-JSON;
- frontend protegido contra respostas nulas ou inválidas da API.

**Etapa 6.3 concluída.**

**Próxima subetapa:** Etapa 6.4 — respostas individuais e persistência progressiva das duas perspectivas.

## 32. Etapa 6.4 — respostas individuais e persistência progressiva

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/ParticipantAccessController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/PublicParticipantAccess.jsx`.

Nova rota pública:

```text
PUT /api/public/acessos/{token}/respostas/{itemId}
```

Payload conceitual:

```json
{
  "perspectiva": "SOBRE_MIM | SOBRE_OUTRO",
  "alternativa_id": 1
}
```

Para itens abertos, `alternativa_id` é substituído por `valor_texto` ou `valor_numero`.

Regras implementadas:

- respondente é sempre o participante associado ao token;
- alvo é derivado no backend pela perspectiva;
- o cliente não escolhe IDs de participantes;
- respostas são salvas progressivamente, uma perspectiva por vez;
- selecionar uma alternativa salva imediatamente;
- respostas abertas são salvas ao sair do campo;
- salvar novamente a mesma perspectiva atualiza o registro existente;
- alternativas são validadas como ativas e pertencentes ao item;
- itens excluídos, inativos ou fora da versão da aplicação não aceitam respostas;
- o endpoint do questionário devolve as respostas já salvas somente daquele participante;
- ao recarregar a página, respostas anteriores voltam preenchidas;
- progresso é calculado como perspectivas respondidas sobre o total de `itens × 2`;
- o acesso registra `ultimo_acesso_em` a cada salvamento;
- respostas do outro participante não são retornadas.

A interface agora exibe controles reais para `Sobre mim` e `Sobre a outra pessoa`, estado `Salvando...`/`Salvo`, barra de progresso e orientação de retomada pelo mesmo link.

A regra “Não se aplica” ainda não foi habilitada nesta subetapa. A conclusão individual também permanece pendente.

### Validação local pendente — Etapa 6.4

Após `git pull`:

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

Validar com o link de um participante já identificado:

1. abrir o questionário;
2. responder `Sobre mim` no primeiro item;
3. confirmar a indicação `Salvo`;
4. responder `Sobre a outra pessoa` no mesmo item;
5. confirmar que o progresso aumenta para duas perspectivas respondidas;
6. alterar uma das duas respostas e confirmar que ela é atualizada, sem duplicar;
7. responder algumas perspectivas de outros itens;
8. pressionar `F5`;
9. confirmar que todas as respostas já salvas voltam marcadas/preenchidas;
10. confirmar que o percentual de progresso é preservado;
11. abrir o link do outro participante e confirmar que ele não vê nenhuma resposta do primeiro;
12. voltar ao primeiro link e confirmar que as respostas continuam intactas.

No banco, pode-se confirmar que cada combinação de aplicação/respondente/alvo/item possui no máximo um registro em `respostas`.

Não há migration nova nesta etapa; a tabela `respostas` e a chave única necessária já existem na migration base.

**Próxima subetapa prevista:** Etapa 6.5 — regra “Não se aplica” e conclusão individual do participante.

## 33. Etapa 6.5 — Não se aplica e conclusão individual

Implementação criada no GitHub em 2026-10-04.

Arquivos principais:

- `mapa-relacional-api/src/Controller/ParticipantAccessController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/PublicParticipantAccess.jsx`.

Novas rotas:

```text
POST /api/public/acessos/{token}/itens/{itemId}/nao-se-aplica
POST /api/public/acessos/{token}/concluir
```

Regras implementadas para **Não se aplica**:

- somente itens configurados para permitir a opção podem ser marcados;
- a interface pede confirmação explícita;
- é criado `aplicacao_itens_excluidos`;
- a exclusão vale para A e B;
- respostas antigas ficam preservadas;
- o item desaparece do questionário dos dois participantes;
- o item sai do denominador de progresso;
- o primeiro registro de exclusão é preservado em tentativas duplicadas;
- não há reversão da exclusão na V1.

Regras implementadas para conclusão:

- participante só conclui com todas as perspectivas válidas respondidas;
- participante passa para `CONCLUIDO`;
- acesso passa para `CONCLUIDO`;
- `concluiu_em` é registrado no participante e no acesso;
- depois disso o link não aceita novas respostas;
- com apenas um participante concluído, aplicação permanece `EM_ANDAMENTO`;
- quando os dois concluem, aplicação passa para `CONCLUIDA`;
- `aplicacoes.concluida_em` é preenchido na conclusão do segundo participante.

A interface inclui botão `Não se aplica` somente nos itens permitidos e botão final `Concluir minha participação`, liberado apenas quando o progresso válido estiver completo.

Também foi adicionado botão explícito `Salvar resposta` para respostas abertas, mantendo o salvamento por saída do campo e evitando dificuldade para salvar a última resposta antes da conclusão.

### Validação local pendente — Etapa 6.5

Após `git pull` e reinício da API/frontend, validar:

1. abrir o link do participante A;
2. responder parcialmente alguns itens;
3. em um item que permita, clicar `Não se aplica`;
4. confirmar o aviso;
5. verificar que o item desaparece imediatamente;
6. pressionar `F5` e confirmar que o item continua ausente;
7. abrir o link do participante B e confirmar que o mesmo item também não aparece;
8. confirmar que respostas anteriores desse item, se existirem, não reaparecem no questionário;
9. completar todas as perspectivas válidas de A;
10. confirmar que o botão de conclusão é liberado;
11. concluir A e verificar `CONCLUIDO` na área profissional;
12. tentar reabrir o link A e confirmar que não é possível alterar respostas;
13. concluir todas as perspectivas válidas de B;
14. concluir B;
15. verificar na área profissional que A e B estão `CONCLUIDO` e a aplicação está `CONCLUIDA`.

Não há migration nova nesta etapa.

**Próxima subetapa prevista:** Etapa 7 — comparação das percepções e cálculo dos resultados.

## 34. Etapa 7.1 — comparação e resultados técnicos

Implementação criada no GitHub em 2026-10-04.

A implementação da Etapa 6.5 permanece disponível no repositório. O usuário solicitou prosseguir diretamente para a Etapa 7 sem um checkpoint textual separado de validação da 6.5.

### Estrutura de banco

Novas migrations:

```text
004_resultados_comparacoes.sql
005_ajustar_faixas_percentuais.sql
```

Novas tabelas:

- `resultado_faixas`;
- `comparacoes`;
- `resultados`.

`resultado_faixas` é vinculada a `instrumento_versoes`, permitindo manter a interpretação associada à versão usada pela aplicação.

Faixas iniciais:

```text
0,00–33,99   Ruim
34,00–66,99  Regular
67,00–100    Bom
```

A migration 005 elimina lacunas para percentuais decimais entre os intervalos inteiros descritos no material-base.

Novas versões criadas depois desta etapa recebem automaticamente as três faixas-base.

### Backend

Novo serviço:

```text
src/Service/ResultService.php
```

Algoritmo `1.0`:

```text
A_SOBRE_B = A→B × B→B
B_SOBRE_A = B→A × A→A
percentual = coincidências / comparações válidas × 100
```

Regras:

- itens “Não se aplica” não entram na comparação;
- alternativas coincidem quando possuem a mesma alternativa;
- números coincidem quando possuem o mesmo valor;
- textos coincidem por igualdade após remoção de espaços nas extremidades;
- resposta ausente ou representação incompatível gera comparação não comparável;
- somente comparações válidas entram no denominador;
- não é calculado resultado global automático nesta etapa.

Quando o segundo participante conclui, o cálculo ocorre automaticamente dentro da transação de conclusão.

Rotas profissionais:

```text
GET  /api/profissional/aplicacoes/{id}/resultados
POST /api/profissional/aplicacoes/{id}/resultados/calcular
```

A segunda rota permite calcular/recalcular aplicações concluídas, inclusive registros concluídos antes da implementação da Etapa 7.

### Frontend profissional

Nova página:

```text
/profissional/avaliacoes/{id}/resultados
```

Arquivo:

```text
mapa-relacional-web/src/pages/ProfessionalApplicationResults.jsx
```

A lista de avaliações exibe `Ver resultados` para aplicações `CONCLUIDA`.

O painel mostra:

- resultado A sobre B;
- resultado B sobre A;
- percentual;
- faixa;
- coincidências / comparações válidas;
- barra percentual;
- resultados derivados por seção;
- comparação item a item;
- resposta percebida versus autorreferida;
- indicação de coincidência/divergência/não comparável;
- itens excluídos por “Não se aplica”;
- versão do algoritmo.

Não há diagnóstico automático.

### Validação local pendente — Etapa 7.1

Depois de sincronizar, executar obrigatoriamente:

```powershell
cd mapa-relacional-api
composer migrate
composer check-domain
composer check
composer serve
```

Em outro terminal:

```powershell
cd ..\mapa-relacional-web
npm run build
npm run dev
```

Validar preferencialmente com uma aplicação nova:

1. concluir A e B;
2. confirmar que a aplicação passa para `CONCLUIDA`;
3. entrar em Profissional → Avaliações;
4. clicar em `Ver resultados`;
5. confirmar os dois sentidos `A_SOBRE_B` e `B_SOBRE_A`;
6. conferir manualmente ao menos três itens, comparando as respostas exibidas;
7. confirmar que coincidências e divergências estão corretas;
8. confirmar que itens “Não se aplica” aparecem somente na área `Fora do cálculo`;
9. confirmar que esses itens não entram nas comparações válidas;
10. conferir o percentual com a fórmula manual;
11. conferir a faixa correspondente;
12. conferir o resultado por seção;
13. testar uma aplicação concluída anteriormente; se estiver sem resultado persistido, usar `Calcular resultados`;
14. confirmar que o recálculo mantém o mesmo resultado quando as respostas não mudaram.

O `composer check-domain` agora exige as migrations 004 e 005 e as três novas tabelas.

**Próximo passo previsto após validação:** Etapa 7.2 — configuração profissional das faixas por versão e refinamentos da apresentação dos resultados, antes da devolutiva/comentário profissional.

## 35. Etapa 7.2 — configuração profissional das faixas

Implementação criada no GitHub em 2026-10-04.

A Etapa 7.1 permanece implementada; o usuário solicitou continuar diretamente para a 7.2 sem registrar um checkpoint separado de validação da 7.1.

### Backend

Novo controlador:

```text
mapa-relacional-api/src/Controller/ResultBandController.php
```

Rotas protegidas:

```text
GET /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/faixas-resultados
PUT /api/profissional/instrumentos/{instrumentId}/versoes/{versionId}/faixas-resultados
```

Regras:

- consulta permitida em qualquer estado da versão pertencente ao profissional;
- edição somente em `RASCUNHO`;
- entre 1 e 10 faixas por versão;
- rótulo obrigatório;
- limites percentuais entre 0 e 100;
- primeira faixa começa em 0,00;
- última termina em 100,00;
- faixas cobrem todo o intervalo sem lacunas nem sobreposições;
- próxima faixa começa 0,01 ponto percentual após a anterior;
- substituição das faixas ocorre em transação;
- códigos técnicos precisam ser únicos e são gerados automaticamente para novas faixas sem código.

Nenhuma migration nova foi necessária, pois `resultado_faixas` já foi criada na migration 004.

### Frontend

Nova página:

```text
/profissional/instrumentos/{instrumentId}/versoes/{versionId}/faixas-resultados
```

Arquivo:

```text
mapa-relacional-web/src/pages/ProfessionalResultBands.jsx
```

Na lista de versões foi adicionado o botão `Faixas de resultado`.

Em versões em rascunho, o profissional pode:

- alterar o nome de uma faixa;
- alterar limites mínimo e máximo;
- adicionar faixa;
- remover faixa;
- salvar a configuração completa.

Em versões publicadas ou arquivadas, a página fica somente para leitura e orienta que uma nova versão deve ser criada para alterar a interpretação.

O painel de resultados também passou a mostrar a versão do instrumento e oferece o botão `Ver faixas`, levando à configuração congelada usada por aquela aplicação.

### Validação local pendente — Etapa 7.2

Se as migrations 004 e 005 ainda não tiverem sido aplicadas neste banco, executar primeiro:

```powershell
cd mapa-relacional-api
composer migrate
```

Depois:

```powershell
composer check-domain
composer check
composer serve
```

Em outro terminal:

```powershell
cd ..\mapa-relacional-web
npm run build
npm run dev
```

Validar com uma versão em `RASCUNHO`:

1. entrar em Profissional → Instrumentos → Versões;
2. clicar em `Faixas de resultado`;
3. confirmar que aparecem as faixas-base;
4. alterar um rótulo;
5. alterar limites mantendo cobertura contínua de 0 a 100;
6. salvar;
7. recarregar a página e confirmar persistência;
8. testar uma configuração com lacuna e confirmar que o backend rejeita;
9. testar uma sobreposição e confirmar rejeição;
10. adicionar uma nova faixa, ajustar os limites e salvar;
11. publicar a versão;
12. abrir novamente `Faixas de resultado` e confirmar modo somente leitura;
13. tentar alteração direta via interface e confirmar que não há controles de gravação;
14. em uma aplicação concluída, abrir `Ver resultados` → `Ver faixas` e confirmar que são exibidas as faixas da versão usada naquela aplicação.

**Próximo passo previsto após validação:** Etapa 8 — comentário profissional, devolutiva e regra de disponibilização dos resultados aos participantes.

## 36. Etapa 8 — devolutiva profissional e liberação dos resultados

Implementação criada no GitHub em 2026-10-04.

Por solicitação explícita do usuário, os testes da Etapa 7.2 foram pulados e a Etapa 8 foi implementada diretamente. Portanto, **não considerar 7.2 nem 8 formalmente validadas**, embora o código esteja implementado.

### Banco de dados

Nova migration:

```text
006_devolutivas.sql
```

Nova tabela:

```text
devolutivas
```

Campos principais:

- `aplicacao_id`;
- `profissional_id`;
- `sintese`;
- `observacoes`;
- `comentario_profissional`;
- `status` (`RASCUNHO` / `LIBERADA`);
- `token_hash`;
- `liberada_em`;
- `enviado_em`.

### Backend profissional

Novo controlador:

```text
src/Controller/FeedbackController.php
```

Rotas protegidas:

```text
GET  /api/profissional/aplicacoes/{id}/devolutiva
PUT  /api/profissional/aplicacoes/{id}/devolutiva
POST /api/profissional/aplicacoes/{id}/devolutiva/liberar
```

Regras:

- somente aplicações `CONCLUIDA` recebem devolutiva;
- o profissional pode salvar rascunho com síntese, observações e comentário;
- cálculo técnico e comentário permanecem separados;
- ao liberar, resultados técnicos são garantidos/calculados se necessário;
- é gerado token seguro exclusivo para a devolutiva;
- somente SHA-256 do token é persistido;
- o link é enviado por SMTP Brevo ao e-mail de contato da aplicação;
- em falha de e-mail, a devolutiva não passa para `LIBERADA`;
- depois da liberação, o conteúdo fica imutável na V1.

### Resultado público

Nova rota da API:

```text
GET /api/public/resultados/{token}
```

Nova rota do frontend:

```text
/resultado/{token}
```

Nova página:

```text
mapa-relacional-web/src/pages/PublicResult.jsx
```

O resultado público apresenta:

- participantes;
- tipo de vínculo;
- tempo de união;
- resultados A→B × B→B e B→A × A→A;
- percentuais e faixas;
- resultados por seção;
- síntese;
- observações;
- comentário profissional;
- itens “Não se aplica”.

Por privacidade, não são expostas respostas individuais brutas nem comparações item a item.

### E-mail

`MailService` recebeu o método de envio da devolutiva. O e-mail informa que o resultado foi liberado e contém um único link seguro para a devolutiva do par relacional.

### Frontend profissional

O painel `Ver resultados` recebeu a seção `Devolutiva profissional`.

Enquanto em rascunho, o profissional pode:

- editar síntese;
- editar observações;
- editar comentário profissional;
- salvar rascunho;
- liberar e enviar por e-mail.

Depois da liberação, os campos ficam somente para leitura.

### Validação

**Pulada por decisão explícita do usuário.**

Não registrar os seguintes itens como testados:

- migration 006 aplicada localmente;
- gravação de rascunho;
- bloqueio de edição pós-liberação;
- envio do e-mail de devolutiva;
- abertura do link público;
- privacidade dos dados públicos.

Para continuar localmente, a migration precisa ser aplicada:

```powershell
cd mapa-relacional-api
composer migrate
```

O `composer check-domain` agora exige `006_devolutivas.sql` e a tabela `devolutivas`.

**Próximo passo sugerido:** Etapa 9 — dashboard profissional e refinamento da lista/detalhe das aplicações.

## 37. Etapa 9 — dashboard e acompanhamento profissional

Implementação criada no GitHub em 2026-10-04.

A Etapa 9 foi implementada sobre a estrutura das Etapas 7 e 8. Como a Etapa 8 teve validação pulada, a migration `006_devolutivas.sql` precisa estar aplicada para o dashboard funcionar integralmente.

### Backend

`ApplicationController` recebeu:

```text
GET /api/profissional/dashboard
```

O endpoint retorna:

- total de avaliações;
- rascunhos;
- prontas;
- em andamento;
- concluídas;
- canceladas;
- resultados disponíveis;
- devolutivas em rascunho;
- devolutivas liberadas;
- seis avaliações mais recentes.

A listagem `GET /api/profissional/aplicacoes` passou a aceitar filtros:

```text
participante
status
instrumento_id
vinculo_id
tipo_vinculo
data_de
data_ate
```

O filtro `participante` pesquisa nomes snapshot de A/B e o e-mail de contato.

Os parâmetros PDO do filtro textual foram mantidos separados para compatibilidade com `PDO::ATTR_EMULATE_PREPARES = false`.

O endpoint de opções profissionais também passa a devolver:

- instrumentos do profissional, inclusive históricos;
- vínculos para filtro, inclusive inativos;
- versões publicadas e vínculos ativos continuam separados para criação assistida.

O detalhe `GET /api/profissional/aplicacoes/{id}` agora retorna também:

- itens excluídos por “Não se aplica”;
- resumo dos resultados persistidos;
- `resultados_count`;
- `devolutiva_status`.

### Frontend

Nova página:

```text
mapa-relacional-web/src/pages/ProfessionalDashboard.jsx
```

A rota `/profissional` agora mostra:

- saudação ao profissional;
- cartões de métricas;
- aviso de devolutivas em rascunho;
- atalhos para módulos principais;
- avaliações recentes;
- acesso rápido a detalhe e resultados.

Nova página:

```text
mapa-relacional-web/src/pages/ProfessionalApplicationDetail.jsx
```

Nova rota:

```text
/profissional/avaliacoes/{id}
```

O detalhe exibe:

- status geral;
- instrumento e versão;
- e-mail de contato;
- tipo de vínculo;
- tempo de união;
- datas principais;
- participantes A e B;
- idade, gênero e status de cada participante;
- início e conclusão individual;
- resumo técnico dos dois sentidos;
- itens “Não se aplica”;
- status da devolutiva;
- atalho para resultados completos.

A lista `/profissional/avaliacoes` recebeu filtros por participante/e-mail, status, instrumento, vínculo, tipo de vínculo e período, além do botão `Ver detalhes`.

### Migration

A Etapa 9 não cria migration nova.

Como o dashboard consulta `devolutivas`, é obrigatório que as migrations anteriores estejam aplicadas, incluindo:

```text
004_resultados_comparacoes.sql
005_ajustar_faixas_percentuais.sql
006_devolutivas.sql
```

### Validação local pendente — Etapa 9

Executar:

```powershell
cd mapa-relacional-api
composer migrate
composer check-domain
composer check
composer serve
```

Em outro terminal:

```powershell
cd ..\mapa-relacional-web
npm run build
npm run dev
```

Validar:

1. entrar em `/profissional`;
2. conferir os cartões de métricas;
3. conferir se as avaliações recentes correspondem aos registros mais novos;
4. abrir `Avaliações`;
5. filtrar por participante;
6. filtrar por status;
7. filtrar por instrumento;
8. filtrar por vínculo administrativo;
9. filtrar por tipo de vínculo;
10. filtrar por período;
11. limpar os filtros;
12. abrir `Ver detalhes`;
13. conferir A/B, datas, tempo de união e status;
14. em aplicação concluída, conferir resumo dos resultados;
15. conferir itens “Não se aplica” quando existirem;
16. conferir o status da devolutiva;
17. usar o atalho `Ver resultados`.

**Próximo passo sugerido:** Etapa 10 — notificações complementares, auditoria e preparação de produção/V1.

## 38. Etapa 10 — segurança, auditoria e preparação da V1

Implementação criada no GitHub em 2026-10-04.

A Etapa 9 foi implementada no turno anterior, mas não houve confirmação explícita de validação local. Portanto, não registrar Etapa 9 como testada.

### Banco

Novas migrations:

```text
007_auditoria.sql
008_rate_limites.sql
```

Novas tabelas:

- `auditoria_eventos`;
- `rate_limites`.

O `check-domain` agora exige migrations 001–008 e as tabelas de auditoria e rate limit.

### Auditoria

Novo serviço:

```text
src/Service/AuditService.php
```

Novo controlador:

```text
src/Controller/AuditController.php
```

Nova rota:

```text
GET /api/profissional/auditoria
```

Nova página:

```text
/profissional/auditoria
```

Eventos inicialmente auditados:

- login profissional bem-sucedido;
- alteração de senha;
- atualização de perfil;
- criação, publicação e arquivamento de versão;
- criação pública de aplicação;
- “Não se aplica”;
- conclusão de participante;
- salvamento de devolutiva;
- liberação de devolutiva;
- reenvio de acesso;
- reenvio de devolutiva.

O serviço remove contexto cujo nome de chave indique senha, password, token, JWT, authorization ou credencial SMTP.

### Notificações complementares

Novo controlador:

```text
src/Controller/NotificationController.php
```

Novas rotas profissionais:

```text
POST /api/profissional/aplicacoes/{id}/acessos/{lado}/reenviar
POST /api/profissional/aplicacoes/{id}/devolutiva/reenviar
```

Regras:

- reenvio de participante permitido apenas antes da conclusão;
- novo token invalida o link anterior;
- falha SMTP restaura o hash anterior;
- devolutiva só pode ser reenviada quando estiver `LIBERADA`;
- reenvio da devolutiva também rotaciona o token;
- a tela de detalhe da aplicação oferece os botões correspondentes.

O `MailService` recebeu envio individual de novo acesso.

### Rate limit

Novo serviço:

```text
src/Service/RateLimitService.php
```

Proteções:

- login profissional por IP;
- login profissional por conta/e-mail;
- criação pública por IP;
- criação pública por e-mail.

Variáveis adicionadas ao `.env.example`:

```text
LOGIN_RATE_LIMIT_MAX=10
LOGIN_IP_RATE_LIMIT_MAX=30
LOGIN_RATE_LIMIT_WINDOW_SECONDS=900
PUBLIC_START_RATE_LIMIT_MAX=10
PUBLIC_START_EMAIL_RATE_LIMIT_MAX=5
PUBLIC_START_RATE_LIMIT_WINDOW_SECONDS=900
```

### Hardening HTTP

Novo middleware:

```text
src/Middleware/SecurityHeadersMiddleware.php
```

Cabeçalhos:

- `X-Content-Type-Options: nosniff`;
- `X-Frame-Options: DENY`;
- `Referrer-Policy: no-referrer`;
- `Cache-Control: no-store`.

CORS e headers de segurança foram posicionados externamente ao middleware de erro para também proteger respostas de erro da API.

### Checklist V1

Novo comando:

```powershell
composer check-v1
```

Estados:

- `PRONTO`;
- `PRONTO_COM_AVISOS`;
- `NAO_PRONTO`.

O comando verifica ambiente, debug, URLs, HTTPS em produção, JWT secret, extensões PHP, SMTP, logs, backups, conexão MySQL, tabelas e migrations.

### Backup

Novo comando:

```powershell
composer backup-db
```

O backup usa `mysqldump`, grava em `storage/backups`, não versionado, e informa tamanho e SHA-256.

Configuração opcional:

```text
MYSQLDUMP_BIN=mysqldump
```

### Documentação operacional

Criado:

```text
docs/PRODUCAO_V1.md
```

### Validação pendente

Como o usuário optou anteriormente por avançar etapas sem executar todos os testes, **não considerar a V1 encerrada ainda**.

Executar localmente:

```powershell
cd "E:\Compartilhar\Kriale\Tânia - plataforma digital\Desenvolvimento"
git pull

cd ".\mapa-relacional-api"
composer install
composer migrate
composer check-domain
composer check
composer check-v1
composer backup-db
composer serve
```

Em outro terminal:

```powershell
cd "E:\Compartilhar\Kriale\Tânia - plataforma digital\Desenvolvimento\mapa-relacional-web"
npm run build
npm run dev
```

Validar especialmente:

1. login normal;
2. bloqueio após repetidas credenciais inválidas;
3. criação pública normal;
4. reenvio do acesso de A ou B e invalidação do link anterior;
5. impossibilidade de reenviar acesso já concluído;
6. reenvio da devolutiva e invalidação do link anterior;
7. página `/profissional/auditoria`;
8. presença dos eventos esperados;
9. ausência de tokens e senhas no contexto da auditoria;
10. cabeçalhos de segurança nas respostas da API;
11. `composer check-v1`;
12. criação do backup;
13. restauração do backup em banco separado;
14. fluxo completo público → participantes → resultado → devolutiva.

**Próximo marco após validação:** fechamento formal da V1 e preparação do deploy.

### Validação confirmada — Etapa 10

Em 2026-10-04, o usuário confirmou que os comandos e verificações da Etapa 10 ficaram corretos após os ajustes de backup no Windows.

Confirmado no ambiente local:

- `mysqldump` localizado automaticamente;
- backup SQL criado com sucesso;
- backup recente reconhecido pelo `check-v1`;
- migrations `007_auditoria.sql` e `008_rate_limites.sql` aplicadas;
- tabelas `auditoria_eventos` e `rate_limites` presentes;
- `composer check-domain` sem pendências estruturais;
- `composer check-v1` sem erros críticos.

Os avisos de `APP_ENV` / `APP_DEBUG`, quando presentes no ambiente local, permanecem esperados até a configuração do deploy de produção.

A Etapa 10 pode ser considerada **validada localmente**.

O fechamento formal da V1 ainda deve distinguir:
- validação técnica local concluída;
- validações funcionais completas das Etapas 7.2, 8 e 9 que foram puladas ou não confirmadas explicitamente;
- validação final em ambiente de produção/homologação ainda pendente.

## 39. Fechamento formal da V1.0.0

Em 2026-10-05, a V1 foi formalmente encerrada em **escopo e desenvolvimento funcional**.

Documento oficial:

```text
docs/FECHAMENTO_V1.md
```

Estado:

- versão oficial: `1.0.0`;
- branch de referência: `main`;
- migrations oficiais da V1: `001` a `008`;
- desenvolvimento funcional da V1: **encerrado**;
- Etapa 10: validada localmente;
- deploy: pendente;
- homologação ponta a ponta: pendente.

Este fechamento não afirma que todas as funcionalidades foram homologadas em produção. As Etapas 7.2, 8 e 9 foram implementadas sem uma validação funcional completa formal antes do avanço, e devem ser cobertas pela homologação final.

A partir deste marco, a V1 aceita somente:

- correção de bug;
- correção de segurança;
- ajuste necessário ao deploy;
- correção de migration/integridade;
- compatibilidade;
- correção textual ou visual sem nova regra de negócio.

Novas funcionalidades deverão ser planejadas para V2.

**Próximo marco:** deploy e homologação da V1.0.0.

## 40. Correção de escopo — site institucional

Em 2026-10-05, após o fechamento formal da V1, foi identificado um erro de escopo: o documento original e o documento de requisitos exigiam um **site institucional configurável pelo profissional**, mas a implementação existente continha apenas uma home pública estática.

O fechamento da V1.0.0 foi, portanto, **retratado**. O projeto voltou ao estado de release candidate `1.0.0-rc.1`.

O requisito original exige que o profissional possa montar blocos de conteúdo com texto, links, imagens e vídeos, além de utilizar sua fotografia ou logotipo e manter responsividade em telas grandes, tablets e smartphones.

### Implementação corretiva — Etapa 11

Nova migration:

```text
009_site_institucional.sql
```

Nova tabela:

```text
site_blocos
```

Tipos de bloco suportados:

- `TITULO`;
- `TEXTO`;
- `IMAGEM`;
- `VIDEO`;
- `AUDIO`;
- `PERFIL`;
- `APRESENTACAO`;
- `CTA`;
- `LINK`;
- `AVALIACAO`.

Cada bloco pode possuir, conforme o tipo:

- título;
- descrição;
- conteúdo;
- URL de mídia;
- texto alternativo;
- link;
- texto do link;
- ordem;
- visibilidade;
- status ativo/inativo.

Novo backend:

```text
src/Controller/SiteController.php
```

Rotas:

```text
GET    /api/public/site
GET    /api/profissional/site/blocos
POST   /api/profissional/site/blocos
PUT    /api/profissional/site/blocos/{id}
DELETE /api/profissional/site/blocos/{id}
POST   /api/profissional/site/blocos/{id}/mover
```

Novo frontend profissional:

```text
/profissional/site
mapa-relacional-web/src/pages/ProfessionalSiteEditor.jsx
```

Recursos:

- criação de blocos;
- edição;
- ativação/inativação;
- visibilidade;
- reordenação;
- exclusão;
- textos;
- imagens;
- vídeos;
- áudio;
- links;
- CTA;
- uso automático dos dados do perfil profissional.

Novo frontend público:

```text
mapa-relacional-web/src/pages/PublicInstitutionalSite.jsx
```

A rota raiz `/` agora carrega o site institucional configurado no banco, usando:

- nome profissional;
- fotografia;
- logotipo;
- descrição;
- atuação;
- contatos;
- blocos ativos e visíveis.

O site mantém a identidade visual oficial e é responsivo.

### Validação pendente

Executar:

```powershell
cd mapa-relacional-api
composer migrate
composer check-domain
composer check
composer check-v1
composer serve
```

Em outro terminal:

```powershell
cd ..\mapa-relacional-web
npm run build
npm run dev
```

Validar:

1. abrir `/profissional/site`;
2. confirmar os blocos iniciais;
3. editar um bloco de texto;
4. criar um bloco de imagem;
5. criar um bloco de vídeo;
6. criar um bloco de link/CTA;
7. mover blocos para cima e para baixo;
8. ocultar um bloco e confirmar que não aparece no público;
9. inativar um bloco e confirmar que não aparece no público;
10. abrir `/` em desktop;
11. abrir `/` em largura de tablet;
12. abrir `/` em largura de smartphone;
13. confirmar fotografia/logotipo e dados do perfil;
14. confirmar botão para avaliações;
15. confirmar que o conteúdo configurado persiste após recarregar.

**Somente depois desta validação o fechamento formal da V1 poderá ser refeito.**

### Ajuste da Etapa 11 — upload de imagens

Em 2026-10-05, o editor institucional foi ajustado para que imagens sejam enviadas pelo computador, em vez de exigir URL manual.

Implementado:

- endpoint `POST /api/profissional/site/upload-imagem`;
- suporte `multipart/form-data` no cliente da API;
- upload de imagem no bloco `IMAGEM`;
- upload de fotografia no perfil profissional;
- upload de logotipo no perfil profissional;
- pré-visualização da imagem;
- JPG, PNG e WEBP;
- limite padrão de 5 MB;
- diretório `public/uploads/site/{profissional_id}`;
- arquivos enviados ignorados pelo Git;
- proteção `.htaccess` contra execução de scripts;
- verificação de `fileinfo` e escrita em `public/uploads` pelo `check-v1`;
- evento `SITE_IMAGEM_ENVIADA` na auditoria.

Nenhuma migration nova foi necessária.

Validação complementar da Etapa 11:

1. em `/profissional/perfil`, enviar uma fotografia;
2. salvar o perfil e confirmar a fotografia no site público;
3. enviar um logotipo, salvar e confirmar no cabeçalho público;
4. em `/profissional/site`, criar bloco `IMAGEM`;
5. selecionar arquivo JPG, PNG ou WEBP;
6. confirmar a pré-visualização;
7. salvar/criar o bloco;
8. recarregar o editor e confirmar persistência;
9. abrir `/` e confirmar a imagem;
10. executar `composer check-v1` e confirmar `public/uploads gravavel` e `Extensao fileinfo carregada`.

### Correção funcional — score geral do casal

Em 2026-10-05, a especificação do cálculo foi completada.

A implementação anterior possuía os dois scores direcionais, mas faltava o **score geral**.

Nova regra:

```text
Score A:
A→B × B→B

Score B:
B→A × A→A

Score geral por item:
1 ponto somente quando
(A→B = B→B) E (B→A = A→A)
```

Nova migration:

```text
010_resultado_geral.sql
```

Nova tabela:

```text
resultados_gerais
```

Campos principais:

- `aplicacao_id`;
- `itens_validos`;
- `acertos_gerais`;
- `percentual`;
- `faixa_id`;
- `faixa`;
- `algoritmo_versao`;
- `calculado_em`.

O algoritmo passa para:

```text
2.0
```

O questionário também passa a apresentar explicitamente as duas perguntas:

- “O que eu penso disso?”
- “O que eu acredito que o outro pensa disso?”

A devolutiva profissional e a página pública passam a mostrar:

- score individual de A;
- score individual de B;
- score geral;
- percentual geral;
- faixa geral;
- barra de acerto.

Cores das faixas:

- Ruim: vermelho;
- Regular: amarelo;
- Bom: verde.

O seed `seeds/avaliacao-conjugal.sql` foi corrigido para usar somente as três faixas oficiais, removendo a adaptação anterior de cinco faixas.

Validação pendente:

```powershell
composer migrate
composer check-domain
composer check
```

Depois, para uma Avaliação Conjugal já inserida anteriormente, executar novamente o seed atualizado para substituir as faixas antigas pelas três faixas oficiais.

Validar com um casal de teste em que seja possível prever manualmente os acertos individuais e o score geral.
### Ajuste do perfil — exclusão de fotografia e logotipo

Em 2026-10-05, o perfil profissional foi ajustado para permitir a exclusão individual das imagens já cadastradas.

Implementado:

- botão `Excluir fotografia` quando existe fotografia cadastrada;
- botão `Excluir logotipo` quando existe logotipo cadastrado;
- confirmação antes da exclusão;
- exclusão imediata, sem depender do botão `Salvar perfil`;
- endpoint `DELETE /api/profissional/perfil/imagem/{tipo}`, com `tipo` igual a `foto` ou `logo`;
- limpeza do campo correspondente no banco de dados;
- remoção do arquivo físico local quando ele não é mais referenciado;
- proteção para não apagar o arquivo físico quando a mesma URL ainda é usada pela outra imagem do perfil ou por um bloco do site;
- evento `PERFIL_IMAGEM_EXCLUIDA` na auditoria.

Arquivos alterados:

- `mapa-relacional-api/src/Controller/ProfessionalController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalProfile.jsx`.

Nenhuma migration nova foi necessária.

Validação complementar:

1. abrir `/profissional/perfil`;
2. confirmar que os botões de exclusão aparecem somente quando há imagem;
3. excluir apenas a fotografia e confirmar que o logotipo permanece;
4. excluir apenas o logotipo e confirmar que a fotografia permanece;
5. testar o caso em que fotografia e logotipo usam a mesma URL e confirmar que a primeira exclusão não quebra a imagem restante;
6. confirmar que a imagem removida deixa de aparecer no site público;
7. recarregar o perfil e confirmar persistência da exclusão;
8. executar `npm run build` no frontend;
9. executar `composer check` e `composer check-v1` na API.

### Ajuste funcional — e-mail automático de resultado da Avaliação Conjugal

Em 2026-10-06 foi implementado o envio automático de um resumo do resultado quando o segundo participante conclui a **Avaliação Conjugal**.

Fluxo implementado:

1. o segundo participante conclui;
2. a aplicação passa para `CONCLUIDA` dentro da transação;
3. `ResultService` calcula os scores direcionais e o score geral com algoritmo 2.0;
4. o `MailService` envia ao e-mail de contato os scores de A, B e o score geral;
5. o texto interpretativo do e-mail é escolhido pela porcentagem do score geral;
6. a devolutiva profissional permanece independente e pode ser enviada posteriormente.

Faixas narrativas do e-mail:

- 80,00–100,00% — Uma percepção compartilhada muito positiva;
- 60,00–79,99% — Uma boa compreensão, com espaço para aprofundar o diálogo;
- 40,00–59,99% — Uma oportunidade de se conhecerem melhor;
- 20,00–39,99% — Um convite à redescoberta;
- 0,00–19,99% — Um caminho para construir maior compreensão.

Os textos completos aprovados estão implementados no `MailService`. Eles são específicos da Avaliação Conjugal e não são aplicados automaticamente aos demais instrumentos.

Arquivos de código alterados nesta tarefa:

- `mapa-relacional-api/src/Service/MailService.php`;
- `mapa-relacional-api/src/Controller/ParticipantAccessController.php`;
- `mapa-relacional-api/public/index.php`.

Não foi necessária migration.

Comportamento de falha: se o SMTP falhar durante a conclusão final da Avaliação Conjugal, a transação é revertida e a API retorna erro de envio. Assim, o segundo participante pode tentar concluir novamente e a aplicação não fica marcada como concluída sem que o resultado automático seja entregue.

Validação local pendente:

```powershell
cd mapa-relacional-api
composer check
composer serve
```

Depois, concluir uma Avaliação Conjugal de teste com os dois participantes e confirmar:

1. o segundo participante recebe sucesso na conclusão;
2. o e-mail automático chega ao `email_contato`;
3. o e-mail mostra score de A, score de B e score geral;
4. o texto corresponde à faixa do score geral;
5. o e-mail informa que não substitui a devolutiva profissional;
6. a devolutiva profissional continua em rascunho/não liberada até ação do profissional;
7. com senha SMTP inválida temporariamente, a conclusão retorna erro e permanece disponível para nova tentativa.

### Ampliação — PDF completo anexado ao resultado automático

Em 2026-10-06 o resultado automático da Avaliação Conjugal foi ampliado para anexar um PDF completo ao e-mail enviado quando o segundo participante conclui.

O PDF reproduz a organização da tela profissional de resultados e inclui:

- comparação relacional e versão do instrumento;
- score geral e explicação do cálculo;
- texto interpretativo da faixa do score total;
- score de A e score de B;
- resultado por tópico;
- comparação item a item com Coincide/Diverge/Não comparável;
- itens marcados como Não se aplica;
- nome do profissional responsável;
- link para a plataforma;
- aviso de que o resultado automático não substitui a devolutiva profissional.

Arquivos de código envolvidos:

- `mapa-relacional-api/src/Service/ResultPdfService.php` (novo);
- `mapa-relacional-api/src/Service/MailService.php`;
- `mapa-relacional-api/src/Controller/ParticipantAccessController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-api/composer.json`.

Dependência adicionada:

- `dompdf/dompdf`.

Não há migration nova.

Antes de testar, executar `composer install` para instalar a nova dependência e depois `composer check`.

### Ajuste institucional — bloco de vídeo

Em 2026-10-06 o bloco `VIDEO` do site institucional foi consolidado como bloco visual completo.

Comportamento implementado:

- seleção do tipo Vídeo no editor;
- campo de URL específico;
- descrição acessível da mídia;
- pré-visualização do vídeo dentro do editor;
- suporte a YouTube, YouTube Shorts e Vimeo por incorporação responsiva;
- suporte a URL direta de arquivo por player HTML5;
- renderização pública responsiva em 16:9 com controles e tela cheia.

Arquivos alterados:

- `mapa-relacional-web/src/utils/video.js` (novo);
- `mapa-relacional-web/src/pages/ProfessionalSiteEditor.jsx`;
- `mapa-relacional-web/src/pages/PublicInstitutionalSite.jsx`.

O backend já aceitava o tipo `VIDEO` e a `midia_url`; portanto não foi necessária alteração de banco nem migration.

Validação pendente:

```powershell
cd mapa-relacional-web
npm run build
npm run dev
```

Depois, criar um bloco Vídeo no editor, testar ao menos uma URL do YouTube e confirmar a reprodução no site público.

### Ajuste funcional — exclusão segura de instrumentos, versões e avaliações

Em 2026-10-06 foi implementada a exclusão destrutiva controlada na área profissional, mantendo proteção explícita contra apagamento indireto de avaliações históricas.

Comportamento implementado:

- avaliações/aplicações podem ser excluídas pelo profissional proprietário, inclusive quando concluídas;
- a exclusão da avaliação remove, em transação, participantes da aplicação, acessos, respostas, itens “Não se aplica”, comparações, resultados direcionais, resultado geral e devolutiva;
- a exclusão da avaliação não remove instrumento, versão, pessoas permanentes nem vínculo administrativo;
- versões em qualquer estado podem ser excluídas quando não possuem aplicações vinculadas;
- a exclusão da versão remove seções, itens, alternativas e faixas de resultado;
- instrumentos podem ser excluídos quando nenhuma de suas versões possui aplicações vinculadas;
- a exclusão do instrumento remove todas as versões e respectivas estruturas;
- versões e instrumentos com aplicações vinculadas continuam bloqueados até que as avaliações que não precisam ser preservadas sejam excluídas individualmente;
- a interface usa confirmação explícita com descrição dos efeitos antes de cada exclusão permitida;
- exclusões bloqueadas por avaliações vinculadas exibem orientação ao profissional;
- eventos `AVALIACAO_EXCLUIDA`, `VERSAO_EXCLUIDA` e `INSTRUMENTO_EXCLUIDO` são registrados na auditoria.

Arquivos alterados nesta tarefa:

- `mapa-relacional-api/src/Controller/ApplicationController.php`;
- `mapa-relacional-api/src/Controller/InstrumentController.php`;
- `mapa-relacional-api/src/Controller/InstrumentVersionController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalApplications.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalApplicationDetail.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalInstruments.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalInstrumentVersions.jsx`;
- `docs/MAPA_RELACIONAL_CONTEXT.md`;
- `docs/DECISOES.md`;
- `docs/REQUISITOS_SISTEMA.md`;
- `docs/MAPA_RELACIONAL_STATUS.md`.

Não foi necessária migration nova; as remoções são feitas explicitamente em ordem segura dentro de transações.

Validação local pendente:

```powershell
cd mapa-relacional-api
composer check
composer check-v1
composer serve
```

Em outro terminal:

```powershell
cd mapa-relacional-web
npm run build
npm run dev
```

Validar:

1. cancelar cada confirmação e confirmar que nenhum dado é removido;
2. excluir uma avaliação concluída e confirmar que ela some da lista e que seus links antigos deixam de funcionar;
3. confirmar que pessoas cadastradas, vínculo, instrumento e versão permanecem após excluir somente a avaliação;
4. tentar excluir uma versão ainda vinculada a avaliação e confirmar o bloqueio;
5. depois de excluir as avaliações vinculadas, excluir a versão e confirmar a remoção de seções, itens, alternativas e faixas;
6. tentar excluir um instrumento com avaliações vinculadas e confirmar o bloqueio;
7. depois de remover as avaliações que não precisam ser preservadas, excluir o instrumento e confirmar a remoção de suas versões e estruturas;
8. abrir `/profissional/auditoria` e confirmar os eventos de exclusão.

### Ajuste funcional — lista consolidada de e-mails das avaliações

Em 2026-10-06 foi implementada a geração de uma lista consolidada dos e-mails cadastrados como contato das avaliações.

Backend:

```text
GET /api/profissional/aplicacoes/emails
```

A consulta:

- é restrita ao profissional autenticado;
- usa `aplicacoes.email_contato`;
- normaliza os endereços para minúsculas;
- remove duplicidades;
- retorna a quantidade de avaliações associadas a cada endereço;
- retorna a primeira e a última data de avaliação associadas ao endereço;
- retorna também o total de e-mails únicos e o total de avaliações representadas;
- registra `LISTA_EMAILS_GERADA` na auditoria somente com totais agregados.

Frontend em `/profissional/avaliacoes`:

- botão **Gerar lista de e-mails**;
- exibição da quantidade de endereços únicos;
- exibição do total de avaliações representadas;
- lista simples, um e-mail por linha;
- botão **Copiar e-mails**;
- botão **Baixar CSV**;
- CSV com e-mail, quantidade de avaliações, primeira avaliação e última avaliação;
- a lista usa todas as avaliações do profissional e não é limitada pelos filtros atuais da tela.

Arquivos alterados:

- `mapa-relacional-api/src/Controller/ApplicationController.php`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/pages/ProfessionalApplications.jsx`;
- `docs/MAPA_RELACIONAL_CONTEXT.md`;
- `docs/REQUISITOS_SISTEMA.md`;
- `docs/DECISOES.md`;
- `docs/MAPA_RELACIONAL_STATUS.md`.

Não foi necessária migration.

**Validação ainda não executada.** A implementação foi registrada, mas não considerar a funcionalidade validada até teste local posterior.

### Módulo — Biblioteca pública

Em 2026-10-06 foi implementado e **validado localmente com sucesso** o módulo de Biblioteca pública solicitado para a Plataforma Tânia.

Funcionalidades implementadas:

- área pública em `/biblioteca`;
- acesso livre, sem login ou cadastro;
- documentos PDF e PNG;
- título e descrição;
- busca por título, descrição e nome original do arquivo;
- pré-visualização de PNG e abertura direta de PDF/PNG;
- área profissional em `/profissional/biblioteca`;
- upload com validação real de MIME por `fileinfo`;
- limite padrão de 20 MB configurável por `LIBRARY_FILE_MAX_MB`;
- status ATIVO/INATIVO para publicar ou ocultar;
- edição de título, descrição e status;
- exclusão física do registro e do arquivo armazenado;
- auditoria para criação, atualização e exclusão;
- novo bloco institucional `BIBLIOTECA`;
- botão Biblioteca no cabeçalho público;
- migration `011_biblioteca_publica.sql`.

Arquivos principais:

- `mapa-relacional-api/src/Controller/LibraryController.php`;
- `mapa-relacional-api/migrations/011_biblioteca_publica.sql`;
- `mapa-relacional-api/public/index.php`;
- `mapa-relacional-web/src/pages/PublicLibrary.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalLibrary.jsx`;
- `mapa-relacional-web/src/services/api.js`;
- `mapa-relacional-web/src/App.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalDashboard.jsx`;
- `mapa-relacional-web/src/pages/ProfessionalSiteEditor.jsx`;
- `mapa-relacional-web/src/pages/PublicInstitutionalSite.jsx`.

### Validação local concluída

O usuário confirmou em 2026-10-06 que o módulo foi testado localmente e funcionou corretamente.

A validação confirmou o fluxo principal do módulo, incluindo acesso à área profissional, publicação de documentos, acesso público e busca.

**Estado:** implementado e validado localmente.

### Entrada no deploy

Com a biblioteca validada, o projeto entra na fase de **deploy/homologação**.

Domínios oficiais definidos em 2026-10-06:

```text
Site público: https://taniasantiago.com.br
API: https://api.taniasantiago.com.br
```

Antes de liberar produção:

1. atualizar o servidor a partir da branch `main`;
2. instalar dependências de produção;
3. configurar `.env` com URLs HTTPS, banco, JWT e SMTP;
4. garantir gravação em `public/uploads`, `storage/logs` e `storage/backups`;
5. aplicar migrations 001–011;
6. executar `composer check-domain`;
7. executar `composer check`;
8. gerar backup com `composer backup-db`;
9. executar `composer check-v1`;
10. gerar o frontend com `npm ci && npm run build`;
11. configurar Apache/HTTPS;
12. realizar homologação ponta a ponta no domínio definitivo.



### Correção de deploy — firebase/php-jwt

Em 2026-10-07, durante a instalação das dependências no Droplet, o Composer bloqueou a linha `firebase/php-jwt 6.x` por advisory de segurança.

Correção aplicada no repositório:

- `firebase/php-jwt` atualizado de `^6.10` para `^7.0`;
- nenhuma exceção de segurança foi adicionada ao Composer;
- o fluxo JWT existente permanece baseado em HS256 e `JWT_SECRET` com mínimo de 32 caracteres.

Validação pendente no servidor após `git pull`:

```bash
cd /var/www/plataforma-tania
git pull origin main
cd mapa-relacional-api
composer install --no-dev --optimize-autoloader
composer check
```

Depois da instalação, validar login profissional antes de avançar para a publicação.


### Deploy — dependências PHP e validação sintática no servidor

Em 2026-10-07, após atualizar `firebase/php-jwt` para a linha segura 7.x, a instalação das dependências PHP foi concluída no Droplet e o comando:

```bash
composer check
```

foi executado com sucesso.

Resultado: todos os arquivos PHP verificados pelo checklist passaram sem erros de sintaxe, incluindo API, autenticação JWT, geração de PDF, biblioteca pública, auditoria, rate limit, scripts de migração e utilitários de deploy.

**Estado desta etapa:** concluída.

**Próximo passo:** preparar banco e `.env` de produção, restaurar os dados necessários e somente depois configurar o VirtualHost da API.


## 2026-10-09 — Recuperação de senha profissional por e-mail (pendente de validação)

Implementação adicionada no GitHub:
- migration 012_profissional_recuperacao_senha.sql com tokens em hash, expiração e uso único;
- PasswordResetController com solicitação e redefinição pública, resposta genérica para e-mails inexistentes e limites de taxa;
- MailService com SMTP atual e links de recuperação;
- rotas POST /api/auth/esqueci-senha e POST /api/auth/redefinir-senha;
- frontend com link "Esqueci minha senha", tela de pedido e tela de nova senha.

A redefinição altera senha_hash e senha_alterada_em, invalidando os JWTs antigos pela regra vigente.

Estado: CÓDIGO VERSIONADO; NÃO HOMOLOGADO. Antes de publicar, executar composer migrate, composer check, npm run build e testes do fluxo por e-mail. Verificar migração 012, expiração, uso único, limites de taxa, e-mail inexistente, senha antiga e invalidação de tokens anteriores. O check-domain não foi alterado para validar nominalmente a tabela nova.

## 2026-10-10 — Título HTML configurável do site institucional

Implementado no GitHub e validado funcionalmente pelo usuário em 2026-10-10:
- migration `013_site_titulo.sql`: campo `profissionais.site_titulo`;
- API protegida `GET/PUT /api/profissional/site/configuracoes`;
- `GET /api/public/site` retorna `site_titulo` com valor padrão;
- editor em `/profissional/site` oferece campo "Título do site" e botão "Salvar título";
- a página institucional aplica `document.title` após carregar a configuração pública.

O título da aba é independente do bloco institucional do tipo TITULO. Limite de 160 caracteres. Sem novas variáveis de ambiente.

**Validação confirmada pelo usuário:** a definição do título do site está funcionando perfeitamente. Funcionalidade considerada concluída quanto ao fluxo funcional reportado. Não houve execução independente de `composer check` ou `npm run build` nesta atualização documental.

**Pendente separada:** a recuperação de senha por e-mail (migration 012) continua sem homologação, por escolha do usuário. A migration 013 integra a funcionalidade já reportada como funcional; verificar o registro de migrations e os checklists no próximo ciclo técnico, sem repetir testes agora.


## 2026-10-10 — Implantação no servidor e recuperação da conexão MySQL

**Marco:** API publicada e acesso ao banco corrigido, conforme testes e confirmação do usuário em produção. **Versão permanece `1.0.0-rc.1`** até homologação completa.

- API: `https://api.taniasantiago.com.br`; DocumentRoot do Apache: `/var/www/api.taniasantiago.com.br/public`; raiz da aplicação: `/var/www/api.taniasantiago.com.br`.
- Frontend: `https://taniasantiago.com.br`; DocumentRoot: `/var/www/taniasantiago.com.br`.
- Log específico do Apache: `/var/log/apache2/api_taniasantiago_error.log`.
- Deploy incremental dos arquivos de recuperação de senha e título configurável foi reenviado ao diretório correto; inicialmente houve envio a diretório incorreto e falha transitória de sintaxe no `public/index.php`.
- Diagnóstico confirmado: `php -l public/index.php` sem erros; `GET /api/health` respondeu HTTP 200 em produção em 2026-10-10.
- `GET /api/public/site` retornou inicialmente HTTP 500 com `PDOException SQLSTATE[HY000] [1698] Access denied for user 'root'@'localhost'`.
- Foi criado/configurado um usuário MySQL de aplicação `mapa_app` para o banco `mapa_relacional`, com credenciais armazenadas exclusivamente no `.env` de produção; o usuário confirmou após a correção: **“funcionou perfeitamente”**.
- **Não versionar senha, conteúdo do `.env`, logs com informações sensíveis nem credenciais.** O acesso MySQL resolvido não implica que todas as rotas e fluxos foram homologados.

**Verificações pendentes:** confirmar `APP_ENV=production` e `APP_DEBUG=false` (a resposta de health anterior informava `environment: development` e um erro HTTP 500 expôs stack trace); validar a aplicação completa no domínio; confirmar registro das migrations 012 e 013 em `schema_migrations` antes de qualquer reexecução; homologar a recuperação de senha por e-mail, ainda não testada; rodar os checklists de produção, backup e verificações de permissões.

**Próxima ação:** revisão de segurança e homologação ponta a ponta antes do encerramento formal da V1.
