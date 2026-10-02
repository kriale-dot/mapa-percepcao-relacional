# Mapa de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-02  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.1.0-dev`  
**Marco atual:** fundação técnica em andamento  
**Etapa atual:** Etapa 1 — backend e frontend integrados localmente  
**Próximo passo:** validar build de produção do frontend e fechar a Etapa 1

## 1. Situação atual

A estrutura inicial do repositório está concluída. O backend Slim 4 foi validado localmente e o frontend React/Vite/Tailwind está executando com comunicação real com a API local.

## 2. Etapa 0 — concluída

Concluído em 2026-10-02:

- repositório GitHub criado;
- acesso do conector autorizado;
- README inicial criado;
- CONTEXT criado;
- STATUS criado;
- `.gitignore` criado;
- diretórios-base representados no GitHub.

## 3. Backend — validado localmente

Implementado:

- Slim 4;
- carregamento de `.env`;
- conexão PDO/MySQL;
- Monolog;
- CORS por `FRONTEND_URL`;
- `GET /api`;
- `GET /api/health`;
- `GET /api/health/database`;
- runner de migrações SQL sequenciais;
- controle por nome e checksum SHA-256;
- scripts Composer `serve`, `migrate` e `check`.

Validação executada em 2026-10-02:

- `composer install`: OK;
- banco `mapa_relacional`: acessível;
- `composer migrate`: OK, 0 migrações funcionais pendentes;
- tabela `schema_migrations`: criada;
- `composer check`: todos os arquivos principais sem erros de sintaxe;
- `composer serve`: OK em `localhost:8383`;
- `GET /api/health`: OK;
- `GET /api/health/database`: OK / banco conectado.

## 4. Frontend — integração local validada

Criado no GitHub:

```text
mapa-relacional-web/
├── src/
│   ├── services/api.js
│   ├── App.jsx
│   ├── main.jsx
│   └── styles.css
├── .env.example
├── index.html
├── package.json
├── vite.config.js
└── README.md
```

Implementado e validado:

- React 19;
- Vite 8;
- Tailwind CSS v4 via plugin Vite;
- variável `VITE_API_URL`;
- serviço central de acesso à API;
- aplicação abriu corretamente em `http://localhost:5173`;
- identidade visual/Tailwind aplicados corretamente;
- consulta automática a `GET /api/health`;
- indicador exibiu `API conectada` com o backend em execução;
- comunicação frontend → API validada localmente.

Observação operacional:

- backend local deve permanecer ativo em `localhost:8383`;
- frontend local deve permanecer ativo em `localhost:5173`;
- se o backend estiver encerrado, o frontend mostra `API indisponível`, comportamento esperado.

## 5. Última validação da Etapa 1

Pendente apenas validar o build de produção do frontend:

```powershell
cd mapa-relacional-web
npm run build
```

O resultado esperado é a criação de `dist/` sem erros.

Após esse teste, a Etapa 1 poderá ser encerrada e a próxima etapa será a definição/modelagem inicial do domínio antes da autenticação e dos cadastros.

## 6. Ainda não implementado

- schema funcional do domínio;
- autenticação;
- perfis/permissões;
- cadastros;
- motor de avaliação;
- cálculo;
- resultados;
- relatórios;
- deploy;
- testes automatizados.

## 7. Pendências de decisão

Devem ser fechadas conforme a implementação avançar:

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

Não transformar este arquivo em changelog detalhado. Ele deve continuar curto o suficiente para ser lido rapidamente no início de uma nova conversa.
