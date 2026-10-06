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

### Exclusão de avaliações/aplicações

O profissional proprietário pode excluir fisicamente uma avaliação, inclusive quando já estiver concluída. A operação é permanente e deve exigir confirmação explícita na interface com aviso dos efeitos.

A exclusão remove os dados pertencentes àquela aplicação: participantes/snapshots da aplicação, acessos e tokens, respostas, marcações “Não se aplica”, comparações, resultados direcionais, resultado geral e eventual devolutiva profissional. A exclusão **não** remove o instrumento, a versão, cadastros permanentes de pessoas nem o vínculo administrativo.

A remoção é executada em transação e registrada na auditoria. Depois da exclusão, links de participantes e de devolutiva associados à aplicação deixam de funcionar.

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

A exclusão física do instrumento é uma operação destrutiva explícita. Ela é permitida somente quando **nenhuma avaliação/aplicação está vinculada a qualquer versão** do instrumento. Quando permitida, a exclusão remove também todas as versões, seções, itens, alternativas e faixas de resultado do instrumento. Se existir ao menos uma aplicação vinculada, a API bloqueia a exclusão; as avaliações que realmente não precisarem ser preservadas devem ser excluídas individualmente antes. A interface deve sempre exibir confirmação com os efeitos da operação.

### Versões do instrumento

Cada versão pertence a um instrumento e possui `numero_versao`, `status` e `publicado_em`.

Estados adotados:

- `RASCUNHO`;
- `PUBLICADA`;
- `ARQUIVADA`.

Na V1 permanece a regra conservadora de imutabilidade para **edição**: somente versões em `RASCUNHO` podem ser alteradas. Ao publicar, a versão passa a ser imutável quanto ao conteúdo; qualquer mudança posterior deve ser feita em uma nova versão. Uma versão `PUBLICADA` pode ser `ARQUIVADA`.

A exclusão física é tratada separadamente da edição. Uma versão em qualquer estado (`RASCUNHO`, `PUBLICADA` ou `ARQUIVADA`) pode ser excluída somente quando **não possui nenhuma aplicação vinculada**. Quando permitida, a exclusão remove a versão e toda a sua estrutura: seções, itens, alternativas e faixas de resultado. Se houver aplicação vinculada, a API bloqueia a operação; as avaliações que realmente não precisarem ser preservadas devem ser excluídas individualmente antes. A interface deve sempre confirmar a exclusão e informar os efeitos. Todas as operações de versão validam a propriedade do instrumento pelo profissional autenticado.

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

### Não se aplica e conclusão individual

Na Etapa 6.5, itens configurados com `permite_nao_se_aplica = 1` podem ser excluídos da aplicação pelo participante.

Ao confirmar **“Não se aplica”**:

- é criado um registro em `aplicacao_itens_excluidos`;
- a exclusão vale para a aplicação inteira;
- o item deixa de ser retornado para A e B;
- respostas já existentes do item são preservadas para rastreabilidade;
- o item não entra no progresso exigido para conclusão;
- o item não deverá entrar nos cálculos comparativos/resultados.

A exclusão é tratada como decisão global e irreversível nesta V1. A interface exige confirmação antes de registrar a exclusão. Em caso de tentativa duplicada, o primeiro registro de quem marcou permanece preservado.

A conclusão individual só é permitida quando todas as duas perspectivas dos itens ainda válidos tiverem sido respondidas. Ao concluir:

- participante passa para `CONCLUIDO`;
- `aplicacao_participantes.concluiu_em` é registrado;
- o acesso passa para `CONCLUIDO`;
- `acessos_aplicacao.concluido_em` é registrado;
- o link deixa de permitir novas alterações;
- se apenas um concluiu, a aplicação permanece `EM_ANDAMENTO`;
- quando A e B concluem, a aplicação passa para `CONCLUIDA` e recebe `concluida_em`.

Depois da conclusão dos dois participantes, a aplicação fica pronta para a etapa de comparação e resultados.

