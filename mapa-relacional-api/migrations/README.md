# Migrações do banco

As migrações SQL deste projeto são sequenciais e imutáveis depois de aplicadas.

Formato recomendado:

```text
001_nome_da_migracao.sql
002_nome_da_migracao.sql
003_nome_da_migracao.sql
```

Para executar:

```bash
composer migrate
```

O runner cria e usa a tabela `schema_migrations`, armazenando o nome do arquivo e seu checksum SHA-256.

## Regras

- não alterar uma migração já aplicada;
- criar uma nova migração para cada mudança estrutural;
- não armazenar dados sensíveis nos arquivos SQL;
- confirmar o banco/ambiente antes de executar migrações;
- manter as migrações compatíveis com MySQL 8.
