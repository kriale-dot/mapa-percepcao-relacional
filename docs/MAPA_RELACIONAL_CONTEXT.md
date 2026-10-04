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

O fluxo principal de entrada em uma avaliação é público e autônomo. Um visitante pode se interessar por uma das avaliações disponibilizadas no site, escolhê-la e iniciar o processo sem que o profissional precise criar previamente a pessoa, o vínculo ou a aplicação.

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

### Pessoas

O cadastro administrativo de pessoas da V1 é acessado em `/profissional/pessoas` e utiliza rotas protegidas sob `/api/profissional/pessoas`.

Cada pessoa pertence ao profissional autenticado e possui nome, e-mail opcional, telefone opcional, data de nascimento opcional, observação administrativa opcional e status.

Os estados adotados nesta etapa são `ATIVO` e `INATIVO`.

A edição do cadastro da pessoa altera apenas os dados administrativos atuais. Snapshots de avaliações são estruturas separadas e não devem ser retroativamente modificados por alterações posteriores no cadastro.

A exclusão física só é permitida enquanto a pessoa não possui vínculos nem aplicações associadas. Quando já houver histórico, a pessoa deve ser preservada e pode ser marcada como `INATIVO`.

### Vínculos

O gerenciamento de vínculos da V1 é acessado em `/profissional/vinculos` e utiliza rotas protegidas sob `/api/profissional/vinculos`.

Cada vínculo pertence ao profissional autenticado e relaciona exatamente duas pessoas distintas, preservadas operacionalmente como lado A e lado B.

Campos usados nesta etapa:

- pessoa do lado A;
- pessoa do lado B;
- tipo do vínculo;
- descrição opcional do tipo;
- tempo de união opcional;
- status.

Os estados adotados são `ATIVO` e `INATIVO`.

O tipo é textual e permanece flexível. Quando `tipo = OUTRO`, a descrição personalizada é obrigatória.

A validação de pessoas distintas ocorre no backend. As duas pessoas também devem pertencer ao mesmo profissional autenticado.

Depois da criação, os lados A/B não são trocados pela edição comum do vínculo, preservando a estabilidade operacional definida no modelo de domínio.

Um mesmo vínculo pode ser utilizado em múltiplas aplicações ao longo do tempo. A exclusão física é permitida somente enquanto não houver aplicações associadas; depois disso, o vínculo deve ser preservado e pode ser marcado como `INATIVO`.

### Aplicações — fluxo público e gestão profissional

O **autoatendimento público** é o fluxo principal de criação de aplicações. O módulo profissional em `/profissional/avaliacoes` deve funcionar principalmente como acompanhamento, consulta e gestão das avaliações que chegam pelo site, mantendo também a possibilidade de criação manual como fluxo secundário.

No fluxo público, o visitante poderá escolher uma avaliação disponibilizada no site e informar diretamente:

- nome do participante A;
- nome do participante B;
- e-mail de contato;
- tipo do vínculo;
- tempo de união, quando aplicável.

A aplicação será associada automaticamente ao profissional responsável pelo instrumento escolhido. Não será necessário o profissional conhecer previamente os participantes nem criar `pessoas` ou `vinculos`.

Para evitar poluir o cadastro administrativo, o autoatendimento público não cria automaticamente registros permanentes em `pessoas` e `vinculos`. A aplicação nasce com `vinculo_id = NULL`; seus dois registros de `aplicacao_participantes` podem permanecer com `pessoa_id = NULL`, mas recebem os nomes informados em `nome_snapshot`, preservando os lados A e B.

O tipo de vínculo e o tempo de união informados pelo visitante são gravados diretamente nos snapshots da aplicação. Mudanças posteriores em cadastros administrativos não alteram esses valores históricos.

Somente avaliações baseadas em versão `PUBLICADA` podem ser iniciadas pelo público. O visitante escolhe a avaliação em linguagem de produto; detalhes internos de instrumento/versão não precisam ser expostos na interface pública.

Para aparecer no catálogo público, o instrumento também precisa estar `ATIVO` e o profissional responsável precisa estar `ATIVO`. Se houver mais de uma versão publicada para o mesmo instrumento, o catálogo oferece somente a versão publicada mais recente.

A criação da aplicação e dos dois participantes deve permanecer atômica. A etapa seguinte deverá gerar os dois acessos individuais seguros e permitir que o fluxo prossiga sem intervenção manual do profissional.

Após a geração dos dois acessos e o envio efetivo do e-mail, a tela pública de confirmação deverá informar claramente que **os links de acesso dos dois participantes foram enviados para o e-mail cadastrado**. Essa mensagem só deve ser exibida depois que o backend confirmar o envio com sucesso; enquanto o envio ainda não existir ou falhar, a interface não deve afirmar que os links foram enviados.

Na V1, o envio de e-mail usa **SMTP Brevo**. Cada aplicação pública gera dois tokens criptograficamente aleatórios, um para A e outro para B. O token bruto existe apenas durante a geração do link; no banco fica somente `SHA-256` em `acessos_aplicacao.token_hash`.

O e-mail cadastrado recebe uma única mensagem contendo os dois links, claramente identificados por participante A e participante B. Depois do envio confirmado, `acessos_aplicacao.enviado_em` e `aplicacoes.enviado_em` são registrados e a aplicação passa de `RASCUNHO` para `PRONTA`.

Se o SMTP falhar durante a criação pública, a transação é revertida e a interface recebe erro de envio, evitando criar uma aplicação sem que os links tenham sido entregues.

