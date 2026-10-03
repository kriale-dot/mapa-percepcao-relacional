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