### Comparação e resultados técnicos

A Etapa 7 introduz o cálculo técnico das percepções depois que os dois participantes concluem.

Os dois sentidos oficiais são:

```text
A_SOBRE_B = A → B × B → B
B_SOBRE_A = B → A × A → A
```

Para cada item válido, o backend busca exatamente as duas respostas necessárias ao sentido. Itens presentes em `aplicacao_itens_excluidos` ficam totalmente fora das comparações e do denominador.

Uma comparação é considerada coincidente quando:

- respostas por alternativa apontam para a mesma alternativa;
- respostas numéricas possuem o mesmo valor numérico;
- respostas textuais, quando usadas, são iguais após remoção de espaços nas extremidades.

Se uma das duas respostas necessárias estiver ausente ou os formatos forem incompatíveis, a comparação é persistida como não comparável e não entra no denominador.

O percentual por sentido é:

```text
coincidências / comparações válidas × 100
```

Os resultados direcionais continuam persistidos separadamente para `A_SOBRE_B` e `B_SOBRE_A`.

Além deles, a regra de consolidação geral foi definida posteriormente: para cada item válido, o score geral soma 1 ponto somente quando **as duas comparações direcionais do item coincidem**. O resultado geral é persistido em `resultados_gerais` e usa:

```text
percentual geral = acertos gerais / itens válidos × 100
```

A versão do algoritmo passa a ser `2.0`.

As faixas de interpretação passam a ser registros versionados por `instrumento_versao_id` em `resultado_faixas`. As faixas-base continuam significando:

- Ruim: de 0 até antes de 34%;
- Regular: de 34 até antes de 67%;
- Bom: de 67 até 100%.

Como o percentual pode possuir casas decimais, os limites técnicos armazenados são 0–33,99; 34–66,99; 67–100, preservando o significado dos intervalos inteiros do material-base sem deixar lacunas decimais.

Ao concluir o segundo participante, o backend calcula e persiste automaticamente `comparacoes` e `resultados` dentro da mesma transação de conclusão. A área profissional também possui uma ação de recálculo para aplicações concluídas anteriormente.

A área profissional pode consultar:

- percentual e faixa por sentido;
- coincidências e comparações válidas;
- consolidação derivada por seção;
- comparação item a item;
- respostas percebida e autorreferida envolvidas;
- coincidências, divergências e casos não comparáveis;
- itens removidos por “Não se aplica”.

O cálculo é técnico e não produz diagnóstico clínico automático.

### Configuração profissional das faixas de resultado

Na Etapa 7.2, as faixas de interpretação deixam de ser apenas registros técnicos e passam a ser configuráveis pelo profissional no contexto de cada versão do instrumento.

Rota profissional da interface:

```text
/profissional/instrumentos/{instrumentId}/versoes/{versionId}/faixas-resultados
```

Regras:

- toda nova versão já nasce com as faixas-base Ruim / Regular / Bom;
- faixas pertencem à `instrumento_versao`;
- somente versões em `RASCUNHO` permitem edição;
- versões `PUBLICADA` ou `ARQUIVADA` exibem as faixas apenas para leitura;
- publicar a versão congela também sua interpretação percentual;
- mudanças futuras de faixas exigem uma nova versão do instrumento;
- a primeira faixa precisa começar em 0,00%;
- a última precisa terminar em 100,00%;
- as faixas intermediárias precisam formar cobertura contínua, sem lacunas nem sobreposições;
- como o cálculo usa duas casas decimais, a faixa seguinte deve começar 0,01 ponto após o limite máximo anterior;
- o profissional pode alterar rótulos, limites e quantidade de faixas enquanto a versão estiver em rascunho;
- resultados já calculados continuam preservando o rótulo da faixa gravado em `resultados.faixa`.

A página de resultados profissionais identifica a versão usada pela aplicação e permite consultar as faixas daquela versão em modo de leitura.

### Devolutiva profissional e liberação aos participantes

