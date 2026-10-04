# Avaliação de Percepção Relacional — Registro de decisões

> Registro curto das decisões técnicas e funcionais que afetam a continuidade do projeto.
> Novas decisões relevantes devem ser acrescentadas ao final; não apagar decisões antigas sem registrar a substituição.

## D-001 — Repositório único
**Data:** 2026-10-03  
**Status:** vigente

O projeto permanece em um único repositório GitHub, separando backend, frontend, documentação e deploy.

## D-002 — Backend e frontend separados
**Data:** 2026-10-03  
**Status:** vigente

- Backend: PHP 8.2+, Slim 4 e MySQL 8.
- Frontend: React + Vite + Tailwind CSS v4.
- A comunicação entre ambos deve ocorrer pela API.

## D-003 — Migrações em `mapa-relacional-api/migrations/`
**Data:** 2026-10-03  
**Status:** vigente

As migrações SQL sequenciais ficam em `mapa-relacional-api/migrations/`.

O nome do arquivo continua sendo a identidade registrada em `schema_migrations`. Mover a pasta não altera o nome nem o checksum do conteúdo da migração já aplicada.

## D-004 — Segredos e dependências não são versionados
**Data:** 2026-10-03  
**Status:** vigente

Não entram no GitHub:

- arquivos `.env` reais;
- senhas, tokens e chaves;
- `vendor/`;
- `node_modules/`;
- artefatos de build e logs.

O repositório mantém somente `.env.example` sem credenciais reais. `vendor/` é criado localmente por `composer install`.

## D-005 — Documentos de continuidade
**Data:** 2026-10-03  
**Status:** vigente

Antes de uma nova etapa de desenvolvimento, consultar:

1. `docs/MAPA_RELACIONAL_CONTEXT.md`;
2. `docs/MAPA_RELACIONAL_STATUS.md`;
3. `docs/DECISOES.md`;
4. o código atual da branch `main`.

## D-006 — GitHub como fonte de verdade
**Data:** 2026-10-03  
**Status:** vigente

O estado do código deve ser confirmado no GitHub. O histórico de conversas não substitui o repositório.

Ao concluir uma etapa relevante:

1. testar;
2. atualizar o STATUS;
3. atualizar CONTEXT ou DECISOES quando houver mudança estrutural;
4. commit;
5. push.


## D-007 — Fluxo oficial de recriação do banco
**Data:** 2026-10-03  
**Status:** vigente

Para recriar o banco de desenvolvimento, o fluxo oficial é:

1. criar o banco `mapa_relacional` vazio;
2. executar `composer migrate`;
3. executar `composer check-domain`.

Não importar `001_base_dominio.sql` manualmente quando o objetivo for testar o fluxo normal de instalação. O runner cria `schema_migrations`, aplica as migrations pendentes e registra o checksum.

## D-008 — Validação de tabelas independente de capitalização
**Data:** 2026-10-03  
**Status:** vigente

O `bin/check-domain.php` deve ler a lista de tabelas com `PDO::FETCH_COLUMN`. Isso evita diferenças entre drivers/ambientes que retornam `table_name` ou `TABLE_NAME`.

## D-009 — Nomenclatura oficial do instrumento
**Data:** 2026-10-03  
**Status:** vigente

O nome oficial a ser usado nos textos, interface, documentação e comunicação com usuários é **Avaliação de Percepção Relacional**.

Os nomes técnicos já existentes do repositório, diretórios, namespaces, banco e caminhos internos que usam `mapa-relacional` ou `mapa-percepcao-relacional` permanecem inalterados por enquanto para evitar renomeações sem benefício funcional e preservar a continuidade do desenvolvimento.

## D-010 — Raiz local oficial de desenvolvimento
**Data:** 2026-10-03  
**Status:** vigente

No ambiente Windows utilizado para desenvolvimento, a raiz oficial do repositório é:

```text
E:\\Compartilhar\\Kriale\\Tânia - plataforma digital\\Desenvolvimento
```

O repositório `kriale-dot/mapa-percepcao-relacional` é clonado diretamente nessa pasta. Não deve ser criada uma subpasta local adicional `mapa-percepcao-relacional` para conter o projeto.

A branch padrão de desenvolvimento é `main`, acompanhando `origin/main`. Antes de iniciar novas alterações locais após mudanças feitas no GitHub, executar `git pull` e confirmar `git status` limpo ou compreender explicitamente as alterações locais existentes.

## D-011 — Autenticação profissional por e-mail e senha
**Data:** 2026-10-03  
**Status:** vigente

A área profissional será autenticada por **e-mail + senha**.

A senha nunca será armazenada em texto puro. O banco guarda somente `senha_hash`, compatível com `password_hash()` e `password_verify()` do PHP.

A migration `002_profissional_autenticacao.sql` acrescenta à tabela `profissionais`:

- `senha_hash VARCHAR(255) NULL`;
- `senha_alterada_em DATETIME NULL`;
- `ultimo_login_em DATETIME NULL`.

`senha_hash` é nulo apenas durante a configuração inicial. Um profissional sem hash de senha não poderá autenticar.

Após autenticação válida, a API utilizará JWT. A proteção das rotas será implementada nas próximas subetapas da Etapa 3.

## D-012 — Primeiro profissional criado somente por CLI
**Data:** 2026-10-03  
**Status:** substituída por D-013

A configuração inicial do primeiro profissional não terá cadastro público.