O profissional visualiza automaticamente essas aplicações em sua área autenticada, acompanha o preenchimento, consulta os resultados e pode usar os dados de contato para abordagem posterior.

### Identificação inicial do participante

Ao abrir seu link individual válido, o participante confirma os dados de identificação antes de acessar o questionário.

Nesta etapa, o participante informa/confirma:

- nome;
- idade;
- gênero.

O **tempo de união** pertence ao contexto da aplicação e é exibido ao participante a partir do snapshot já registrado na criação pública; ele não precisa ser redigitado individualmente.

Depois da identificação:

- `nome_snapshot`, `idade_snapshot` e `genero_snapshot` são gravados em `aplicacao_participantes`;
- o participante passa de `PENDENTE` para `EM_ANDAMENTO`;
- `iniciou_em` do participante é preenchido apenas na primeira vez;
- a aplicação passa de `PRONTA` para `EM_ANDAMENTO` no primeiro participante que inicia;
- `aplicacoes.iniciada_em` é preservado como o primeiro início da aplicação.

O gênero permanece um campo textual nesta fase porque o material-base exige o dado, mas não define uma lista fechada de opções. Nenhuma enumeração adicional é presumida pela plataforma nesta etapa.

Depois da identificação, o sistema carrega somente seções, itens e alternativas ativos da versão vinculada à aplicação. Itens já excluídos globalmente por “Não se aplica” não entram no questionário retornado.

A Etapa 6.3 apenas confirma a identificação e carrega a estrutura individual do questionário. O salvamento progressivo das duas perspectivas por item será implementado na subetapa seguinte.

### Respostas individuais e persistência progressiva

Na Etapa 6.4, cada participante responde separadamente às duas perspectivas de cada item:

- `SOBRE_MIM`: o alvo da resposta é o próprio participante;
- `SOBRE_OUTRO`: o alvo é o outro participante da mesma aplicação.

O frontend nunca envia `respondente_id` nem `alvo_id`. O backend deriva ambos a partir do token individual, evitando que um participante tente gravar respostas em nome do outro.

Cada perspectiva é salva progressivamente usando a chave única já existente em `respostas`:

```text
aplicacao_id + respondente_id + alvo_id + item_id
```

Uma resposta posterior para a mesma perspectiva/item atualiza o registro existente em vez de criar duplicata.

Para itens com alternativas ativas, somente uma alternativa ativa pertencente ao próprio item é aceita. Para itens sem alternativas, a API aceita exatamente um valor textual ou numérico.

Ao recarregar o link, o questionário retorna apenas as respostas do participante autenticado pelo token. As respostas do outro participante nunca são incluídas na resposta da API.

O questionário informa progresso em quantidade de perspectivas respondidas e percentual. Como cada item possui duas perspectivas, o total esperado é `itens válidos × 2`.

A regra global de **“Não se aplica”** e a conclusão individual permanecem para a próxima subetapa.

A criação manual pela área profissional continua disponível apenas como modo assistido/administrativo, não como requisito para que uma avaliação pública exista.

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

### Seções da versão

As seções organizam a versão do instrumento em tópicos. Cada seção possui título, descrição opcional, ordem e indicador ativo.

Somente versões em `RASCUNHO` podem receber criação, edição ou exclusão de seções. Versões `PUBLICADA` ou `ARQUIVADA` exibem a estrutura em modo somente leitura.

A ordem pode ser informada pelo profissional; na criação, se omitida, a API coloca a seção automaticamente ao final. A exclusão física de uma seção é permitida somente enquanto ela não possui itens vinculados. Depois que possuir itens, a seção deve ser preservada, podendo ser marcada como inativa.

### Itens/perguntas

Cada item pertence a uma seção e possui código, texto/pergunta, tipo de resposta, ordem, indicador `permite_nao_se_aplica` e indicador ativo.

Somente versões em `RASCUNHO` permitem criar, editar ou excluir itens. Em versões `PUBLICADA` ou `ARQUIVADA`, os itens permanecem somente para leitura.

O código do item deve ser único dentro da própria seção. A ordem pode ser omitida na criação; nesse caso, a API coloca o item automaticamente ao final.

O campo `tipo_resposta` permanece como identificador textual obrigatório, sem lista fechada nesta etapa, para atender à exigência de expansão futura dos tipos de resposta sem alterar a estrutura do banco ou quebrar aplicações históricas.

A opção `permite_nao_se_aplica` é configurável por item. O comportamento global de exclusão do item marcado como “Não se aplica” da aplicação e dos denominadores permanece uma regra funcional da plataforma e será aplicado no fluxo de respostas/cálculo.

Um item só pode ser excluído fisicamente enquanto não possui alternativas nem respostas vinculadas.

### Alternativas dos itens

Itens de resposta fechada podem possuir alternativas. Cada alternativa pertence a um item e possui `valor`, `rotulo`, `ordem` e indicador ativo.

O `valor` é o identificador persistido da opção e deve ser único dentro do próprio item. O `rotulo` é o texto apresentado ao participante.

Somente versões em `RASCUNHO` permitem criar, editar ou excluir alternativas. Em versões `PUBLICADA` ou `ARQUIVADA`, as alternativas permanecem somente para leitura.

A ordem pode ser omitida na criação; nesse caso, a alternativa é posicionada automaticamente ao final.

Uma alternativa com respostas vinculadas não pode ser excluída fisicamente, mesmo que a chave estrangeira permita `ON DELETE SET NULL`; a aplicação adota essa proteção para preservar rastreabilidade histórica.

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
