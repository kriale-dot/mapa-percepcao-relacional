# Avaliação de Percepção Relacional — Modelo de domínio V1

> Documento de modelagem conceitual inicial. Este arquivo descreve as entidades e relações antes da criação das primeiras migrações funcionais.

## 1. Princípio central

O sistema trabalha com **duas pessoas vinculadas a uma mesma aplicação relacional**.

Para um par A/B, cada participante responde em duas perspectivas:

```text
A → A = A responde sobre si
A → B = A responde sobre B

B → B = B responde sobre si
B → A = B responde sobre A
```

As comparações principais são:

```text
A → B × B → B
B → A × A → A
```

Assim, o sistema compara a percepção que uma pessoa tem da outra com a resposta que a outra pessoa deu sobre si mesma.

## 2. Separação entre pessoa, vínculo e aplicação

A modelagem deve manter três conceitos separados.

### Pessoa

Representa um indivíduo cadastrado no sistema.

Exemplos:

- Ana;
- Carlos;
- mãe;
- filho;
- empregador;
- empregado.

A pessoa existe independentemente de uma avaliação específica.

### Vínculo relacional

Representa a relação entre duas pessoas.

Exemplos:

- casal;
- pai e filho;
- mãe e filha;
- amigos;
- líder e liderado;
- empregador e empregado;
- outro.

Um mesmo indivíduo pode participar de mais de um vínculo.

### Aplicação

É uma ocorrência concreta do instrumento para um vínculo.

Exemplo:

```text
Ana + Carlos
Instrumento: Avaliação de Percepção Relacional
Aplicação iniciada em 10/10/2026
```

Uma mesma dupla pode realizar novas aplicações ao longo do tempo, preservando o histórico.

## 3. Entidades principais

### 3.1 profissionais

Representa o profissional que utiliza a área profissional da plataforma.

Campos conceituais mínimos:

- id;
- nome;
- email;
- senha_hash, nulo somente enquanto a configuração inicial ainda não foi concluída;
- telefone opcional;
- status;
- senha_alterada_em;
- ultimo_login_em;
- created_at;
- updated_at.

Autenticação e permissões serão detalhadas em etapa própria.

### 3.2 pessoas

Representa os participantes/informantes da avaliação.

Campos conceituais mínimos:

- id;
- nome;
- email opcional;
- telefone opcional;
- data de nascimento opcional;
- observação administrativa opcional;
- status;
- created_at;
- updated_at.

A tabela não deve armazenar respostas do instrumento.

### 3.3 vinculos

Representa uma relação entre exatamente duas pessoas.

Campos conceituais mínimos:

- id;
- profissional_id;
- pessoa_a_id;
- pessoa_b_id;
- tipo;
- descricao_tipo opcional;
- status;
- created_at;
- updated_at.

Regras:

- `pessoa_a_id` e `pessoa_b_id` não podem ser iguais;
- a ordem A/B é operacional e deve permanecer estável dentro do vínculo;
- o tipo deve permitir `OUTRO`.

### 3.4 instrumentos

Representa o instrumento lógico.

Na V1 haverá inicialmente o próprio **Avaliação de Percepção Relacional**, mas a estrutura deve permitir versionamento.

Campos:

- id;
- nome;
- descricao;
- status;
- created_at;
- updated_at.

### 3.5 instrumento_versoes

Preserva a estrutura utilizada em cada aplicação.

Campos:

- id;
- instrumento_id;
- numero_versao;
- status;
- publicado_em;
- created_at;
- updated_at.

Uma aplicação deve apontar para uma versão específica, para que mudanças futuras nas perguntas não alterem aplicações antigas.

### 3.6 secoes

Agrupa perguntas do instrumento por tema.

Campos:

- id;
- instrumento_versao_id;
- titulo;
- descricao opcional;
- ordem;
- ativo.

### 3.7 itens

Representa cada pergunta/afirmação.

Campos conceituais:

- id;
- secao_id;
- codigo;
- texto;
- tipo_resposta;
- ordem;
- permite_nao_se_aplica;
- ativo.

O modelo deve permitir expansão futura de tipos de resposta sem alterar respostas históricas.

### 3.8 alternativas

Usada quando um item possui opções fechadas.

Campos:

- id;
- item_id;
- valor;
- rotulo;
- ordem;
- ativo.

