# Avaliação de Percepção Relacional — Contexto permanente do projeto

> Documento curto para iniciar novas conversas de desenvolvimento sem depender do histórico completo do chat.
> Repositório oficial: `kriale-dot/mapa-percepcao-relacional`
> Branch principal: `main`

## 1. Identidade do produto

**Nome:** Avaliação de Percepção Relacional  
**Subtítulo:** Instrumento de percepção mútua e conhecimento interpessoal.

A Avaliação de Percepção Relacional é uma plataforma digital para comparar, de forma estruturada, como duas pessoas percebem a si mesmas, a outra pessoa e a relação entre elas.

### Regra de nomenclatura

A denominação oficial do produto e do instrumento, em toda comunicação com usuários, documentação funcional e interface, é **Avaliação de Percepção Relacional**.

Os nomes técnicos já existentes do repositório, diretórios e componentes internos — como `mapa-percepcao-relacional`, `mapa-relacional-api` e `mapa-relacional-web` — permanecem inalterados por enquanto para preservar a continuidade técnica do projeto.

O instrumento não é exclusivo para casais. Deve permitir diferentes tipos de vínculo, por exemplo:

- casal/cônjuges;
- pais e filhos;
- amigos;
- familiares;
- patrão e empregado;
- líder e liderado;
- outros vínculos definidos pelo profissional.

## 2. Objetivo central

A plataforma deve ajudar a identificar:

- quanto uma pessoa conhece a percepção da outra;
- quanto suas necessidades, sentimentos, opiniões e preferências estão sendo percebidos corretamente;
- convergências e divergências de percepção;
- pontos de sintonia;
- áreas que podem precisar de diálogo ou intervenção profissional.

A plataforma é um instrumento de percepção e comparação. Não deve apresentar conclusões clínicas automáticas como diagnóstico.

## 3. Estrutura geral do produto

A primeira página da plataforma será um **site institucional público da profissional**.

A solução deve integrar três áreas principais:

```text
/
├── Site institucional público
│
├── /avaliacao/...
│   └── Área do participante
│
└── /profissional/...
    └── Área profissional
```

A API deve permanecer separada da interface.

### Área pública

Deve apresentar a profissional, sua atuação, informações sobre o instrumento e meios de contato/acesso.

### Área profissional

O profissional poderá, progressivamente:

- cadastrar participantes;
- definir o tipo de vínculo entre duas pessoas;
- criar uma aplicação;
- acompanhar o preenchimento;
- consultar resultados comparativos;
- registrar comentários profissionais;
- disponibilizar resultados conforme as regras do sistema.

### Área do participante

O participante deve acessar somente a avaliação que lhe foi atribuída e as informações explicitamente liberadas para ele.

## 4. Lógica relacional da avaliação

Para duas pessoas, A e B, a estrutura conceitual é:

```text
A → A = o que A responde sobre si
A → B = o que A acredita/percebe sobre B

B → B = o que B responde sobre si
B → A = o que B acredita/percebe sobre A
```

As comparações principais são:

```text
A → B  ×  B → B
B → A  ×  A → A
```

Isso permite medir o quanto cada pessoa percebe corretamente a outra.

Itens marcados como **“Não se aplica”** não devem entrar no denominador das comparações válidas.

Regra percentual de referência do material atual:

```text
percentual = acertos / comparações válidas × 100
```

As faixas atuais do material-base são:

- 0–33: ruim;
- 34–66: regular;
- 67–100: bom.

Essas faixas devem ser tratadas como regra do instrumento atual e permanecer configuráveis/versionáveis caso o modelo seja refinado posteriormente.

### Fluxo operacional inicial do instrumento

O material-base da avaliação define também estas regras para a primeira versão:

- para iniciar uma avaliação, existe um **e-mail de contato** associado à aplicação;
- uma aplicação gera **dois acessos**, um para cada participante;
- cada acesso dá direito ao preenchimento de um formulário por um dos participantes;
- por segurança, a implementação deve tratar essas “senhas” como **códigos/tokens de acesso individuais**, armazenados somente em hash no banco;
- cada participante informa, no contexto do formulário, dados de identificação como nome, idade e gênero;
- para vínculos do tipo casal, o material-base também solicita tempo de união; no sistema generalizado isso será tratado como informação do vínculo/aplicação;
- cada item apresenta duas perspectivas ao respondente: resposta sobre si e resposta sobre a outra pessoa;
- quando um item é marcado como **“Não se aplica”**, ele deve ser excluído daquela aplicação, não ser apresentado ao outro participante quando ainda não respondido e não entrar no denominador da pontuação;
- o profissional pode criar vários instrumentos/avaliações nesse formato;
- o profissional visualiza os resultados e pode registrar comentário profissional;
- o resultado deve poder ser disponibilizado aos participantes pelo e-mail de contato da aplicação.

A plataforma é generalizada para diferentes vínculos, portanto termos conjugais do material-base devem ser convertidos para participante A/B e tipo de vínculo sem perder a lógica original.

