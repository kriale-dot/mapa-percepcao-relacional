# Mapa de Percepção Relacional — Contexto permanente do projeto

> Documento curto para iniciar novas conversas de desenvolvimento sem depender do histórico completo do chat.
> Repositório oficial: `kriale-dot/mapa-percepcao-relacional`
> Branch principal: `main`

## 1. Identidade do produto

**Nome:** Mapa de Percepção Relacional  
**Subtítulo:** Instrumento de percepção mútua e conhecimento interpessoal.

O Mapa de Percepção Relacional é uma plataforma digital para comparar, de forma estruturada, como duas pessoas percebem a si mesmas, a outra pessoa e a relação entre elas.

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

Usar o logotipo oficial do **Mapa de Percepção Relacional** já criado para o projeto.

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

O banco deve evoluir por migrações SQL sequenciais.

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

Os dois documentos prioritários são:

```text
docs/MAPA_RELACIONAL_CONTEXT.md
docs/MAPA_RELACIONAL_STATUS.md
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

## 10. Como iniciar uma nova conversa no ChatGPT

Usar um pedido semelhante a:

```text
Acesse o repositório kriale-dot/mapa-percepcao-relacional no GitHub.

Leia primeiro:
- docs/MAPA_RELACIONAL_CONTEXT.md
- docs/MAPA_RELACIONAL_STATUS.md

Depois consulte somente os arquivos necessários para a tarefa atual.

Não altere decisões estruturais registradas no CONTEXT sem me avisar.

Ao terminar uma etapa relevante, atualize o STATUS.
```

O `CONTEXT` explica como o sistema deve funcionar.
O `STATUS` informa exatamente onde o desenvolvimento está naquele momento.
