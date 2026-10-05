# Fechamento formal da V1

**Produto:** Avaliação de Percepção Relacional  
**Versão:** 1.0.0  
**Data de fechamento:** 2026-10-05  
**Repositório:** `kriale-dot/mapa-percepcao-relacional`  
**Branch de referência:** `main`

## 1. Estado formal

A V1 está **formalmente encerrada em escopo e desenvolvimento funcional**.

Isso significa que:

- o escopo funcional definido para a V1 foi implementado;
- a estrutura de banco necessária à V1 está definida pelas migrations 001–008;
- os fluxos centrais do produto estão implementados;
- a infraestrutura técnica local foi validada até a Etapa 10;
- novas funcionalidades deixam de entrar na V1;
- a partir deste marco, somente correções de defeitos, ajustes de deploy, segurança e compatibilidade podem alterar a V1;
- funcionalidades novas devem ser planejadas para uma V2.

O fechamento formal da V1 **não equivale a declarar o sistema já publicado em produção**.

Antes do deploy, continuam obrigatórias as validações de homologação descritas em `docs/PRODUCAO_V1.md`.

## 2. Escopo entregue

A V1 contempla:

### Área pública

- página pública institucional;
- catálogo de avaliações publicadas;
- autoatendimento para início de uma avaliação;
- cadastro de nomes dos dois participantes;
- e-mail de contato;
- tipo de vínculo;
- tempo de união;
- criação automática da aplicação;
- geração de dois acessos independentes;
- envio dos dois acessos por e-mail via SMTP Brevo.

### Participantes

- acesso individual por token seguro;
- identificação inicial;
- preenchimento nas perspectivas `SOBRE_MIM` e `SOBRE_OUTRO`;
- salvamento progressivo;
- retomada pelo mesmo link;
- regra global de “Não se aplica”;
- conclusão individual;
- bloqueio de alteração depois da conclusão.

### Comparação e resultados

- comparação `A→B × B→B`;
- comparação `B→A × A→A`;
- persistência item a item de coincidências e divergências;
- exclusão dos itens “Não se aplica” do denominador;
- percentual por sentido;
- faixa interpretativa por versão;
- resultado por seção;
- painel profissional detalhado.

### Área profissional

- autenticação JWT;
- perfil e senha;
- instrumentos;
- versões;
- seções;
- itens;
- alternativas;
- pessoas e vínculos administrativos;
- avaliações;
- filtros;
- dashboard;
- detalhe de aplicação;
- resultados;
- configuração das faixas por versão;
- devolutiva profissional;
- auditoria.

### Devolutiva

- síntese;
- observações;
- comentário profissional;
- estado rascunho;
- liberação explícita;
- envio por e-mail;
- link público seguro;
- reenvio com rotação de token;
- proteção contra exposição das respostas individuais brutas.

### Segurança e operação

- tokens persistidos somente por hash;
- rotação de token nos reenvios;
- rate limit de login;
- rate limit do autoatendimento público;
- trilha de auditoria;
- CORS;
- cabeçalhos HTTP defensivos;
- backup do banco;
- checklist automatizado da V1.

## 3. Banco de dados da V1

A V1 é composta pelas migrations:

```text
001_base_dominio.sql
002_profissional_autenticacao.sql
003_profissional_perfil.sql
004_resultados_comparacoes.sql
005_ajustar_faixas_percentuais.sql
006_devolutivas.sql
007_auditoria.sql
008_rate_limites.sql
```

Não adicionar novas migrations à V1 para funcionalidades novas.

Uma migration adicional na linha 1.x somente será aceitável para correção indispensável de defeito, integridade ou deploy.

## 4. Estado de validação

### Confirmado localmente

Foram confirmados durante o desenvolvimento:

- fundação técnica;
- banco e migrations;
- autenticação profissional;
- catálogo de instrumentos e suas estruturas;
- pessoas e vínculos;
- fluxo de aplicações;
- envio SMTP Brevo;
- acessos individuais;
- preenchimento progressivo;
- backup;
- `composer check-domain`;
- `composer check-v1` sem erros críticos após aplicação das migrations 007 e 008.

### Não considerar como homologação final

As Etapas 7.2, 8 e 9 foram implementadas sem uma rodada formal completa de validação funcional antes de se avançar.

Portanto, antes de produção ainda é necessário executar um fluxo de homologação ponta a ponta incluindo:

- faixas de resultado;
- dashboard e filtros;
- devolutiva;
- link público do resultado;
- auditoria;
- reenvios;
- rate limit;
- restauração de backup;
- comportamento em HTTPS e configuração real de produção.

Essa pendência não reabre o escopo da V1. Ela é uma pendência de **aceitação e deploy**.

## 5. Regra de congelamento

A partir deste fechamento:

### Permitido na V1

- correção de bug;
- correção de segurança;
- correção de migration;
- ajuste necessário ao deploy;
- correção de compatibilidade;
- correção de texto ou interface sem mudar regra funcional;
- ajuste de configuração operacional.

### Não permitido na V1

- novo módulo;
- nova regra de negócio;
- nova forma de cálculo;
- novo canal de comunicação;
- novo papel de usuário;
- nova família de relatórios;
- funcionalidade que altere o escopo aprovado.

Esses itens devem ser registrados para a V2.

## 6. Critério para publicação

A V1 poderá ser declarada **em produção** somente depois de:

1. configurar o ambiente de produção;
2. aplicar migrations 001–008;
3. executar `composer check-domain`;
4. executar `composer check`;
5. executar `composer check-v1`;
6. criar backup;
7. validar restauração em banco separado;
8. executar `npm run build`;
9. realizar um fluxo completo público → A/B → resultados → devolutiva;
10. confirmar envio SMTP real;
11. confirmar auditoria;
12. confirmar reenvio e invalidação dos links anteriores;
13. confirmar HTTPS e CORS do domínio definitivo.

## 7. Próximo marco

O desenvolvimento funcional da V1 está encerrado.

O próximo marco é:

```text
DEPLOY / HOMOLOGAÇÃO DA V1.0.0
```

Após o deploy e a homologação, qualquer expansão funcional deve iniciar planejamento de V2.