### 3.9 aplicacoes

Representa a realização concreta do instrumento por um vínculo.

Campos:

- id;
- profissional_id;
- vinculo_id;
- instrumento_versao_id;
- status;
- iniciada_em;
- concluida_em;
- created_at;
- updated_at.

Status iniciais sugeridos:

```text
RASCUNHO
PRONTA
EM_ANDAMENTO
CONCLUIDA
CANCELADA
```

### 3.10 aplicacao_participantes

Cria os dois lados operacionais A e B da aplicação e desacopla a aplicação da forma futura de autenticação.

Campos:

- id;
- aplicacao_id;
- pessoa_id;
- lado;
- status;
- iniciou_em;
- concluiu_em;
- created_at;
- updated_at.

Valores de `lado`:

```text
A
B
```

Cada aplicação deve possuir exatamente um participante A e um participante B.

### 3.11 respostas

Cada resposta precisa registrar:

- quem respondeu;
- sobre quem respondeu;
- qual item;
- qual aplicação;
- valor informado;
- se marcou “Não se aplica”;
- momento da resposta.

Campos conceituais:

- id;
- aplicacao_id;
- respondente_id;
- alvo_id;
- item_id;
- alternativa_id opcional;
- valor_texto opcional;
- valor_numero opcional;
- nao_se_aplica;
- respondido_em;
- created_at;
- updated_at.

Isso permite representar diretamente:

```text
A → A
A → B
B → B
B → A
```

sem duplicar estruturas.

Regra importante:

- `respondente_id` e `alvo_id` referem-se a registros de `aplicacao_participantes`, e não diretamente a pessoas.

Assim toda resposta fica congelada no contexto daquela aplicação.

## 4. Fluxo de cadastro, dois acessos e identificação

O material-base define que uma avaliação é iniciada a partir de um e-mail de contato e gera dois acessos, um para cada participante.

Para preservar essa regra sem armazenar senhas em texto puro, a implementação usará **tokens/códigos de acesso individuais com hash**.

### 4.1 aplicacoes — campos adicionais

Além dos campos já descritos, a aplicação precisa registrar:

- email_contato;
- tipo_vinculo_snapshot;
- duracao_vinculo_texto opcional;
- enviado_em opcional.

O `vinculo_id` pode ser nulo no momento inicial, pois a aplicação pode existir antes de os dois participantes terem preenchido seus dados de identificação.

### 4.2 aplicacao_participantes — snapshot de identificação

Cada aplicação possui exatamente dois slots: A e B.

Além de `pessoa_id` opcional, cada slot deve preservar os dados informados naquele preenchimento:

- nome_snapshot;
- idade_snapshot;
- genero_snapshot;
- lado A/B;
- status;
- iniciou_em;
- concluiu_em.

Isso evita que mudanças futuras no cadastro permanente da pessoa alterem o histórico da avaliação.

### 4.3 acessos_aplicacao

Cada participante recebe um acesso individual.

Campos conceituais:

- id;
- aplicacao_participante_id;
- token_hash;
- status;
- enviado_em;
- primeiro_acesso_em;
- ultimo_acesso_em;
- concluido_em;
- revogado_em;
- created_at;
- updated_at.

Regras:

- nunca armazenar o código de acesso em texto puro;
- deve existir no máximo um acesso ativo por participante da aplicação;
- o acesso pode permitir retomada enquanto o formulário não estiver concluído;
- após conclusão ou revogação, não pode iniciar novo preenchimento.

### 4.4 exclusão de item por “Não se aplica”

A marcação “Não se aplica” tem efeito sobre a **aplicação inteira**, e não somente sobre uma linha de resposta.

Criar a entidade `aplicacao_itens_excluidos`:

- id;
- aplicacao_id;
- item_id;
- marcado_por_participante_id;
- motivo opcional;
- created_at.

Regras:

- combinação `aplicacao_id + item_id` é única;
- item excluído não entra no denominador da pontuação;
- se o outro participante ainda não respondeu esse item, ele não deve ser apresentado;
- se já houver resposta do outro participante, ela pode ser preservada para auditoria, mas deve ser ignorada no cálculo.

## 5. Comparações e resultados

### 5.1 comparacoes

