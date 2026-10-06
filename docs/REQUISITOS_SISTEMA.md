# Avaliação de Percepção Relacional — Documento de Requisitos do Sistema

**Versão do documento:** 1.0  
**Data:** 2026-10-03  
**Status:** baseline inicial para desenvolvimento  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`

---

## 1. Objetivo deste documento

Este documento consolida os requisitos funcionais, regras de negócio, requisitos não funcionais, limites da V1 e decisões ainda abertas da plataforma **Avaliação de Percepção Relacional**.

Ele deve ser usado como referência para:

- planejamento das etapas de desenvolvimento;
- criação de banco de dados e migrations;
- implementação da API;
- implementação do frontend;
- testes de aceitação;
- validação com a profissional responsável;
- manutenção futura do sistema.

Este documento não substitui:

- `MAPA_RELACIONAL_CONTEXT.md`, que registra o contexto permanente;
- `MAPA_RELACIONAL_STATUS.md`, que registra o estado atual;
- `DECISOES.md`, que registra decisões técnicas e funcionais;
- `MODELO_DOMINIO_V1.md`, que detalha o modelo conceitual do domínio.

---

## 2. Visão geral do produto

### 2.1 Nome

**Avaliação de Percepção Relacional**

### 2.2 Subtítulo

**Instrumento de percepção mútua e conhecimento interpessoal.**

### 2.3 Finalidade

A plataforma deverá permitir que duas pessoas respondam, separadamente, a um instrumento de percepção relacional e que o sistema compare:

```text
A → A = o que A responde sobre si
A → B = o que A imagina/percebe sobre B