## 5. Modelo conceitual inicial

Entidades esperadas:

- Profissional
- Participante
- Relação/Vínculo
- Par relacional
- Instrumento
- Versão do instrumento
- Seção/Tópico
- Item/Pergunta
- Alternativa/Resposta
- Aplicação
- Convite/Acesso
- Resposta
- Comparação
- Resultado
- Comentário profissional
- Relatório/Devolutiva
- Auditoria

A modelagem definitiva será criada durante as etapas iniciais da implementação.

## 6. Identidade visual

Paleta oficial aprovada:

- Verde escuro: `#385048`
- Verde médio: `#88B098`
- Verde claro: `#A8C8B8`
- Dourado/bege: `#D8B078`
- Azul suave: `#A8C8D0`
- Fundo claro: `#FEFDFB`

Usar o logotipo oficial da **Avaliação de Percepção Relacional** já criado para o projeto.

A interface deve ser:

- clara;
- acolhedora;
- profissional;
- de leitura simples;
- responsiva para desktop, tablet e celular.

## 7. Stack técnica inicial

Para acelerar o desenvolvimento e reaproveitar o padrão já dominado em outros projetos, a base recomendada é:

### Backend
- PHP 8.2+
- Slim 4
- MySQL 8
- PDO
- JWT
- PHP dotenv
- Monolog

### Frontend
- React
- Vite
- JavaScript/JSX
- Tailwind CSS v4

### Estrutura sugerida

```text
mapa-percepcao-relacional/
├── mapa-relacional-api/
├── mapa-relacional-web/
├── docs/
├── deploy/
├── README.md
└── .gitignore
```

O banco deve evoluir por migrações SQL sequenciais armazenadas em:

```text
mapa-relacional-api/migrations/
```

### Ambiente de desenvolvimento local

A raiz oficial do projeto no ambiente Windows de desenvolvimento é:

```text
E:\\Compartilhar\\Kriale\\Tânia - plataforma digital\\Desenvolvimento
```

O repositório Git foi clonado diretamente nessa pasta. Portanto, **não existe uma pasta intermediária `mapa-percepcao-relacional` no caminho local**. A própria pasta `Desenvolvimento` contém `.git`, `mapa-relacional-api`, `mapa-relacional-web`, `docs`, `deploy` e os arquivos da raiz do repositório.

A branch de trabalho padrão é `main`, sincronizada com `origin/main` do repositório `kriale-dot/mapa-percepcao-relacional`.

### Fluxo padrão para recriar/validar o banco

Quando for necessário recriar o banco de desenvolvimento do zero:

```text
1. criar o banco vazio;
2. executar composer migrate;
3. executar composer check-domain.
```

O arquivo de migration não deve ser importado manualmente quando o objetivo for validar o fluxo normal da aplicação. O runner é responsável por criar e manter `schema_migrations`.

### Autenticação profissional

A autenticação da área profissional usa **e-mail + senha**, com a senha armazenada somente como hash no banco. A implementação deve usar as funções nativas do PHP `password_hash()` e `password_verify()`, sem armazenar senha em texto puro.

A tabela `profissionais` possui os campos de autenticação `senha_hash`, `senha_alterada_em` e `ultimo_login_em`. O campo `senha_hash` pode permanecer nulo somente enquanto a configuração inicial do primeiro profissional ainda não tiver sido concluída.

Após login válido, a API emite JWT HS256 conforme as variáveis `JWT_SECRET` e `JWT_TTL_SECONDS`.

As rotas da área profissional ficam sob o prefixo `/api/profissional` e devem utilizar middleware de autenticação no backend. O cliente envia o token no cabeçalho `Authorization: Bearer <token>`. O middleware valida assinatura, expiração, emissor, tipo do token e ID do profissional, consulta o profissional no banco e só libera a requisição quando o registro continua existente e com `status = ATIVO`.

No frontend da V1, a área profissional usa as rotas `/profissional/login` e `/profissional`. O JWT é mantido em `sessionStorage`, persistindo durante recargas da página, mas sendo descartado ao encerrar a sessão da aba/navegador. Ao abrir a área profissional, o frontend valida o token em `GET /api/profissional/me`; em resposta `401`, remove o token local e retorna ao login.

A alteração de senha autenticada usa `PUT /api/profissional/senha`. O backend exige a senha atual, valida a nova senha, grava somente um novo `password_hash()` e atualiza `senha_alterada_em`. O JWT profissional inclui uma impressão digital derivada do hash atual da senha; o middleware compara essa impressão com o hash vigente no banco. Assim, quando a senha muda, tokens emitidos antes da alteração deixam de ser aceitos. O frontend encerra a sessão local e exige novo login após a troca de senha.

### Instrumentos

O gerenciamento inicial de instrumentos da V1 é acessado em `/profissional/instrumentos` e utiliza rotas protegidas sob `/api/profissional/instrumentos`.

Cada instrumento pertence ao profissional autenticado e possui nome, descrição e status. Os estados adotados nesta etapa são `RASCUNHO`, `ATIVO` e `ARQUIVADO`.