Na Etapa 8, cálculo técnico e interpretação profissional permanecem separados.

Depois que a aplicação estiver `CONCLUIDA`, o profissional pode preparar uma devolutiva com:

- síntese;
- observações;
- comentário profissional.

A devolutiva começa em `RASCUNHO` e pode ser alterada livremente enquanto não tiver sido liberada.

A liberação é uma ação explícita do profissional. Ao liberar:

- o sistema garante que os resultados técnicos existam;
- gera um token aleatório independente dos acessos de preenchimento;
- persiste somente `SHA-256(token)`;
- envia pelo **SMTP Brevo** um link seguro ao e-mail de contato cadastrado na aplicação;
- somente após o envio confirmado a devolutiva passa para `LIBERADA`;
- `liberada_em` e `enviado_em` são registrados;
- o conteúdo da devolutiva fica congelado para preservar o histórico do que foi disponibilizado.

Rota pública da V1:

```text
/resultado/{token}
```

A página pública da devolutiva mostra:

- nomes dos dois participantes;
- tipo de vínculo e tempo de união;
- score individual de cada participante;
- score geral do casal/par;
- percentuais e faixas;
- resultados por seção;
- síntese;
- observações;
- comentário profissional;
- itens excluídos por “Não se aplica”.

Por privacidade, a página pública **não mostra as respostas individuais brutas nem a comparação item a item**. Essas informações continuam disponíveis somente na área profissional.

A devolutiva técnica não constitui diagnóstico clínico automático.

### Dashboard e acompanhamento profissional

Na Etapa 9, a rota `/profissional` passa a ser um dashboard operacional real.

O dashboard apresenta contagens derivadas do estado atual do banco:

- total de avaliações;
- prontas;
- em andamento;
- concluídas;
- resultados disponíveis;
- devolutivas em rascunho;
- devolutivas liberadas;
- avaliações recentes.

Esses indicadores não são armazenados em tabela própria; são calculados a partir de `aplicacoes`, `resultados` e `devolutivas`.

A lista profissional de avaliações passa a suportar filtros por:

- participante ou e-mail;
- status;
- instrumento;
- vínculo administrativo, quando existir;
- tipo de vínculo;
- período de criação.

Os filtros são executados no backend e sempre restritos ao profissional autenticado.

A área de avaliações também permite gerar uma **lista única de e-mails de contato** cadastrados nas aplicações do profissional. A lista:

- considera todas as aplicações do profissional, independentemente dos filtros visuais da tela;
- normaliza os endereços para minúsculas e remove duplicidades;
- informa quantas avaliações utilizaram cada e-mail;
- informa a primeira e a última data de avaliação associadas ao endereço;
- pode ser copiada como texto simples;
- pode ser exportada em CSV;
- nunca inclui aplicações pertencentes a outro profissional;
- registra o evento `LISTA_EMAILS_GERADA` na auditoria com apenas os totais, sem gravar os endereços no contexto do evento.

A página de detalhe de uma aplicação mostra:

- identificação da aplicação;
- instrumento e versão;
- e-mail;
- tipo de vínculo e tempo de união;
- status e datas principais;
- dados e status de A e B;
- resumo dos resultados quando disponíveis;
- itens marcados como “Não se aplica”;
- status da devolutiva;
- atalhos para o painel completo de resultados.

O autoatendimento público continua independente dos cadastros permanentes de `pessoas` e `vinculos`, por isso o detalhe profissional deve funcionar também quando `vinculo_id` e `pessoa_id` forem nulos.

### Segurança, auditoria e preparação da V1

A Etapa 10 introduz endurecimento operacional antes do deploy.

#### Mensagens complementares

A V1 permanece baseada em e-mail SMTP Brevo.

O profissional pode reenviar:

- o acesso individual de A ou B, desde que o participante ainda não tenha concluído;
- a devolutiva, desde que já esteja `LIBERADA`.

Como tokens brutos nunca são persistidos, um reenvio sempre gera **novo token** e substitui o hash vigente. Consequentemente, o link anterior deixa de funcionar.