B → B = o que B responde sobre si
B → A = o que B imagina/percebe sobre A
```

As comparações centrais serão:

```text
A → B × B → B
B → A × A → A
```

O objetivo é identificar convergências e divergências entre a percepção de uma pessoa sobre a outra e a forma como a outra pessoa responde sobre si mesma.

A plataforma não deve emitir diagnóstico clínico automático.

### 2.4 Tipos de vínculo

O sistema não será exclusivo para casais. Deve permitir, entre outros:

- casal/cônjuges;
- pais e filhos;
- familiares;
- amigos;
- empregador e empregado;
- líder e liderado;
- outros vínculos definidos pelo profissional.

---

## 3. Escopo geral do sistema

A solução será dividida em três áreas principais:

```text
1. Site institucional público
2. Área do participante
3. Área profissional
```

A API deverá permanecer separada do frontend.

### 3.1 Site institucional público

Área pública destinada a apresentar:

- a profissional;
- sua identificação;
- sua apresentação;
- sua atuação;
- informações sobre os instrumentos/avaliações disponíveis;
- conteúdos institucionais;
- meios de contato;
- chamadas para ação;
- catálogo público de avaliações disponíveis;
- início autônomo de uma avaliação pelo próprio visitante, sem intervenção prévia do profissional.

O fluxo público de autoatendimento é parte central do produto: o visitante poderá escolher uma avaliação disponibilizada publicamente, informar os participantes, o tipo de vínculo e o tempo de união e iniciar o processo. A aplicação criada deverá aparecer automaticamente na área profissional para acompanhamento e posterior contato.

### 3.2 Área do participante

Área destinada ao acesso individual de cada participante à avaliação atribuída.

O participante não deverá ter acesso às respostas da outra pessoa durante o preenchimento.

### 3.3 Área profissional

Área autenticada destinada à gestão:

- do perfil profissional;
- do site institucional;
- dos instrumentos;
- das pessoas;
- dos vínculos;
- das aplicações;
- dos convites/acessos;
- do acompanhamento do preenchimento;
- dos resultados;
- dos comentários profissionais;
- das devolutivas futuras.

---

## 4. Perfis e atores

### 4.1 Profissional

Usuário autenticado responsável por administrar sua operação na plataforma.

### 4.2 Participante A

Primeira pessoa vinculada à aplicação.

### 4.3 Participante B

Segunda pessoa vinculada à aplicação.

### 4.4 Visitante

Pessoa não autenticada que acessa o site institucional.

### 4.5 Sistema

Responsável por:

- validações;
- geração de acessos;
- persistência das respostas;
- aplicação das regras de “Não se aplica”;
- comparação;
- cálculo de resultados;
- notificações;
- auditoria futura.

---

# 5. Requisitos funcionais

## 5.1 Autenticação e conta profissional

### RF-001 — Login profissional

O sistema deverá permitir autenticação segura do profissional.

**Critérios de aceitação:**

- o profissional informa credenciais válidas;
- o backend valida as credenciais;
- o sistema cria uma sessão/token autenticado;
- credenciais inválidas não concedem acesso;
- rotas profissionais protegidas não podem ser acessadas sem autenticação.

### RF-002 — Logout

O profissional deverá poder encerrar sua sessão.

### RF-003 — Perfil profissional

O profissional deverá poder manter, no mínimo:

- nome;
- e-mail;
- telefone;
- descrição/apresentação profissional;
- informações de atuação;
- imagem/fotografia, quando habilitada;
- logotipo;
- dados de contato;
- status.

### RF-004 — Alteração segura de senha

O sistema deverá permitir alteração de senha autenticada.

Recuperação de senha por e-mail deverá ser implementada quando o módulo de e-mail estiver disponível.

---

## 5.2 Site institucional

### RF-010 — Página institucional pública

A página inicial pública deverá ser um site institucional da profissional.

### RF-011 — Editor de conteúdo por blocos

O profissional deverá poder montar o conteúdo institucional por blocos independentes.

Tipos iniciais previstos:

- título;
- texto;
- imagem;
- áudio;
- identificação profissional;
- apresentação;
- chamada para ação;
- botão/link;
- seção de informações sobre a avaliação.

### RF-012 — Propriedades dos blocos

Cada bloco deverá poder possuir, conforme seu tipo:

- título;
- descrição;
- conteúdo;
- mídia;
- texto alternativo quando aplicável;
- ordem;
- visibilidade;
- status ativo/inativo.

### RF-013 — Ordenação de blocos

O profissional deverá poder alterar a ordem de exibição dos blocos.

### RF-014 — Identidade profissional

O site institucional deverá poder exibir:

- nome da profissional;
- descrição;
- logotipo;
- fotografia, se configurada;
- contatos;
- informações profissionais.

### RF-015 — Chamada para avaliação

O site poderá conter botão ou seção que direcione o visitante ao fluxo de cadastro/início de avaliação quando essa modalidade estiver habilitada.

### RF-016 — Acesso por link externo

O sistema deverá também permitir que uma avaliação seja iniciada por link individual enviado ao participante, sem necessidade de navegar pelo site institucional.

---

## 5.3 Pessoas e vínculos

### RF-020 — Cadastro de pessoa

O profissional deverá poder cadastrar uma pessoa com:

- nome;
- e-mail opcional;
- telefone opcional;
- data de nascimento opcional;
- observação administrativa opcional;
- status.

### RF-021 — Edição de pessoa

O profissional deverá poder atualizar dados administrativos da pessoa sem alterar snapshots de avaliações anteriores.

### RF-022 — Cadastro de vínculo

O profissional deverá poder criar um vínculo entre exatamente duas pessoas.

### RF-023 — Tipo de vínculo

O vínculo deverá conter um tipo e permitir valor personalizado/“Outro”.

### RF-024 — Pessoas distintas

Uma pessoa não poderá ocupar simultaneamente os lados A e B do mesmo vínculo.

Essa validação deverá ser garantida pelo backend.

### RF-025 — Histórico do vínculo

Uma mesma dupla poderá realizar múltiplas aplicações ao longo do tempo.

---

## 5.4 Instrumentos e versões

### RF-030 — Cadastro de instrumento

O profissional deverá poder criar mais de um instrumento neste modelo de avaliação.

Campos mínimos:

- nome;
- descrição;
- status.

### RF-031 — Versionamento

Cada instrumento deverá possuir versões.

Uma aplicação deverá apontar para uma versão específica para preservar histórico.

### RF-032 — Estados de versão

Uma versão deverá suportar, no mínimo:

- RASCUNHO;
- PUBLICADA;
- ARQUIVADA, quando implementado.

### RF-033 — Imutabilidade após publicação

Depois de publicada e utilizada em aplicações, uma versão não deverá ter sua estrutura histórica alterada silenciosamente.

Alterações estruturais deverão gerar nova versão.

---

## 5.5 Seções, itens e alternativas

### RF-040 — Seções

O profissional deverá poder organizar o instrumento em seções/tópicos.

Cada seção deverá possuir:

- título;
- descrição opcional;
- ordem;
- status ativo.

### RF-041 — Itens

Cada seção deverá conter itens/perguntas.

Campos mínimos:

- código;
- texto;
- tipo de resposta;
- ordem;
- indicação se permite “Não se aplica”;
- status ativo.

### RF-042 — Alternativas

Itens de resposta fechada deverão permitir cadastro de alternativas com:

- valor;
- rótulo;
- ordem;
- status.

### RF-043 — Tipos de resposta

A arquitetura deverá permitir expansão futura dos tipos de resposta sem quebrar aplicações históricas.

### RF-044 — “Não se aplica”

Cada item deverá poder ser configurado para aceitar ou não a opção **“Não se aplica”**.

No instrumento atual, essa opção deve estar disponível quando aplicável à pergunta.

---

## 5.6 Aplicações

### RF-050 — Nova aplicação

A plataforma deverá permitir dois modos de criação de aplicação:

**1. Autoatendimento público — fluxo principal**

Um visitante do site poderá, sem autenticação profissional e sem intervenção prévia do profissional:

- escolher uma avaliação disponibilizada publicamente;
- informar o nome do participante A;
- informar o nome do participante B;
- informar um e-mail de contato;
- informar o tipo de vínculo;
- informar o tempo de união, quando aplicável;
- iniciar a avaliação.

A aplicação pública deverá ser associada automaticamente ao profissional responsável pela avaliação escolhida e deverá aparecer na área profissional para acompanhamento.

Uma avaliação ficará disponível no catálogo público quando o instrumento estiver `ATIVO`, houver versão `PUBLICADA` e o profissional responsável estiver `ATIVO`. Quando houver mais de uma versão publicada do mesmo instrumento, o catálogo público utilizará a versão publicada mais recente.

O autoatendimento público não deverá exigir cadastro prévio em `pessoas` nem criação prévia em `vinculos`.

**2. Criação assistida pelo profissional — fluxo secundário**

O profissional autenticado poderá continuar criando uma aplicação manualmente quando necessário, vinculando-a a:

- profissional;
- instrumento/versão;
- vínculo existente, quando conhecido;
- e-mail de contato;
- tipo de vínculo;
- tempo de união, quando aplicável.

Nos dois modos, a aplicação deve preservar a versão específica do instrumento e os snapshots necessários para manter o histórico independente de alterações posteriores.

### RF-051 — Dois participantes

Cada aplicação deverá possuir exatamente dois lados operacionais:

- A;
- B.

### RF-052 — Snapshot da identificação

A aplicação deverá preservar os dados informados pelos participantes naquele momento, incluindo:

- nome;
- idade;
- gênero;
- lado A/B;
- demais dados aprovados para o instrumento.

Mudanças posteriores no cadastro permanente não devem modificar o histórico da aplicação.

### RF-053 — Estados da aplicação

Estados iniciais previstos:

- RASCUNHO;
- PRONTA;
- EM_ANDAMENTO;
- CONCLUIDA;
- CANCELADA.

A nomenclatura final poderá ser refinada antes da produção.

### RF-054 — Acompanhamento

O profissional deverá visualizar o estado de preenchimento de cada participante.

---

## 5.7 Convites e acessos individuais

### RF-060 — Dois acessos por aplicação

Cada aplicação deverá gerar um acesso individual para A e outro para B.

### RF-061 — Token seguro

O código/token de acesso não deverá ser armazenado em texto puro.

No banco deverá ser armazenado apenas o hash necessário para validação.

### RF-062 — Link individual

Cada acesso deverá poder originar um link individual para o participante.

### RF-063 — Canais de envio

A arquitetura deverá permitir distribuição do link por diferentes meios, como:

- copiar link;
- e-mail;
- SMS;
- WhatsApp;
- outros meios externos.

Na V1, o canal obrigatório de envio dos acessos será **e-mail via SMTP Brevo**.

No autoatendimento público, os dois links individuais de A e B serão enviados ao e-mail de contato cadastrado na aplicação. A tela só poderá afirmar que os links foram enviados depois de confirmação real de sucesso do SMTP.

### RF-064 — Retomada

Enquanto o preenchimento estiver ativo e não concluído/revogado, o participante poderá retomar a avaliação pelo seu acesso válido.

### RF-065 — Revogação

O profissional deverá poder revogar um acesso quando essa funcionalidade for implementada.

### RF-066 — Isolamento

O participante deverá acessar somente a aplicação correspondente ao seu token.

---

## 5.8 Preenchimento da avaliação

### RF-070 — Identificação inicial

O participante deverá informar os dados de identificação exigidos pelo instrumento/aplicação.

### RF-071 — Duas perguntas por item

Para cada item, cada participante deverá responder duas perguntas:

- **O que eu penso disso?**
- **O que eu acredito que o outro pensa disso?**

As duas respostas pertencem ao participante que está preenchendo. O outro participante responde o mesmo item de forma independente.

### RF-072 — Persistência progressiva

O sistema deverá preservar o progresso para permitir retomada segura do preenchimento.

### RF-073 — Independência das respostas

A e B respondem separadamente.

Nenhum dos participantes deverá visualizar as respostas do outro durante o preenchimento.

### RF-074 — Conclusão individual

Cada participante deverá concluir seu formulário individualmente.

### RF-075 — Conclusão da aplicação

A aplicação poderá ser considerada pronta para comparação final quando os dois participantes tiverem concluído, respeitadas as regras de itens excluídos.

---

## 5.9 Regra “Não se aplica”

### RF-080 — Exclusão do item na aplicação

Quando um participante marcar **“Não se aplica”** em um item configurado para permitir essa opção, o item deverá ser excluído do conjunto comparável daquela aplicação.

### RF-081 — Exclusão do denominador

O item excluído não poderá entrar no denominador da pontuação.

### RF-082 — Comportamento para o outro participante

Se o outro participante ainda não respondeu o item, o sistema não deverá apresentá-lo.

### RF-083 — Resposta já existente

Se o outro participante já tiver respondido antes da exclusão:

- a resposta poderá ser preservada para rastreabilidade/auditoria;
- ela deverá ser ignorada no cálculo.

### RF-084 — Rastreabilidade

O sistema deverá registrar:

- aplicação;
- item;
- participante que marcou;
- data;
- motivo opcional.

---

## 5.10 Comparação

### RF-090 — Comparação A sobre B

O sistema deverá comparar:

```text
A → B × B → B
```

### RF-091 — Comparação B sobre A

O sistema deverá comparar:

```text
B → A × A → A
```

### RF-092 — Coincidência

Uma comparação deverá registrar se as duas respostas correspondentes coincidem.

### RF-093 — Comparabilidade

Itens excluídos ou sem respostas suficientes não deverão ser contabilizados como comparação válida.

### RF-094 — Cálculo no backend

A regra de comparação deverá existir no backend e não apenas na interface.

---

## 5.11 Resultados

### RF-100 — Percentual

A regra atual de cálculo será:

```text
percentual = coincidências / comparações válidas × 100
```

### RF-101 — Resultado por sentido

O sistema deverá permitir resultado separado para:

- A sobre B;
- B sobre A.

### RF-102 — Score geral

Além dos scores individuais de A e B, o sistema deverá calcular um **score geral do par**.

Para cada item válido existem duas comparações:

```text
A acredita que B pensa × resposta real de B
B acredita que A pensa × resposta real de A
```

Cada comparação coincidente soma um acerto ao score individual correspondente.

O **score geral do item** será 1 somente quando as duas comparações do mesmo item coincidirem.

```text
score geral do item = 1
somente se:
A→B = B→B
E
B→A = A→A
```

O percentual geral será:

```text
acertos gerais / itens válidos × 100
```

Itens marcados como “Não se aplica” ficam fora do denominador.

### RF-103 — Faixas atuais

O material atual utiliza:

```text
0–33   = ruim
34–66  = regular
67–100 = bom
```

Essas faixas deverão ser configuráveis/versionáveis antes da versão de produção, evitando valores permanentes somente no frontend.

### RF-104 — Barra de resultado

O resultado deverá poder ser apresentado graficamente por barra percentual.

Apresentação visual obrigatória:

- Ruim (0–33): vermelho;
- Regular (34–66): amarelo;
- Bom (67–100): verde.

Como o cálculo pode possuir duas casas decimais, os limites técnicos contínuos são 0–33,99; 34–66,99; 67–100.

### RF-105 — Resultado por seção

Quando tecnicamente possível e aprovado para o instrumento, o sistema deverá permitir consolidar resultados por seção/tópico.

### RF-106 — Itens coincidentes e divergentes

A área profissional deverá poder identificar itens comparáveis com:

- coincidência;
- divergência;
- exclusão por “Não se aplica”.

### RF-107 — Resultado automático por e-mail da Avaliação Conjugal

Quando os dois participantes concluírem a **Avaliação Conjugal**, o sistema deverá calcular os scores e enviar automaticamente ao e-mail de contato da aplicação:

- score percentual do participante A;
- score percentual do participante B;
- score geral;
- título e texto interpretativo conforme o score geral;
- aviso de que o resultado automático não substitui a devolutiva profissional.

As faixas narrativas são:

- 80,00–100,00%: **Uma percepção compartilhada muito positiva**;
- 60,00–79,99%: **Uma boa compreensão, com espaço para aprofundar o diálogo**;
- 40,00–59,99%: **Uma oportunidade de se conhecerem melhor**;
- 20,00–39,99%: **Um convite à redescoberta**;
- 0,00–19,99%: **Um caminho para construir maior compreensão**.

Essas cinco faixas narrativas não substituem as três faixas técnicas Ruim / Regular / Bom usadas no resultado persistido e na barra gráfica.

Os textos conjugais não deverão ser aplicados automaticamente a outros instrumentos.

---

## 5.12 Comentário profissional e devolutiva

### RF-110 — Comentário profissional

O profissional deverá poder registrar comentário associado ao resultado/aplicação.

### RF-111 — Separação entre cálculo e comentário

O comentário profissional não poderá alterar respostas nem resultados técnicos calculados.

### RF-112 — Disponibilização do resultado

O sistema deverá permitir disponibilizar o resultado aos participantes conforme regra de liberação definida pelo profissional/sistema.

### RF-113 — Envio ao e-mail de contato

O resultado deverá poder ser encaminhado ou disponibilizado a partir do e-mail de contato associado à aplicação.

Na **Avaliação Conjugal**, após a conclusão dos dois participantes, a plataforma envia automaticamente um resumo com os três scores agregados e o texto interpretativo definido em RF-107.

A devolutiva profissional permanece um fluxo separado. Quando o profissional a liberar, a disponibilização completa será feita por **link seguro enviado por e-mail via SMTP Brevo**. O link somente ficará ativo depois da liberação profissional. O token bruto não será armazenado no banco; será persistido apenas seu hash SHA-256.

O link público deverá apresentar o resultado comparativo liberado, a síntese, as observações e o comentário profissional, sem expor as respostas individuais brutas de cada participante.

### RF-114 — Relatório/devolutiva

A arquitetura deverá comportar relatório/devolutiva futura com:

- síntese;
- observações;
- comentário profissional;
- status;
- data de finalização.

---

## 5.13 Painel profissional

### RF-120 — Dashboard

O profissional deverá possuir uma visão resumida de:

- aplicações recentes;
- pendentes;
- em andamento;
- concluídas;
- resultados disponíveis.

### RF-121 — Lista de aplicações

A área profissional deverá permitir busca e consulta por:

- participante;
- vínculo;
- instrumento;
- status;
- período, quando implementado.

### RF-122 — Detalhe da aplicação

O profissional deverá visualizar:

- identificação da aplicação;
- participantes;
- status de cada participante;
- versão do instrumento;
- itens excluídos;
- resultados quando disponíveis;
- comentário profissional.

---

## 5.14 Notificações

### RF-130 — Infraestrutura de mensagens

A V1 utilizará mensagens transacionais por **e-mail via SMTP Brevo**.

Os fluxos implementados incluem:

- envio inicial dos dois acessos da avaliação;
- reenvio individual de acesso de participante ainda não concluído;
- envio automático dos scores e texto interpretativo da Avaliação Conjugal quando os dois participantes concluem;
- envio da devolutiva liberada;
- reenvio da devolutiva liberada.

Sempre que um link seguro for reenviado, um novo token será gerado e o link anterior correspondente ficará inválido.

### RF-131 — Convite por e-mail

O e-mail é o canal transacional obrigatório da V1.

As credenciais SMTP devem permanecer apenas no arquivo `.env` e nunca devem ser versionadas.

### RF-132 — Outros canais

SMS e WhatsApp permanecem fora do escopo da V1. A arquitetura poderá receber esses canais em versão futura.

---

## 5.15 Histórico e auditoria

### RF-140 — Preservação histórica

Aplicações concluídas deverão preservar:

- versão do instrumento;
- participantes/snapshots;
- respostas;
- exclusões;
- cálculo;
- comentário/devolutiva.

### RF-141 — Auditoria

A V1 deverá registrar ações relevantes em trilha de auditoria, incluindo:

- entidade afetada;
- ação;
- ator;
- profissional responsável;
- data/hora;
- contexto não sensível;
- IP;
- user agent.

A auditoria não deverá armazenar senhas, tokens, JWT, cabeçalhos de autorização ou credenciais SMTP.

A área profissional deverá permitir consulta dos eventos vinculados ao próprio profissional.

---

# 6. Regras de negócio

## RN-001 — Par relacional

Cada aplicação trabalha com exatamente duas posições operacionais: A e B.

## RN-002 — Pessoas distintas

A e B não podem representar a mesma pessoa dentro do mesmo vínculo.

## RN-003 — Independência

Os participantes devem responder separadamente.

## RN-004 — Quatro contextos de resposta

O modelo de dados deve suportar:

```text
A → A
A → B
B → B
B → A
```

## RN-005 — Comparações válidas

As comparações principais são:

```text
A → B × B → B
B → A × A → A
```

## RN-006 — “Não se aplica”

Um item excluído por “Não se aplica” não entra no cálculo daquela aplicação.

## RN-007 — Versionamento

A aplicação deve apontar para uma versão específica do instrumento.

## RN-008 — Histórico

Alterações futuras em pessoa, vínculo ou instrumento não devem reescrever silenciosamente dados históricos da aplicação.

## RN-009 — Token

Tokens de participante devem ser armazenados somente em hash.

## RN-010 — Resultado técnico

O resultado deve ser calculado no backend.

## RN-011 — Comentário profissional

Comentários são informação adicional e não alteram o cálculo técnico.

## RN-012 — Múltiplos instrumentos

O profissional pode criar mais de um instrumento no mesmo modelo de plataforma.

---

# 7. Requisitos não funcionais

## RNF-001 — Arquitetura

Backend e frontend permanecerão separados.

### Backend

- PHP 8.2+;
- Slim 4;
- MySQL 8;
- PDO;
- JWT;
- PHP dotenv;
- Monolog.

### Frontend

- React;
- Vite;
- JavaScript/JSX;
- Tailwind CSS v4.

## RNF-002 — Responsividade

A interface deverá funcionar adequadamente em:

- desktop;
- tablet;
- celular.

## RNF-003 — Usabilidade

A interface deverá priorizar:

- clareza;
- linguagem simples;
- baixo atrito;
- legibilidade;
- sequência de preenchimento fácil de entender.

## RNF-004 — Identidade visual

A plataforma utilizará a identidade visual aprovada do projeto, incluindo o logotipo oficial.

Paleta registrada:

- verde escuro: `#385048`;
- verde médio: `#88B098`;
- verde claro: `#A8C8B8`;
- dourado/bege: `#D8B078`;
- azul suave: `#A8C8D0`;
- fundo claro: `#FEFDFB`.