As comparações podem ser calculadas novamente enquanto a aplicação está em andamento, mas ao concluir a aplicação é recomendável persistir um snapshot técnico.

Campos:

- id;
- aplicacao_id;
- item_id;
- sentido;
- resposta_percebida_id;
- resposta_autorreferida_id;
- comparavel;
- coincide;
- created_at.

Valores de `sentido`:

```text
A_SOBRE_B
B_SOBRE_A
```

Se qualquer uma das respostas necessárias estiver marcada como `nao_se_aplica`, a comparação não entra no denominador válido.

### 5.2 resultados

Consolida o resultado técnico da aplicação.

Campos conceituais:

- id;
- aplicacao_id;
- sentido;
- comparacoes_validas;
- coincidencias;
- percentual;
- faixa;
- algoritmo_versao;
- calculado_em;
- created_at.

Fórmula atual:

```text
percentual = coincidencias / comparacoes_validas × 100
```

Faixas atualmente registradas no projeto:

```text
0–33   = ruim
34–66  = regular
67–100 = bom
```

As faixas não devem ficar codificadas somente na interface. Devem ser configuráveis/versionáveis no backend antes da versão de produção do instrumento.

## 6. Comentários profissionais e devolutiva

### 6.1 comentarios_profissionais

Permite ao profissional registrar observações sem alterar respostas ou resultados técnicos.

Campos:

- id;
- aplicacao_id;
- profissional_id;
- texto;
- created_at;
- updated_at.

### 6.2 relatorios

Representa uma devolutiva ou relatório associado à aplicação.

Campos iniciais:

- id;
- aplicacao_id;
- profissional_id;
- status;
- sintese;
- observacoes;
- finalizado_em;
- created_at;
- updated_at.

A definição do formato final da devolutiva será feita em etapa posterior.

## 7. Acesso e convite

O domínio deve permitir futuramente acesso por convite sem obrigar todos os participantes a possuírem conta.

Entidades previstas:

- `convites`;
- tokens temporários armazenados em hash;
- validade;
- revogação;
- registro de primeiro acesso.

A implementação será feita junto da etapa de autenticação/acesso remoto.

## 8. Auditoria

Ações relevantes devem poder ser auditadas.

Entidade futura:

### auditoria

Campos esperados:

- id;
- usuario_id opcional;
- profissional_id opcional;
- entidade;
- entidade_id;
- acao;
- dados_contexto JSON;
- ip;
- user_agent;
- request_id;
- created_at.

## 9. Relações resumidas

```text
PROFISSIONAL
   │
   ├── PESSOAS
   │
   └── VÍNCULOS
          │
          ├── PESSOA A
          └── PESSOA B
                │
                ▼
             APLICAÇÃO
                │
                ├── participante A
                ├── participante B
                │
                ├── RESPOSTAS
                │      ├── A→A
                │      ├── A→B
                │      ├── B→B
                │      └── B→A
                │
                ├── COMPARAÇÕES
                │      ├── A→B × B→B
                │      └── B→A × A→A
                │
                └── RESULTADOS
```

Em paralelo:

```text
INSTRUMENTO
   └── VERSÃO
       └── SEÇÕES
           └── ITENS
               └── ALTERNATIVAS
```

A aplicação aponta para uma versão publicada específica do instrumento.

## 10. Regras que a primeira migração deve preservar

A primeira migração funcional não deve tentar implementar o produto inteiro.

Ela deve criar apenas a base necessária para:

1. profissionais;
2. pessoas;
3. vínculos;
4. instrumentos e versões;
5. seções, itens e alternativas;
6. aplicações;
7. participantes da aplicação;
8. acessos individuais dos dois participantes;
9. exclusões de itens por “Não se aplica”;
10. respostas.

Comparações, resultados consolidados, relatórios e auditoria podem entrar em migrações posteriores para manter as etapas pequenas e testáveis.

## 11. Decisões ainda abertas

Este documento não fixa silenciosamente:

- perfis oficiais de autenticação;
- se participante terá conta permanente ou apenas convite;
- canais de convite;
- regras finais de disponibilização do resultado;
- conteúdo definitivo do relatório;
- nomenclatura final das faixas interpretativas;
- tipos definitivos de resposta do instrumento.

Essas decisões serão fechadas em etapas próprias antes de dependências irreversíveis no banco.