O bootstrap é realizado pelo comando:

```text
composer setup-professional
```

Regras:

- só pode executar com a tabela `profissionais` vazia;
- nome, e-mail e telefone são informados no terminal;
- a senha inicial entra somente pela variável temporária `SETUP_PROFESSIONAL_PASSWORD`;
- a senha é transformada imediatamente por `password_hash(PASSWORD_DEFAULT)`;
- senha em texto puro não é persistida nem registrada pelo sistema;
- após existir um profissional, o setup inicial é bloqueado;
- `composer check-professional` valida a configuração sem precisar conhecer a senha.

## D-013 — Provisionamento inicial sem cadastro público
**Data:** 2026-10-03  
**Status:** vigente

Substitui D-012 quanto ao meio de provisionamento.

Não haverá endpoint público para criação do primeiro profissional. Em desenvolvimento, o primeiro profissional pode ser provisionado diretamente no banco de dados/phpMyAdmin ou pelo utilitário de CLI, desde que a senha seja armazenada somente como hash compatível com `password_verify()`.

Se já existir um profissional inicial, o registro deve ser preservado e não deve ser criada duplicata.

## D-014 — Proteção das rotas profissionais por middleware JWT
**Data:** 2026-10-03  
**Status:** vigente

Todas as rotas profissionais sob `/api/profissional` devem passar pelo middleware de autenticação JWT.

O middleware exige `Authorization: Bearer <token>`, valida o JWT HS256, o emissor, a expiração, o tipo `professional` e o `sub` com o ID do profissional. Além da validação criptográfica, o profissional é consultado no banco em cada requisição protegida e precisa continuar com `status = ATIVO`.

Essa consulta permite bloquear imediatamente um token ainda não expirado caso o profissional seja desativado no banco.

## D-015 — Sessão profissional no frontend
**Data:** 2026-10-03  
**Status:** vigente

Na V1, o frontend profissional usa o JWT Bearer emitido pela API e mantém o token em `sessionStorage`.

A sessão deve:

- persistir durante recargas da página na mesma sessão do navegador;
- ser encerrada quando o usuário clicar em sair;
- ser invalidada localmente quando `GET /api/profissional/me` retornar `401`;
- não armazenar senha no navegador;
- reenviar o JWT apenas em chamadas autenticadas da área profissional.

As rotas iniciais do frontend são `/profissional/login` e `/profissional`.

## D-016 — Perfil profissional da V1
**Data:** 2026-10-03  
**Status:** vigente

O perfil profissional é mantido no próprio registro de `profissionais`. A migration `003_profissional_perfil.sql` acrescenta `descricao`, `atuacao`, `foto_url`, `logo_url` e `dados_contato`.

Na V1 desta etapa:

- fotografia e logotipo são referências por URL HTTP/HTTPS; upload de arquivos não é implementado ainda;
- nome, e-mail, telefone, descrição, atuação e dados de contato podem ser editados pelo profissional autenticado;
- o status é exibido, porém não pode ser alterado pelo próprio formulário de perfil, pois participa da regra de autorização;
- as rotas de leitura e atualização do perfil permanecem protegidas pelo middleware JWT.

## D-017 — Alteração segura de senha
**Data:** 2026-10-03  
**Status:** vigente

A troca de senha do profissional é autenticada e exige a senha atual.

Regras:

- endpoint protegido: `PUT /api/profissional/senha`;
- a nova senha deve ter pelo menos 8 caracteres e ser diferente da senha atual;
- a senha atual é validada com `password_verify()`;
- a nova senha é armazenada somente por `password_hash(PASSWORD_DEFAULT)`;
- `senha_alterada_em` é atualizado após sucesso;
- o JWT contém uma impressão digital SHA-256 do hash vigente da senha;
- o middleware compara essa impressão com o hash atual do banco, invalidando tokens antigos após uma troca de senha;
- após alterar a senha, o frontend remove o JWT local e exige novo login.

## D-018 — Ciclo de vida inicial dos instrumentos
**Data:** 2026-10-03  
**Status:** vigente

Na V1, os instrumentos pertencem ao profissional autenticado e usam os estados `RASCUNHO`, `ATIVO` e `ARQUIVADO`.

A exclusão física de um instrumento somente é permitida quando ele ainda não possui nenhuma versão. Se já houver versão vinculada, a API recusa a exclusão para preservar a estrutura histórica; nesse caso, o instrumento deve ser arquivado.

Todas as consultas e alterações de instrumentos devem ser filtradas por `profissional_id`, impedindo acesso cruzado entre profissionais.

## D-019 — Imutabilidade das versões publicadas
**Data:** 2026-10-04  
**Status:** vigente

A V1 adota regra mais conservadora que o mínimo exigido pelo RF-033: a imutabilidade começa no momento da publicação, mesmo antes de a versão ser utilizada em uma aplicação.

Regras:

- novas versões nascem como `RASCUNHO`;
- somente `RASCUNHO` pode ter o número editado;
- `RASCUNHO` pode ser publicado;
- `PUBLICADA` é imutável e pode ser arquivada;
- `ARQUIVADA` permanece imutável;
- alterações após publicação exigem criação de uma nova versão;
- rascunhos só podem ser excluídos quando não possuem seções nem aplicações vinculadas;
- o número da versão é único dentro de cada instrumento.

Essa regra reduz o risco de uma versão publicada mudar silenciosamente e simplifica a preservação histórica.