## RNF-005 — Segurança

O sistema deverá:

- não versionar segredos;
- manter credenciais em `.env`;
- usar hash para senhas e tokens;
- proteger rotas autenticadas;
- validar autorização no backend;
- evitar exposição das respostas de um participante ao outro durante o preenchimento.

## RNF-006 — Persistência

Regras críticas não devem depender exclusivamente do frontend.

## RNF-007 — Integridade histórica

Mudanças estruturais em instrumentos devem usar versionamento.

## RNF-008 — Migrações

Alterações de banco deverão ocorrer por migrations SQL sequenciais.

Migrações aplicadas tornam-se imutáveis; novas alterações devem usar novos arquivos.

## RNF-009 — Logs

A API deverá registrar erros e eventos técnicos relevantes sem incluir segredos.

## RNF-010 — Rastreabilidade

Resultados devem ser reproduzíveis a partir das respostas e da versão do algoritmo/regra aplicável.

## RNF-011 — Compatibilidade

A solução deverá ser compatível com o ambiente previsto de produção:

- Ubuntu;
- Apache;
- PHP 8.2+;
- MySQL 8;
- HTTPS.

## RNF-012 — Proteção de dados

Como a plataforma manipula dados pessoais e respostas relacionais, o projeto deverá incorporar práticas adequadas de proteção, minimização, controle de acesso e retenção de dados.