A exclusão física é permitida somente enquanto o instrumento não possui versões. Depois que houver ao menos uma versão, o histórico deve ser preservado e o instrumento pode ser arquivado em vez de excluído.

### Versões do instrumento

Cada versão pertence a um instrumento e possui `numero_versao`, `status` e `publicado_em`.

Estados adotados:

- `RASCUNHO`;
- `PUBLICADA`;
- `ARQUIVADA`.

Na V1 foi adotada uma regra conservadora de imutabilidade: somente versões em `RASCUNHO` podem ser editadas ou excluídas. Ao publicar, a versão passa a ser imutável; qualquer mudança posterior deve ser feita em uma nova versão. Uma versão `PUBLICADA` pode ser `ARQUIVADA`, sem apagar seu histórico.

A exclusão de rascunho também é bloqueada se a versão já possuir seções ou aplicações vinculadas. Todas as operações de versão validam a propriedade do instrumento pelo profissional autenticado.

### Perfil profissional

O perfil profissional da V1 é acessado em `/profissional/perfil` e usa as rotas protegidas `GET /api/profissional/perfil` e `PUT /api/profissional/perfil`.

Campos complementares do perfil:

- descrição/apresentação profissional;
- informações de atuação;
- fotografia por URL;
- logotipo por URL;
- dados de contato.

Esses campos são adicionados pela migration `003_profissional_perfil.sql`. Nome, e-mail e telefone continuam no registro principal do profissional. O status é exibido no perfil, mas não é alterado pelo formulário do próprio profissional nesta etapa, pois também controla a autorização de acesso.

### Configuração inicial do primeiro profissional

Não haverá endpoint público para criação do primeiro profissional.

No ambiente de desenvolvimento, o primeiro profissional pode ser provisionado diretamente no banco de dados, inclusive via phpMyAdmin/SQL, ou pelo utilitário de CLI disponível no repositório.

Comandos auxiliares existentes:

```text
composer setup-professional
composer check-professional
```

Regras permanentes:

- se já existir um profissional na tabela `profissionais`, não executar novamente o setup de criação;
- o e-mail deve identificar o profissional de forma única;
- a senha nunca pode ser armazenada em texto puro;
- `senha_hash` deve conter um hash gerado pelo PHP e compatível com `password_verify()`;
- o profissional habilitado para login deve estar com `status = ATIVO`;
- `senha_alterada_em` deve registrar a definição da senha;
- `ultimo_login_em` permanece nulo até o primeiro login válido.

No banco local atual, o primeiro profissional já está cadastrado. Portanto, o fluxo de desenvolvimento deve preservar esse registro e apenas completar/corrigir campos de autenticação caso a validação aponte alguma pendência.

## 8. Princípios de desenvolvimento

- Não alterar regras estruturais sem atualizar este documento.
- Separar claramente site público, participante e profissional.
- Preservar rastreabilidade das respostas e cálculos.
- Evitar lógica importante somente no frontend.
- Regras de pontuação/comparação devem existir no backend.
- Não versionar `.env`, senhas, tokens ou credenciais reais.
- Implementar em etapas pequenas e testáveis.
- Depois de cada marco relevante, atualizar `docs/MAPA_RELACIONAL_STATUS.md`.
- Para corrigir bugs, consultar primeiro somente os arquivos envolvidos.
- Não carregar o repositório inteiro quando a tarefa puder ser resolvida com poucos arquivos.

## 9. Documentos de continuidade

Os documentos prioritários são:

```text
docs/MAPA_RELACIONAL_CONTEXT.md
docs/MAPA_RELACIONAL_STATUS.md
docs/DECISOES.md
```

### CONTEXT

Contém decisões relativamente permanentes:

- objetivo;
- arquitetura;
- regras centrais;
- identidade visual;
- stack;
- convenções.

### STATUS

Contém somente o estado atual:

- etapa;
- funcionalidades concluídas;
- alterações recentes;
- testes;
- erros;
- pendências;
- próximo passo.

### DECISOES

Registra decisões técnicas e funcionais que precisam permanecer rastreáveis ao longo do projeto, incluindo mudanças de estrutura, convenções e decisões substituídas.

## 10. Como iniciar uma nova conversa no ChatGPT

Usar um pedido semelhante a:

```text
Acesse o repositório kriale-dot/mapa-percepcao-relacional no GitHub.

Leia primeiro:
- docs/MAPA_RELACIONAL_CONTEXT.md
- docs/MAPA_RELACIONAL_STATUS.md
- docs/DECISOES.md

Depois consulte somente os arquivos necessários para a tarefa atual.

Não altere decisões estruturais registradas no CONTEXT sem me avisar.

Ao terminar uma etapa relevante, atualize o STATUS.
```

O `CONTEXT` explica como o sistema deve funcionar.
O `STATUS` informa exatamente onde o desenvolvimento está naquele momento.
O `DECISOES` registra decisões estruturais e funcionais relevantes.
