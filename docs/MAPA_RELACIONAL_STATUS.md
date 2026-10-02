# Mapa de Percepção Relacional — Status atual

> Documento de checkpoint. Atualizar ao final de cada etapa relevante, correção ou mudança de estado do projeto.

**Data do checkpoint:** 2026-10-02  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`  
**Versão:** `0.0.0`  
**Marco atual:** estrutura inicial do projeto criada  
**Etapa atual:** Etapa 0 — concluída  
**Próxima etapa:** Etapa 1 — fundação técnica

## 1. Situação atual

A estrutura inicial do repositório foi criada no GitHub.

Já estão definidas e registradas as bases conceituais do produto:

- nome: Mapa de Percepção Relacional;
- subtítulo: Instrumento de percepção mútua e conhecimento interpessoal;
- aplicação para diferentes tipos de vínculo, não somente casais;
- comparação de percepção entre duas pessoas;
- site institucional como entrada pública;
- área separada para participante;
- área separada para profissional;
- identidade visual e paleta aprovadas;
- logotipo já criado.

## 2. Estrutura inicial

```text
mapa-percepcao-relacional/
├── mapa-relacional-api/
├── mapa-relacional-web/
├── docs/
│   ├── MAPA_RELACIONAL_CONTEXT.md
│   └── MAPA_RELACIONAL_STATUS.md
├── deploy/
├── README.md
└── .gitignore
```

Arquitetura lógica:

```text
Site institucional
       ↓
Frontend React/Vite
       ↓
API Slim 4
       ↓
MySQL
```

Áreas previstas:

```text
/                    → institucional
/avaliacao/...       → participante
/profissional/...    → profissional
```

## 3. Regra funcional central registrada

Para participantes A e B:

```text
A → A
A → B
B → B
B → A
```

Comparações:

```text
A → B × B → B
B → A × A → A
```

Itens “Não se aplica” devem ser excluídos das comparações válidas.

O modelo percentual atual é:

```text
acertos / comparações válidas × 100
```

## 4. Etapa 0 — concluída

Concluído em 2026-10-02:

- repositório GitHub criado;
- acesso do conector autorizado;
- README inicial criado;
- CONTEXT criado;
- STATUS criado;
- `.gitignore` criado;
- diretórios-base representados no GitHub.

## 5. Próxima etapa — Etapa 1: fundação técnica

1. iniciar API Slim 4;
2. configurar `.env.example`;
3. criar conexão MySQL;
4. configurar migrações;
5. criar projeto React/Vite/Tailwind;
6. configurar comunicação frontend ↔ API;
7. criar health check;
8. validar execução local de backend e frontend.

## 6. Ainda não implementado

- estrutura funcional PHP/Slim;
- estrutura funcional React/Vite;
- banco de dados;
- migrações;
- autenticação;
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