O detalhamento jurídico e operacional definitivo deve ser validado antes da produção.

---

# 8. Modelo de dados mínimo já aprovado para a base

A primeira migration funcional contém:

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

Além dessas, o runner mantém:

- `schema_migrations`.

Entidades previstas para migrations posteriores:

- comparações;
- resultados;
- comentários profissionais;
- relatórios/devolutivas;
- auditoria;
- estruturas do site institucional;
- autenticação profissional, conforme desenho final.

---

# 9. Fluxos principais

## 9.1 Fluxo profissional — preparação

```text
Login
→ configurar perfil profissional
→ configurar site institucional
→ criar/selecionar instrumento
→ publicar versão
→ criar aplicação
→ gerar acessos A/B
→ enviar links
```

## 9.2 Fluxo participante

```text
Abrir link
→ validar token
→ identificação
→ responder itens
→ marcar “Não se aplica” quando necessário
→ salvar progresso
→ concluir
```

## 9.3 Fluxo de conclusão

```text
A conclui
+
B conclui
→ validar itens comparáveis
→ calcular comparações
→ calcular percentuais
→ gerar resultado
→ profissional consulta
→ profissional adiciona comentário
→ resultado pode ser liberado
```

## 9.4 Fluxo público pelo site

```text
Visitante acessa página institucional
→ conhece a profissional/instrumento
→ acessa chamada para avaliação
→ inicia cadastro/solicitação quando habilitado
ou
→ utiliza link individual recebido externamente
```

