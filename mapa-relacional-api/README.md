# mapa-relacional-api

Backend da plataforma **Mapa de Percepção Relacional**.

## Stack

- PHP 8.2+
- Slim 4
- MySQL 8
- PDO
- JWT
- PHP dotenv
- Monolog

## Estrutura inicial

```text
mapa-relacional-api/
├── bin/
│   └── migrate.php
├── migrations/
│   ├── 001_base_dominio.sql
│   └── README.md
├── public/
│   └── index.php
├── src/
│   ├── Config/
│   │   ├── Database.php
│   │   └── LoggerFactory.php
│   ├── Controller/
│   │   └── HealthController.php
│   └── Middleware/
│       └── CorsMiddleware.php
├── storage/
│   └── logs/
├── vendor/              # gerado localmente; não versionado
├── .env                  # local; não versionado
├── .env.example
└── composer.json
```

## Preparação local

No Windows PowerShell:

```powershell
cd mapa-relacional-api
Copy-Item .env.example .env
composer install
```

Edite o arquivo `.env` com os dados do MySQL local.

Crie um banco vazio:

```sql
CREATE DATABASE mapa_relacional
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Depois execute:

```powershell
composer migrate
composer check
composer serve
```

A API ficará disponível em:

```text
http://localhost:8383
```

## Endpoints de fundação

### API

```text
GET /api
```

### Saúde da aplicação

```text
GET /api/health
```

### Saúde do banco

```text
GET /api/health/database
```

O endpoint de aplicação não depende do banco. O endpoint de banco executa uma conexão real e um `SELECT 1`.

## Migrações

Os arquivos SQL serão adicionados em `migrations/` com nomes sequenciais:

```text
001_nome.sql
002_nome.sql
003_nome.sql
```

O comando:

```powershell
composer migrate
```

cria a tabela `schema_migrations`, executa apenas migrações ainda não aplicadas e valida o checksum SHA-256 das migrações já registradas.

## Continuidade

Antes de mudanças estruturais, consultar:

- `../docs/MAPA_RELACIONAL_CONTEXT.md`
- `../docs/MAPA_RELACIONAL_STATUS.md`