Participação concluída não pode ser reaberta por reenvio.

#### Auditoria

A tabela `auditoria_eventos` registra ações relevantes com:

- `profissional_id`;
- tipo e ID do ator;
- ação;
- entidade e ID;
- contexto JSON não sensível;
- IP;
- user agent;
- data/hora.

O serviço de auditoria remove chaves cujo nome contenha referências a senha, password, token, JWT, authorization ou credenciais SMTP.

Eventos iniciais incluem:

- login profissional bem-sucedido;
- alteração de senha;
- atualização de perfil;
- criação/publicação/arquivamento/exclusão de versão;
- exclusão de instrumento;
- criação pública de aplicação;
- exclusão de avaliação/aplicação;
- marcação “Não se aplica”;
- conclusão de participante;
- salvamento/liberação de devolutiva;
- reenvio de acesso;
- reenvio de devolutiva.

A consulta fica disponível em `/profissional/auditoria` e é sempre limitada ao profissional autenticado.

#### Rate limit

A V1 usa rate limit persistido em banco para dois pontos expostos:

- tentativas inválidas de login;
- criação pública de avaliações.

O login é limitado tanto por IP quanto por conta/e-mail. O autoatendimento público é limitado tanto por IP quanto pelo e-mail informado.

Os limites são configuráveis por `.env`.

#### Cabeçalhos de segurança

A API envia:

- `X-Content-Type-Options: nosniff`;
- `X-Frame-Options: DENY`;
- `Referrer-Policy: no-referrer`;
- `Cache-Control: no-store`.

O CORS continua restrito ao `FRONTEND_URL`.

#### Checklist V1 e backup

Foi criado `composer check-v1`, que verifica configuração de produção, URLs, HTTPS, JWT, extensões PHP, SMTP, diretórios, banco, migrations e tabelas.

Foi criado `composer backup-db`, baseado em `mysqldump`, que grava backup SQL fora do Git e informa SHA-256 do arquivo.

O guia operacional está em:

```text
docs/PRODUCAO_V1.md
```

Antes de produção, um backup precisa ser criado e uma restauração em banco separado precisa ser validada.

### Estado oficial da V1.0.0

Em 2026-10-05, a V1 foi formalmente encerrada em escopo e desenvolvimento funcional.

A versão oficial é:

```text
1.0.0
```

O documento de fechamento é:

```text
docs/FECHAMENTO_V1.md
```

A V1 passa a operar em regime de congelamento funcional:

- bugs podem ser corrigidos;
- falhas de segurança podem ser corrigidas;
- ajustes indispensáveis ao deploy podem ser feitos;
- correções de compatibilidade, integridade e migration podem ser feitas;
- novas funcionalidades e novas regras de negócio não entram mais na V1.

A homologação final ainda deve cobrir o fluxo completo em ambiente de produção/homologação, especialmente as áreas que foram implementadas sem rodada formal completa de testes antes do avanço.

Após o fechamento, qualquer expansão funcional deve ser tratada como planejamento de V2.

### Site institucional configurável — correção de escopo

O site institucional é requisito obrigatório da V1, não item de V2.

A raiz pública `/` deve representar o site institucional do profissional, e não uma home técnica ou estática.

O profissional administra o conteúdo em `/profissional/site` por blocos independentes.

Os blocos podem representar título, texto, imagem, vídeo, áudio, perfil profissional, apresentação, chamada para ação, link/botão ou seção de avaliações.

O site público usa os dados mantidos em `profissionais` para identidade profissional:

- nome;
- descrição;
- atuação;
- fotografia;
- logotipo;
- telefone/e-mail;
- dados de contato.

Somente blocos `ATIVO` e visíveis são exibidos publicamente, sempre respeitando a ordem configurada.

A identidade visual oficial permanece a paleta do projeto e o layout deve ser responsivo para desktop, tablet e smartphone.