---

# 10. Critérios gerais de aceitação da V1

A V1 funcional deverá ser considerada apta quando for possível:

- autenticar o profissional;
- configurar a identificação profissional;
- cadastrar pessoas;
- criar vínculos;
- criar e versionar instrumento;
- criar seções, itens e alternativas;
- configurar “Não se aplica”;
- criar aplicação;
- gerar dois acessos;
- permitir que A e B respondam separadamente;
- salvar e retomar preenchimento;
- concluir os dois formulários;
- excluir corretamente itens marcados como “Não se aplica”;
- comparar A→B com B→B;
- comparar B→A com A→A;
- calcular percentuais;
- exibir resultado;
- permitir comentário profissional;
- preservar histórico;
- manter as rotas profissionais protegidas;
- manter respostas isoladas entre participantes;
- executar as migrations em banco vazio sem erro;
- passar nas validações automáticas definidas para backend e frontend.

---

# 11. Fora do escopo inicial ou dependente de decisão

Os itens abaixo não devem ser considerados definidos apenas por este documento:

- aplicativo móvel nativo;
- pagamentos/assinaturas;
- múltiplos níveis de profissionais/equipes;
- integração com prontuário eletrônico;
- assinatura digital;
- videochamada;
- IA para interpretação clínica;
- diagnóstico automático;
- regras jurídicas finais de consentimento e retenção;
- conteúdo definitivo de relatórios;
- canal definitivo de WhatsApp;
- provedor definitivo de SMS;
- política comercial da plataforma.

