# Mapa de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-02  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.1.0`  
**Marco atual:** fundação técnica concluída  
**Etapa atual:** Etapa 1 — concluída  
**Próxima etapa:** Etapa 2 — modelagem funcional e primeira migração

## 1. Situação atual

A fundação técnica foi concluída e validada localmente: backend Slim 4, MySQL, frontend React/Vite/Tailwind e comunicação frontend → API estão funcionando.

A modelagem conceitual inicial da V1 foi registrada em:

```text
docs/MODELO_DOMINIO_V1.md
```

## 2. Etapa 0 — concluída

Concluído em 2026-10-02:

- repositório GitHub criado;
- acesso do conector autorizado;
- README, CONTEXT e STATUS criados;
- `.gitignore` criado;
- diretórios-base definidos.

## 3. Etapa 1 — concluída

### Backend

Implementado e validado:

- Slim 4;
- PHP 8.2+;
- carregamento de `.env`;
- conexão PDO/MySQL;
- Monolog;
- CORS por `FRONTEND_URL`;
- `GET /api`;
- `GET /api/health`;
- `GET /api/health/database`;
- migrações SQL sequenciais;
- controle de migrações por checksum SHA-256;
- `composer install`: OK;
- `composer migrate`: OK;
- `composer check`: OK;
- API local em `localhost:8383`: OK;
- conexão real com MySQL: OK.

### Frontend

Implementado e validado:

- React 19;
- Vite 8;
- Tailwind CSS v4;
- variável `VITE_API_URL`;
- serviço central de API;
- tela institucional inicial;
- identidade visual inicial;
- frontend local em `localhost:5173`: OK;
- frontend → `GET /api/health`: OK;
- indicador `API conectada`: OK;
- `npm run build`: OK;
- build Vite de produção gerado em `dist/` sem erros.

Resultado do build validado em 2026-10-02:

```text
vite v8.3.2
17 modules transformed
dist/index.html
dist/assets/*.css
dist/assets/*.js
build concluído com sucesso
```

## 4. Etapa 2 — modelagem funcional

Documento inicial criado:

```text
docs/MODELO_DOMINIO_V1.md
```

Modelo conceitual proposto:

- profissionais;
- pessoas;
- vínculos;
- instrumentos;
- versões;
- seções;
- itens;
- alternativas;
- aplicações;
- participantes da aplicação;
- respostas;
- comparações;
- resultados;
- comentários profissionais;
- relatórios;
- convites;
- auditoria.

A primeira migração funcional deve permanecer enxuta e criar apenas a base necessária para cadastro, instrumento e coleta de respostas.

## 5. Próximo passo

Criar a primeira migração funcional:

```text
database/001_base_dominio.sql
```

Escopo previsto:

1. profissionais;
2. pessoas;
3. vínculos;
4. instrumentos;
5. versões de instrumento;
6. seções;
7. itens;
8. alternativas;
9. aplicações;
10. participantes da aplicação;
11. respostas.

Depois:

- executar `composer migrate`;
- confirmar tabelas;
- iniciar endpoints de cadastro básicos.

## 6. Ainda não implementado

- primeira migração funcional do domínio;
- autenticação;
- perfis/permissões;
- endpoints de cadastro;
- motor de avaliação;
- comparação automática;
- resultados;
- relatórios;
- convites/acesso remoto;
- deploy;
- testes automatizados.

## 7. Decisões ainda abertas

- perfis oficiais do sistema;
- fluxo de cadastro/autenticação dos participantes;
- modo de convite;
- envio por e-mail/SMS/WhatsApp;
- política de disponibilização dos resultados;
- configuração definitiva das faixas interpretativas;
- conteúdo definitivo do site institucional;
- domínio de produção.

Essas decisões não devem ser inventadas silenciosamente durante o desenvolvimento.

## 8. Regra para atualizar este STATUS

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
