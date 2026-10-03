# Avaliação de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-03  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.2.0-dev`  
**Marco atual:** banco base recriado e domínio validado com sucesso  
**Etapa atual:** Etapa 2 concluída — fundação técnica, migração base e validação do domínio  
**Próximo passo:** iniciar a Etapa 3.1 — estrutura de autenticação do profissional no banco de dados

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

O próximo passo técnico permanece a Etapa 3, começando pela **Etapa 3.1 — estrutura de autenticação do profissional no banco de dados**.

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