Qualquer inclusão deverá ser registrada como nova decisão de produto.

---

# 12. Decisões ainda abertas

Antes de determinadas etapas, deverão ser fechadas explicitamente:

1. modelo definitivo de autenticação do profissional;
2. criação de conta permanente ou acesso apenas por convite para participantes;
3. canais obrigatórios da V1 para envio de convite;
4. validade e regras de expiração dos tokens;
5. forma final de liberação do resultado ao participante;
6. regra de resultado global;
7. nomenclatura final das faixas interpretativas;
8. conteúdo e formato definitivo da devolutiva;
9. política de consentimento;
10. retenção e exclusão de dados;
11. escopo exato do editor institucional na V1;
12. domínio/URLs finais de produção.

---

# 13. Priorização sugerida de implementação

## Etapa 3 — autenticação e profissional

- login;
- senha;
- JWT;
- proteção de rotas;
- perfil profissional.

## Etapa 4 — instrumentos

- instrumentos;
- versões;
- seções;
- itens;
- alternativas;
- publicação.

## Etapa 5 — pessoas e vínculos

- pessoas;
- vínculos;
- validações.

## Etapa 6 — aplicações e acessos

- aplicação;
- participantes A/B;
- token;
- link;
- acompanhamento.

## Etapa 7 — formulário