A identificação desta lacuna invalidou o fechamento formal anterior da V1. O projeto voltou temporariamente a `1.0.0-rc.1` até a validação da Etapa 11.

#### Upload de imagens institucionais

Fotografia, logotipo e blocos do tipo `IMAGEM` não exigem mais que o profissional informe uma URL manualmente.

O frontend envia o arquivo por `multipart/form-data` para:

```text
POST /api/profissional/site/upload-imagem
```

O backend:

- valida autenticação profissional;
- aceita JPG, PNG e WEBP;
- valida MIME real com `fileinfo`;
- aplica limite configurável por `SITE_IMAGE_MAX_MB` (5 MB por padrão);
- gera nome aleatório;
- grava em `public/uploads/site/{profissional_id}/`;
- devolve a URL pública;
- registra o envio na auditoria.

A URL gerada continua sendo armazenada em `foto_url`, `logo_url` ou `site_blocos.midia_url`, preservando a estrutura atual do banco sem migration adicional.

Vídeo e áudio continuam por URL na V1.

### Resultado automático por e-mail da Avaliação Conjugal

Quando os dois participantes concluírem a **Avaliação Conjugal**, o cálculo técnico ocorre automaticamente e, no mesmo fluxo de conclusão, a plataforma envia ao `email_contato` da aplicação um resumo automático contendo:

- score de A (`A_SOBRE_B`);
- score de B (`B_SOBRE_A`);
- score geral do par;
- título e texto interpretativo conforme a porcentagem do score geral;
- aviso de que o resultado automático não substitui a devolutiva profissional.

As faixas narrativas aprovadas são independentes das faixas técnicas Ruim / Regular / Bom:

- 80,00–100,00% — Uma percepção compartilhada muito positiva;
- 60,00–79,99% — Uma boa compreensão, com espaço para aprofundar o diálogo;
- 40,00–59,99% — Uma oportunidade de se conhecerem melhor;
- 20,00–39,99% — Um convite à redescoberta;
- 0,00–19,99% — Um caminho para construir maior compreensão.

Os textos fornecidos para essas faixas são específicos da Avaliação Conjugal e não devem ser aplicados automaticamente a outros instrumentos.

A devolutiva profissional continua separada: o profissional pode preparar síntese, observações e comentário e, quando liberar, o sistema envia o link seguro da devolutiva.

Se o envio SMTP do resultado automático falhar na conclusão do segundo participante, a transação de conclusão é revertida para permitir nova tentativa e impedir que a Avaliação Conjugal fique marcada como concluída sem o envio obrigatório.


O PDF automático inclui score geral, scores individuais, resultados por tópico, comparação item a item, itens excluídos por Não se aplica, nome do profissional e link para a plataforma. A geração usa `dompdf/dompdf` e não exige migration.

## Biblioteca pública de documentos

A plataforma possui um módulo público de biblioteca para materiais em PDF e PNG.

Fluxos:

```text
Visitante
→ /biblioteca
→ pesquisa por título/descrição/nome do arquivo
→ abre PDF ou PNG livremente

Profissional autenticado
→ /profissional/biblioteca
→ envia PDF/PNG
→ informa título e descrição
→ define ATIVO ou INATIVO
→ edita metadados ou exclui o documento
```

API:

- `GET /api/public/biblioteca?q=`
- `GET /api/profissional/biblioteca/documentos?q=`
- `POST /api/profissional/biblioteca/documentos`
- `PUT /api/profissional/biblioteca/documentos/{id}`
- `DELETE /api/profissional/biblioteca/documentos/{id}`

Arquivos enviados ficam em:

```text
mapa-relacional-api/public/uploads/biblioteca/{profissional_id}/
```

Formatos permitidos: PDF e PNG. O limite padrão é 20 MB via `LIBRARY_FILE_MAX_MB`. A tabela é `biblioteca_documentos`, criada pela migration `011_biblioteca_publica.sql`.

O site institucional aceita o bloco `BIBLIOTECA`, cujo destino padrão é `/biblioteca`.

