# Mapa de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-02  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.1.0-dev`  
**Marco atual:** fundação técnica em andamento  
**Etapa atual:** Etapa 1 — backend base criado  
**Próximo passo:** validar backend local e iniciar frontend React/Vite/Tailwind

## 1. Situação atual

A estrutura inicial do repositório está concluída e a fundação do backend Slim 4 foi criada.

Bases conceituais já registradas:

- nome: Mapa de Percepção Relacional;
- subtítulo: Instrumento de percepção mútua e conhecimento interpessoal;
- aplicação para diferentes tipos de vínculo;
- comparação de percepção entre duas pessoas;
- site institucional como entrada pública;
- área separada para participante;
- área separada para profissional;
- identidade visual e paleta aprovadas.

## 2. Etapa 0 — concluída

Concluído em 2026-10-02:

- repositório GitHub criado;
- acesso do conector autorizado;
- README inicial criado;
- CONTEXT criado;
- STATUS criado;
- `.gitignore` criado;
- diretórios-base representados no GitHub.

## 3. Etapa 1 — backend base criado

Arquivos principais:

```text
mapa-relacional-api/
├── bin/migrate.php
├── database/README.md
├── public/index.php
├── src/Config/Database.php
├── src/Config/LoggerFactory.php
├── src/Controller/HealthController.php
├── src/Middleware/CorsMiddleware.php
├── storage/logs/.gitkeep
├── .env.example
├── composer.json
└── README.md
```

Implementado:

- dependências Slim 4 definidas no Composer;
- autoload PSR-4 `App\\`;
- carregamento de `.env`;
- conexão PDO/MySQL;
- log em arquivo com Monolog;
- CORS configurado por `FRONTEND_URL`;
- endpoint `GET /api`;
- endpoint `GET /api/health`;
- endpoint `GET /api/health/database`;
- runner de migrações SQL sequenciais;
- controle de migrações por nome e checksum SHA-256;
- scripts Composer `serve`, `migrate` e `check`.

O `.gitignore` foi ajustado para **não ignorar migrações SQL nem `composer.lock`**, pois ambos devem ser versionados no projeto.

## 4. Validação ainda pendente

O código foi criado no GitHub, mas ainda precisa ser executado no ambiente local.

Pendente confirmar:

- `composer install`;
- criação do banco local `mapa_relacional`;
- cópia de `.env.example` para `.env`;
- `composer migrate`;
- `composer check`;
- servidor local em `localhost:8383`;
- resposta de `GET /api/health`;
- resposta de `GET /api/health/database`.

Não considerar o backend validado até esses testes serem executados.

## 5. Próxima sequência da Etapa 1

1. validar o backend local;
2. iniciar projeto React/Vite;
3. adicionar Tailwind CSS v4;
4. criar configuração de API no frontend;
5. testar frontend → `GET /api/health`;
6. fechar a Etapa 1 somente depois da execução local dos dois lados.

## 6. Ainda não implementado

- frontend funcional React/Vite;
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