- identificação;
- duas perspectivas;
- persistência;
- retomada;
- “Não se aplica”;
- conclusão.

## Etapa 8 — comparação e resultado

- comparações;
- percentuais;
- faixas;
- barras;
- resultado por sentido;
- visão profissional.

## Etapa 9 — comentários e devolutiva

- comentários;
- liberação;
- envio/acesso de resultado.

## Etapa 10 — site institucional

- perfil público;
- editor de blocos;
- imagens;
- áudio;
- chamadas para ação;
- entrada para avaliação.

A ordem poderá ser ajustada pelo STATUS sem alterar os requisitos aprovados.

---

# 14. Referências internas do projeto

Este documento foi consolidado a partir de:

- material original da Avaliação Conjugal;
- descrição funcional da plataforma;
- `docs/MAPA_RELACIONAL_CONTEXT.md`;
- `docs/MAPA_RELACIONAL_STATUS.md`;
- `docs/DECISOES.md`;
- `docs/MODELO_DOMINIO_V1.md`;
- decisões confirmadas durante o desenvolvimento.

---

# 15. Regra de manutenção deste documento

Alterações que mudem o comportamento esperado do produto devem:

1. atualizar este documento;
2. registrar decisão em `DECISOES.md` quando a mudança for estrutural;
3. atualizar `MAPA_RELACIONAL_CONTEXT.md` se afetar o contexto permanente;
4. atualizar `MAPA_RELACIONAL_STATUS.md` quando a mudança entrar em desenvolvimento ou for concluída;
5. ser implementadas por etapa pequena e testável.
