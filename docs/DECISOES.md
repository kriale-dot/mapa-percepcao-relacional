# Avaliação de Percepção Relacional — Registro de decisões

> Registro curto das decisões técnicas e funcionais que afetam a continuidade do projeto.
> Novas decisões relevantes devem ser acrescentadas ao final; não apagar decisões antigas sem registrar a substituição.

## D-001 — Repositório único
**Data:** 2026-10-03  
**Status:** vigente

O projeto permanece em um único repositório GitHub, separando backend, frontend, documentação e deploy.

## D-002 — Backend e frontend separados
**Data:** 2026-10-03  
**Status:** vigente

- Backend: PHP 8.2+, Slim 4 e MySQL 8.
- Frontend: React + Vite + Tailwind CSS v4.
- A comunicação entre ambos deve ocorrer pela API.

## D-003 — Migrações em `mapa-relacional-api/migrations/`
**Data:** 2026-10-03  
**Status:** vigente

As migrações SQL sequenciais ficam em `mapa-relacional-api/migrations/`.

O nome do arquivo continua sendo a identidade registrada em `schema_migrations`. Mover a pasta não altera o nome nem o checksum do conteúdo da migração já aplicada.

## D-004 — Segredos e dependências não são versionados
**Data:** 2026-10-03  
**Status:** vigente

Não entram no GitHub:

- arquivos `.env` reais;
- senhas, tokens e chaves;
- `vendor/`;
- `node_modules/`;
- artefatos de build e logs.

O repositório mantém somente `.env.example` sem credenciais reais. `vendor/` é criado localmente por `composer install`.

## D-005 — Documentos de continuidade
**Data:** 2026-10-03  
**Status:** vigente

Antes de uma nova etapa de desenvolvimento, consultar:

1. `docs/MAPA_RELACIONAL_CONTEXT.md`;
2. `docs/MAPA_RELACIONAL_STATUS.md`;
3. `docs/DECISOES.md`;
4. o código atual da branch `main`.

## D-006 — GitHub como fonte de verdade
**Data:** 2026-10-03  
**Status:** vigente

O estado do código deve ser confirmado no GitHub. O histórico de conversas não substitui o repositório.

Ao concluir uma etapa relevante:

1. testar;
2. atualizar o STATUS;
3. atualizar CONTEXT ou DECISOES quando houver mudança estrutural;
4. commit;
5. push.


## D-007 — Fluxo oficial de recriação do banco
**Data:** 2026-10-03  
**Status:** vigente

Para recriar o banco de desenvolvimento, o fluxo oficial é:

1. criar o banco `mapa_relacional` vazio;
2. executar `composer migrate`;
3. executar `composer check-domain`.

Não importar `001_base_dominio.sql` manualmente quando o objetivo for testar o fluxo normal de instalação. O runner cria `schema_migrations`, aplica as migrations pendentes e registra o checksum.

## D-008 — Validação de tabelas independente de capitalização
**Data:** 2026-10-03  
**Status:** vigente

O `bin/check-domain.php` deve ler a lista de tabelas com `PDO::FETCH_COLUMN`. Isso evita diferenças entre drivers/ambientes que retornam `table_name` ou `TABLE_NAME`.

## D-009 — Nomenclatura oficial do instrumento
**Data:** 2026-10-03  
**Status:** vigente

O nome oficial a ser usado nos textos, interface, documentação e comunicação com usuários é **Avaliação de Percepção Relacional**.

Os nomes técnicos já existentes do repositório, diretórios, namespaces, banco e caminhos internos que usam `mapa-relacional` ou `mapa-percepcao-relacional` permanecem inalterados por enquanto para evitar renomeações sem benefício funcional e preservar a continuidade do desenvolvimento.

## D-010 — Raiz local oficial de desenvolvimento
**Data:** 2026-10-03  
**Status:** vigente

No ambiente Windows utilizado para desenvolvimento, a raiz oficial do repositório é:

```text
E:\\Compartilhar\\Kriale\\Tânia - plataforma digital\\Desenvolvimento
```

O repositório `kriale-dot/mapa-percepcao-relacional` é clonado diretamente nessa pasta. Não deve ser criada uma subpasta local adicional `mapa-percepcao-relacional` para conter o projeto.

A branch padrão de desenvolvimento é `main`, acompanhando `origin/main`. Antes de iniciar novas alterações locais após mudanças feitas no GitHub, executar `git pull` e confirmar `git status` limpo ou compreender explicitamente as alterações locais existentes.

## D-011 — Autenticação profissional por e-mail e senha
**Data:** 2026-10-03  
**Status:** vigente

A área profissional será autenticada por **e-mail + senha**.

A senha nunca será armazenada em texto puro. O banco guarda somente `senha_hash`, compatível com `password_hash()` e `password_verify()` do PHP.

A migration `002_profissional_autenticacao.sql` acrescenta à tabela `profissionais`:

- `senha_hash VARCHAR(255) NULL`;
- `senha_alterada_em DATETIME NULL`;
- `ultimo_login_em DATETIME NULL`.

`senha_hash` é nulo apenas durante a configuração inicial. Um profissional sem hash de senha não poderá autenticar.

Após autenticação válida, a API utilizará JWT. A proteção das rotas será implementada nas próximas subetapas da Etapa 3.

## D-012 — Primeiro profissional criado somente por CLI
**Data:** 2026-10-03  
**Status:** substituída por D-013

A configuração inicial do primeiro profissional não terá cadastro público.

O bootstrap é realizado pelo comando:

```text
composer setup-professional
```

Regras:

- só pode executar com a tabela `profissionais` vazia;
- nome, e-mail e telefone são informados no terminal;
- a senha inicial entra somente pela variável temporária `SETUP_PROFESSIONAL_PASSWORD`;
- a senha é transformada imediatamente por `password_hash(PASSWORD_DEFAULT)`;
- senha em texto puro não é persistida nem registrada pelo sistema;
- após existir um profissional, o setup inicial é bloqueado;
- `composer check-professional` valida a configuração sem precisar conhecer a senha.

## D-013 — Provisionamento inicial sem cadastro público
**Data:** 2026-10-03  
**Status:** vigente

Substitui D-012 quanto ao meio de provisionamento.

Não haverá endpoint público para criação do primeiro profissional. Em desenvolvimento, o primeiro profissional pode ser provisionado diretamente no banco de dados/phpMyAdmin ou pelo utilitário de CLI, desde que a senha seja armazenada somente como hash compatível com `password_verify()`.

Se já existir um profissional inicial, o registro deve ser preservado e não deve ser criada duplicata.

## D-014 — Proteção das rotas profissionais por middleware JWT
**Data:** 2026-10-03  
**Status:** vigente

Todas as rotas profissionais sob `/api/profissional` devem passar pelo middleware de autenticação JWT.

O middleware exige `Authorization: Bearer <token>`, valida o JWT HS256, o emissor, a expiração, o tipo `professional` e o `sub` com o ID do profissional. Além da validação criptográfica, o profissional é consultado no banco em cada requisição protegida e precisa continuar com `status = ATIVO`.

Essa consulta permite bloquear imediatamente um token ainda não expirado caso o profissional seja desativado no banco.

## D-015 — Sessão profissional no frontend
**Data:** 2026-10-03  
**Status:** vigente

Na V1, o frontend profissional usa o JWT Bearer emitido pela API e mantém o token em `sessionStorage`.

A sessão deve:

- persistir durante recargas da página na mesma sessão do navegador;
- ser encerrada quando o usuário clicar em sair;
- ser invalidada localmente quando `GET /api/profissional/me` retornar `401`;
- não armazenar senha no navegador;
- reenviar o JWT apenas em chamadas autenticadas da área profissional.

As rotas iniciais do frontend são `/profissional/login` e `/profissional`.

## D-016 — Perfil profissional da V1
**Data:** 2026-10-03  
**Status:** parcialmente substituída por D-044

O perfil profissional é mantido no próprio registro de `profissionais`. A migration `003_profissional_perfil.sql` acrescenta `descricao`, `atuacao`, `foto_url`, `logo_url` e `dados_contato`.

Na V1 desta etapa:

- fotografia e logotipo são referências por URL HTTP/HTTPS; upload de arquivos não é implementado ainda;
- nome, e-mail, telefone, descrição, atuação e dados de contato podem ser editados pelo profissional autenticado;
- o status é exibido, porém não pode ser alterado pelo próprio formulário de perfil, pois participa da regra de autorização;
- as rotas de leitura e atualização do perfil permanecem protegidas pelo middleware JWT.

## D-017 — Alteração segura de senha
**Data:** 2026-10-03  
**Status:** vigente

A troca de senha do profissional é autenticada e exige a senha atual.

Regras:

- endpoint protegido: `PUT /api/profissional/senha`;
- a nova senha deve ter pelo menos 8 caracteres e ser diferente da senha atual;
- a senha atual é validada com `password_verify()`;
- a nova senha é armazenada somente por `password_hash(PASSWORD_DEFAULT)`;
- `senha_alterada_em` é atualizado após sucesso;
- o JWT contém uma impressão digital SHA-256 do hash vigente da senha;
- o middleware compara essa impressão com o hash atual do banco, invalidando tokens antigos após uma troca de senha;
- após alterar a senha, o frontend remove o JWT local e exige novo login.

## D-018 — Ciclo de vida inicial dos instrumentos
**Data:** 2026-10-03  
**Status:** vigente

Na V1, os instrumentos pertencem ao profissional autenticado e usam os estados `RASCUNHO`, `ATIVO` e `ARQUIVADO`.

A exclusão física de um instrumento somente é permitida quando ele ainda não possui nenhuma versão. Se já houver versão vinculada, a API recusa a exclusão para preservar a estrutura histórica; nesse caso, o instrumento deve ser arquivado.

Todas as consultas e alterações de instrumentos devem ser filtradas por `profissional_id`, impedindo acesso cruzado entre profissionais.

## D-019 — Imutabilidade das versões publicadas
**Data:** 2026-10-04  
**Status:** vigente

A V1 adota regra mais conservadora que o mínimo exigido pelo RF-033: a imutabilidade começa no momento da publicação, mesmo antes de a versão ser utilizada em uma aplicação.

Regras:

- novas versões nascem como `RASCUNHO`;
- somente `RASCUNHO` pode ter o número editado;
- `RASCUNHO` pode ser publicado;
- `PUBLICADA` é imutável e pode ser arquivada;
- `ARQUIVADA` permanece imutável;
- alterações após publicação exigem criação de uma nova versão;
- rascunhos só podem ser excluídos quando não possuem seções nem aplicações vinculadas;
- o número da versão é único dentro de cada instrumento.

Essa regra reduz o risco de uma versão publicada mudar silenciosamente e simplifica a preservação histórica.

## D-020 — Edição de seções somente em rascunho
**Data:** 2026-10-04  
**Status:** vigente

As seções fazem parte da estrutura versionada do instrumento e, por isso, só podem ser criadas, editadas ou excluídas enquanto a versão está em `RASCUNHO`.

Regras:

- versões `PUBLICADA` e `ARQUIVADA` exibem seções somente para leitura;
- a seção possui título, descrição opcional, ordem e indicador ativo;
- na criação, a ordem pode ser omitida e será calculada como a próxima posição;
- a ordem pode ser alterada manualmente enquanto a versão estiver em rascunho;
- uma seção com itens vinculados não pode ser excluída fisicamente;
- se já possuir itens, a seção pode ser desativada para preservação da estrutura;
- toda operação valida a relação profissional → instrumento → versão → seção.

## D-021 — Itens e tipos de resposta extensíveis
**Data:** 2026-10-04  
**Status:** vigente

Os itens fazem parte da estrutura versionada e só podem ser alterados enquanto a versão está em `RASCUNHO`.

Regras:

- cada item possui código, texto, tipo de resposta, ordem, permissão de “Não se aplica” e status ativo;
- o código é único dentro da seção;
- a ordem pode ser calculada automaticamente na criação;
- `tipo_resposta` é um identificador textual obrigatório de até 40 caracteres, sem enumeração rígida nesta etapa;
- a ausência de enumeração fechada preserva a possibilidade de novos tipos de resposta no futuro, conforme RF-043;
- a opção “Não se aplica” é configurada individualmente por item;
- versões publicadas e arquivadas exibem itens somente para leitura;
- um item com alternativas ou respostas vinculadas não pode ser excluído fisicamente.

## D-022 — Alternativas preservam rastreabilidade histórica
**Data:** 2026-10-04  
**Status:** vigente

As alternativas de itens fechados seguem as mesmas regras de versionamento da estrutura do instrumento.

Regras:

- alternativa possui valor, rótulo, ordem e status ativo;
- `valor` deve ser único dentro do item;
- a ordem pode ser calculada automaticamente na criação;
- somente versões em `RASCUNHO` permitem criação, edição ou exclusão;
- versões `PUBLICADA` e `ARQUIVADA` mantêm alternativas somente para leitura;
- uma alternativa com respostas vinculadas não pode ser excluída fisicamente;
- a proteção contra exclusão prevalece na aplicação mesmo com a FK de `respostas.alternativa_id` definida como `ON DELETE SET NULL`, para evitar perda de rastreabilidade.

## D-023 — Pessoas preservam histórico por inativação
**Data:** 2026-10-04  
**Status:** vigente

O cadastro de pessoas é administrativo e separado dos snapshots armazenados em avaliações.

Regras:

- cada pessoa pertence ao profissional autenticado;
- estados adotados: `ATIVO` e `INATIVO`;
- nome é obrigatório; e-mail, telefone, data de nascimento e observação administrativa são opcionais;
- alterações no cadastro atual não modificam snapshots de avaliações anteriores;
- a exclusão física é permitida somente quando a pessoa não possui vínculos nem aplicações associadas;
- quando já existir histórico, a pessoa deve ser preservada e pode ser marcada como `INATIVO`;
- todas as consultas e alterações são filtradas por `profissional_id`.

## D-024 — Vínculos preservam os lados A/B
**Data:** 2026-10-04  
**Status:** vigente

O vínculo representa exatamente duas pessoas e preserva uma ordem operacional estável entre lado A e lado B.

Regras:

- `pessoa_a_id` e `pessoa_b_id` são obrigatórios e devem ser diferentes;
- ambas as pessoas devem pertencer ao profissional autenticado;
- os lados A/B são definidos na criação e não são trocados pela edição comum do vínculo;
- tipo de vínculo é textual e flexível;
- quando o tipo for `OUTRO`, a descrição personalizada é obrigatória;
- duração textual é opcional;
- estados adotados: `ATIVO` e `INATIVO`;
- um mesmo vínculo pode ser utilizado em múltiplas aplicações;
- vínculo com aplicações associadas não pode ser excluído fisicamente e deve ser preservado por inativação.

## D-025 — Aplicações usam versão publicada e criam A/B atomicamente
**Data:** 2026-10-04  
**Status:** vigente

Novas aplicações somente podem apontar para versões `PUBLICADA` do instrumento.

A criação de uma aplicação e de seus dois participantes operacionais é atômica:

- aplicação criada com status `RASCUNHO`;
- participante lado `A` criado com status `PENDENTE`;
- participante lado `B` criado com status `PENDENTE`;
- se qualquer inserção falhar, toda a transação é revertida.

O vínculo é opcional na criação. Quando informado, deve estar ativo e pertencer ao profissional autenticado; seus lados A/B, tipo e duração são copiados para a aplicação como base do snapshot.

Sem vínculo prévio, são criados dois slots A/B sem `pessoa_id`, permitindo que a identificação seja completada posteriormente pelo fluxo do participante.

O nome atual da pessoa vinculada pode preencher `nome_snapshot` no momento da criação. `idade_snapshot` e `genero_snapshot` permanecem nulos até o fluxo de identificação do participante.

## D-026 — Autoatendimento público é o fluxo principal
**Data:** 2026-10-04  
**Status:** vigente

O fluxo principal de entrada em uma Avaliação de Percepção Relacional é o autoatendimento público, e não a criação prévia pelo profissional.

Regras:

- o visitante escolhe uma avaliação disponibilizada publicamente;
- informa os participantes A e B, e-mail de contato, tipo do vínculo e duração quando aplicável;
- a aplicação é criada sem exigir autenticação profissional;
- a aplicação é associada automaticamente ao profissional responsável pelo instrumento escolhido;
- o profissional não precisa conhecer previamente os participantes nem liberar individualmente a avaliação;
- a aplicação aparece automaticamente na área profissional para acompanhamento, consulta de resultados e contato posterior;
- o autoatendimento não cria automaticamente registros permanentes em `pessoas` ou `vinculos`;
- no fluxo público, `vinculo_id` pode permanecer nulo e os participantes podem permanecer com `pessoa_id = NULL`, usando os campos snapshot como registro histórico;
- os nomes informados pelo visitante são gravados em `nome_snapshot` nos lados A e B;
- tipo e duração são gravados como snapshot da aplicação;
- somente versões publicadas podem receber novas aplicações públicas;
- a criação manual pela área profissional permanece como modo assistido secundário.

D-025 continua vigente quanto à versão publicada, criação atômica da aplicação e existência obrigatória dos lados A/B; D-026 redefine **quem inicia normalmente a aplicação**.

## D-027 — Termo oficial “Tempo de união”
**Data:** 2026-10-04  
**Status:** vigente

Na interface, documentação funcional e comunicação com usuários, o termo oficial é **“Tempo de união”**.

Não utilizar “duração do vínculo” como rótulo ou texto apresentado ao usuário.

Para preservar a continuidade técnica e evitar migration desnecessária, nomes internos já existentes, como `duracao_texto` e `duracao_vinculo_texto`, podem permanecer no banco e na API enquanto não houver necessidade técnica de renomeação. Essa nomenclatura interna não deve aparecer na interface.

## D-028 — Catálogo público usa instrumento ativo e versão publicada mais recente
**Data:** 2026-10-04  
**Status:** vigente

Uma avaliação pode ser iniciada diretamente pelo visitante quando:

- o instrumento está com status `ATIVO`;
- existe ao menos uma versão `PUBLICADA`;
- o profissional responsável está `ATIVO`.

Quando houver mais de uma versão publicada do mesmo instrumento, o catálogo público mostra apenas uma entrada para a avaliação e utiliza a versão publicada mais recente.

O visitante não vê número de versão na experiência pública; essa informação permanece técnica e histórica.

A criação pública não exige registros prévios em `pessoas` ou `vinculos`. Os nomes dos participantes são armazenados em `nome_snapshot`, o tipo de vínculo e o **tempo de união** são preservados nos campos snapshot da aplicação, e `vinculo_id`/`pessoa_id` podem permanecer nulos.

## D-029 — Confirmação de envio dos acessos somente após envio real
**Data:** 2026-10-04  
**Status:** vigente

Depois que a avaliação pública for iniciada, o sistema deverá gerar dois acessos individuais, um para cada participante, e enviar os respectivos links ao e-mail cadastrado.

A tela de confirmação deverá informar explicitamente que os links de acesso dos participantes foram enviados para o e-mail cadastrado.

Essa mensagem só pode ser exibida após confirmação real de sucesso no envio pelo backend. Se o envio ainda não tiver ocorrido ou falhar, a interface deve apresentar uma mensagem correspondente e nunca afirmar que o e-mail foi enviado.

## D-030 — Acessos individuais por token e SMTP Brevo
**Data:** 2026-10-04  
**Status:** vigente

Cada aplicação pública gera exatamente dois acessos individuais, um para o lado A e outro para o lado B.

Regras:

- token bruto gerado com 32 bytes aleatórios criptograficamente seguros e representado em hexadecimal;
- nunca armazenar o token bruto;
- armazenar apenas `SHA-256(token)` em `acessos_aplicacao.token_hash`;
- cada link aponta para `/avaliacao/acesso/{token}`;
- o endpoint público converte novamente o token em hash para localizar o acesso;
- acesso revogado, concluído ou inválido não pode abrir novo preenchimento;
- `primeiro_acesso_em` e `ultimo_acesso_em` são atualizados quando um link válido é aberto;
- a V1 usa e-mail por **SMTP Brevo**;
- o e-mail de contato recebe os dois links, identificados separadamente como participante A e participante B;
- somente após o SMTP confirmar o envio são preenchidos os campos `enviado_em` e a aplicação passa para `PRONTA`;
- falha de SMTP durante o autoatendimento reverte a criação da aplicação e dos acessos para evitar registro sem entrega dos links;
- credenciais Brevo ficam apenas no arquivo `.env`, nunca no Git.

## D-031 — Identificação inicial antes do questionário
**Data:** 2026-10-04  
**Status:** vigente

O participante precisa concluir sua identificação antes de receber a estrutura do questionário.

Regras:

- nome, idade e gênero são registrados no snapshot do próprio participante da aplicação;
- o nome inicialmente informado pelo visitante pode ser confirmado/corrigido pelo próprio participante;
- idade é obrigatória e validada como número inteiro entre 1 e 120;
- gênero é obrigatório e textual, com até 30 caracteres;
- não será criada uma enumeração de gênero nesta etapa, pois o material-base não define opções fechadas;
- o **tempo de união** é contexto da aplicação, não um campo individual a ser redigitado por A e B;
- ao concluir a identificação, o participante passa para `EM_ANDAMENTO`;
- a aplicação passa de `PRONTA` para `EM_ANDAMENTO` no primeiro início;
- `iniciou_em` é preenchido com `COALESCE` para preservar o primeiro início;
- o questionário só pode ser carregado depois da identificação;
- apenas seções, itens e alternativas ativos são carregados;
- itens excluídos globalmente da aplicação por “Não se aplica” não são retornados.

A persistência das respostas A→A/A→B ou B→B/B→A fica para a subetapa seguinte.

## D-032 — Backend deriva respondente e alvo das respostas
**Data:** 2026-10-04  
**Status:** vigente

No preenchimento individual, o cliente não pode escolher IDs de respondente ou de alvo.

A API deriva o contexto exclusivamente a partir do token individual:

- `SOBRE_MIM` gera resposta com `alvo_id = respondente_id`;
- `SOBRE_OUTRO` gera resposta com `alvo_id` igual ao outro participante da mesma aplicação.

Regras complementares:

- uma resposta é persistida individualmente a cada perspectiva;
- a chave única `aplicacao_id + respondente_id + alvo_id + item_id` garante uma única resposta vigente por contexto;
- nova gravação do mesmo contexto atualiza a resposta anterior;
- alternativas precisam estar ativas e pertencer ao item;
- itens com alternativas ativas exigem alternativa;
- itens sem alternativas aceitam exatamente um valor textual ou numérico;
- a retomada carrega somente respostas do próprio participante;
- respostas do outro participante nunca são devolvidas pelo endpoint individual;
- progresso = perspectivas válidas respondidas / (itens válidos × 2).

A regra “Não se aplica” e a conclusão individual serão tratadas separadamente para manter a validação incremental.

## D-033 — Não se aplica é global e conclusão bloqueia edição
**Data:** 2026-10-04  
**Status:** vigente

A opção **“Não se aplica”** atua sobre a aplicação inteira, não apenas sobre a resposta de um participante.

Regras:

- somente itens com `permite_nao_se_aplica = 1` podem ser excluídos;
- a exclusão é registrada em `aplicacao_itens_excluidos`;
- o primeiro participante que marca fica preservado como autor da exclusão;
- respostas já existentes são mantidas para auditoria;
- o item deixa de ser exibido para os dois participantes;
- o item deixa de compor o denominador de progresso e, futuramente, de comparação;
- na V1 a exclusão é irreversível depois da confirmação, evitando inconsistência com participante já concluído.

A conclusão individual exige 100% das perspectivas ainda válidas respondidas.

Depois da conclusão:

- participante e acesso passam para `CONCLUIDO`;
- o token não permite novas alterações;
- a aplicação só passa para `CONCLUIDA` quando os dois participantes estiverem concluídos.

A conclusão não apaga respostas nem itens excluídos.

## D-034 — Resultado técnico é calculado por sentido e versionado
**Data:** 2026-10-04  
**Status:** parcialmente substituída por D-045

A Etapa 7 calcula dois resultados independentes:

- `A_SOBRE_B`: compara A→B com B→B;
- `B_SOBRE_A`: compara B→A com A→A.

Não haverá consolidação global automática enquanto uma regra específica não for aprovada.

A comparação usa o conteúdo efetivamente persistido:

- mesma `alternativa_id` = coincidência em itens fechados;
- mesmo valor numérico = coincidência em itens numéricos;
- texto igual após `trim` = coincidência em itens textuais;
- ausência de uma resposta ou formato incompatível = não comparável.

Itens marcados como “Não se aplica” não geram comparação e ficam fora do denominador.

A fórmula oficial permanece:

```text
percentual = coincidências / comparações válidas × 100
```

O algoritmo inicial é identificado como `1.0`.

As faixas são vinculadas à versão do instrumento, não codificadas apenas no frontend. Para acomodar percentuais decimais sem lacunas, a interpretação técnica dos intervalos 0–33 / 34–66 / 67–100 é:

- `0,00–33,99` = Ruim;
- `34,00–66,99` = Regular;
- `67,00–100,00` = Bom.

Novas versões do instrumento recebem essas faixas-base automaticamente e podem evoluir futuramente sem alterar resultados históricos já calculados.

Ao concluir o segundo participante, o cálculo ocorre automaticamente e é persistido. Aplicações antigas concluídas podem ser recalculadas explicitamente pela área profissional.

## D-035 — Faixas são editáveis apenas em versões em rascunho
**Data:** 2026-10-04  
**Status:** vigente

As faixas de interpretação são parte da definição versionada do instrumento.

Consequências:

- toda versão recebe faixas-base no momento da criação;
- o profissional pode editar as faixas somente enquanto a versão estiver `RASCUNHO`;
- versões `PUBLICADA` e `ARQUIVADA` são somente leitura também para faixas;
- alterar faixas depois da publicação exige criar uma nova versão;
- uma configuração válida precisa cobrir integralmente 0,00–100,00 sem lacunas nem sobreposições;
- o backend, e não apenas o frontend, valida essa cobertura;
- o código técnico da faixa é preservado quando já existe e pode ser gerado automaticamente para novas faixas;
- o rótulo persistido no resultado serve como snapshot interpretativo do cálculo realizado.

Essa decisão mantém a imutabilidade histórica já adotada para versões publicadas e evita que a mesma aplicação passe a ter interpretação diferente depois de concluída.

## D-036 — Devolutiva é liberada por link seguro enviado pelo Brevo
**Data:** 2026-10-04  
**Status:** parcialmente substituída por D-046

Na V1, o resultado não é enviado como anexo nem incorporado integralmente ao corpo do e-mail.

O profissional prepara uma devolutiva em rascunho e executa uma ação explícita de liberação.

Na liberação:

- um token aleatório exclusivo da devolutiva é gerado;
- somente o hash SHA-256 do token é armazenado;
- um link `/resultado/{token}` é enviado ao e-mail de contato pela infraestrutura SMTP Brevo já validada;
- a devolutiva somente passa para `LIBERADA` depois do envio confirmado;
- depois de liberada, síntese, observações e comentário profissional ficam imutáveis na V1.

A página pública mostra resultados agregados e conteúdo profissional, mas não expõe respostas individuais brutas nem comparação item a item.

Essa separação preserva privacidade, rastreabilidade e o princípio de que comentário profissional não altera o cálculo técnico.

## D-037 — Dashboard é derivado do estado das aplicações
**Data:** 2026-10-04  
**Status:** vigente

O dashboard profissional não terá uma tabela própria de métricas na V1.

Os indicadores são derivados em tempo real de:

- `aplicacoes.status`;
- existência de registros em `resultados`;
- estado de `devolutivas`.

A lista de avaliações recebe filtros server-side e todos os filtros continuam subordinados ao `profissional_id` obtido da autenticação.

A página de detalhe é a visão operacional principal de uma aplicação e deve funcionar tanto para aplicações vinculadas a cadastros administrativos quanto para aplicações criadas pelo autoatendimento público sem `pessoas` ou `vinculos` permanentes.

Essa decisão evita duplicação de estado e mantém o dashboard consistente com os dados transacionais reais.

## D-038 — Reenvio invalida o link anterior
**Data:** 2026-10-04  
**Status:** vigente

Tokens brutos de participante e devolutiva não são armazenados.

Quando o profissional solicita reenvio:

- um novo token criptograficamente aleatório é gerado;
- somente seu SHA-256 substitui o hash anterior;
- o link anterior deixa de funcionar imediatamente;
- o novo link só é considerado reenviado depois da confirmação do SMTP;
- em falha de SMTP, o hash anterior é restaurado;
- participante concluído não pode receber novo acesso de preenchimento.

Essa regra permite reenvio sem manter segredos recuperáveis no banco.

## D-039 — Auditoria não armazena segredos
**Data:** 2026-10-04  
**Status:** vigente

A trilha de auditoria é persistida em `auditoria_eventos`.

Ela registra ator, ação, entidade, contexto não sensível, IP e user agent, mas deve eliminar campos que indiquem senha, token, JWT, autorização ou credencial SMTP.

Eventos de participantes são associados ao `profissional_id` proprietário da aplicação para aparecerem na trilha profissional correspondente.

## D-040 — Rate limit persistido protege login e autoatendimento
**Data:** 2026-10-04  
**Status:** vigente

A V1 usa rate limit persistido em MySQL.

No login profissional:

- limite por IP;
- limite por conta/e-mail;
- depois de atingido o limite, novas tentativas são bloqueadas antes da verificação da senha até o fim da janela.

Na criação pública de avaliação:

- limite por IP;
- limite por e-mail informado.

Os parâmetros ficam no `.env` para ajuste operacional sem alterar código.

## D-041 — Checklist e backup são requisitos de liberação da V1
**Data:** 2026-10-04  
**Status:** vigente

O deploy da V1 não deve ser considerado pronto apenas porque a aplicação inicia.

Antes da liberação:

- executar `composer migrate`;
- executar `composer check-domain`;
- executar `composer check`;
- executar `composer check-v1`;
- gerar backup com `composer backup-db`;
- validar restauração em banco separado;
- executar ao menos um fluxo funcional completo em ambiente de produção/homologação.

O documento operacional oficial é `docs/PRODUCAO_V1.md`.

## D-042 — V1.0.0 entra em congelamento funcional
**Data:** 2026-10-05  
**Status:** vigente

A V1 foi formalmente encerrada em escopo e desenvolvimento funcional com a versão `1.0.0`.

A partir deste marco:

- novas funcionalidades não entram na V1;
- novas regras de negócio não entram na V1;
- novos canais, módulos, papéis ou formas de cálculo ficam para V2;
- correções de bug, segurança, integridade, compatibilidade e deploy continuam permitidas;
- o fechamento da V1 não substitui a homologação final em ambiente real.

As migrations oficiais da V1 são `001` a `008`.

O documento de referência do encerramento é `docs/FECHAMENTO_V1.md`.

A versão `1.0.0` representa **escopo fechado e código funcionalmente completo**, enquanto o estado “em produção” somente será atribuído depois do checklist de homologação e deploy.

## D-043 — Fechamento da V1.0.0 é retratado por requisito obrigatório ausente
**Data:** 2026-10-05  
**Status:** vigente

O fechamento formal realizado anteriormente foi prematuro.

O documento original e `REQUISITOS_SISTEMA.md` exigem um site institucional público configurável pelo profissional por blocos. A home estática existente não satisfazia esse requisito.

Consequências:

- o fechamento de `1.0.0` fica retratado;
- o projeto retorna temporariamente para `1.0.0-rc.1`;
- o site institucional é implementado como correção de escopo da própria V1;
- a migration `009_site_institucional.sql` passa a integrar a V1;
- somente após validar o editor e a renderização pública responsiva poderá ocorrer novo fechamento formal da V1.

O requisito não será deslocado para V2 porque já fazia parte do escopo original.

## D-044 — Imagens institucionais usam upload, não digitação manual de URL
**Data:** 2026-10-05  
**Status:** vigente

A decisão inicial de usar URL manual para fotografia, logotipo e imagens do site foi substituída.

Na interface da V1:

- fotografia do profissional é enviada por upload;
- logotipo é enviado por upload;
- bloco do tipo `IMAGEM` usa upload;
- não é necessário o usuário informar URL para essas imagens;
- vídeo e áudio continuam aceitando URL nesta etapa.

O backend salva os arquivos em:

```text
mapa-relacional-api/public/uploads/site/{profissional_id}/
```

e persiste nas colunas já existentes somente a URL pública gerada pelo sistema. Por isso não foi necessária nova migration.

Regras do upload de imagem:

- formatos permitidos: JPG, PNG e WEBP;
- limite padrão: 5 MB, configurável por `SITE_IMAGE_MAX_MB`;
- o tipo real do arquivo é validado por `fileinfo`, não apenas pela extensão informada pelo navegador;
- nomes de arquivo são aleatórios;
- arquivos enviados não entram no Git;
- a pasta de uploads bloqueia execução de scripts por configuração Apache;
- `composer check-v1` verifica `fileinfo` e permissão real de gravação em `public/uploads`;
- o envio é registrado na auditoria sem armazenar o conteúdo do arquivo.

D-044 substitui especificamente a parte de D-016 que dizia que fotografia e logotipo seriam mantidos apenas por URL manual.

## D-045 — Score geral exige coincidência nos dois sentidos do mesmo item
**Data:** 2026-10-05  
**Status:** vigente

A regra de resultado geral foi definida e substitui a parte de D-034 que mantinha a consolidação global em aberto.

Cada participante continua recebendo um score individual:

```text
Score de A = A→B comparado com B→B
Score de B = B→A comparado com A→A
```

Para cada item válido:

- se A acertar a percepção de B, A recebe um acerto individual;
- se B acertar a percepção de A, B recebe um acerto individual;
- o score geral recebe **1 ponto somente se ambos acertarem naquele mesmo item**.

Formalmente:

```text
acerto_geral_item =
    (A→B = B→B)
    AND
    (B→A = A→A)
```

O percentual geral é:

```text
acertos_gerais / itens_validos × 100
```

Itens excluídos por “Não se aplica” não entram no denominador.

A classificação oficial é:

- Ruim: 0–33;
- Regular: 34–66;
- Bom: 67–100.

Para percentuais com duas casas decimais, os limites técnicos são 0–33,99; 34–66,99; 67–100.

As cores obrigatórias da barra são:

- Ruim: vermelho;
- Regular: amarelo;
- Bom: verde.

O algoritmo de cálculo passa de `1.0` para `2.0`.

O score geral é persistido em `resultados_gerais`, separado dos dois registros direcionais de `resultados`.

## D-046 — Resultado automático por e-mail após conclusão
**Data:** 2026-10-06  
**Status:** vigente

Quando os dois participantes concluírem a **Avaliação Conjugal**, a plataforma deverá calcular os resultados e enviar automaticamente ao e-mail de contato da aplicação um resumo técnico, sem depender da devolutiva preparada pelo profissional.

O e-mail automático contém:

- score percentual do participante A;
- score percentual do participante B;
- score geral da avaliação;
- título interpretativo;
- texto interpretativo definido pela faixa do score geral;
- aviso explícito de que o resultado automático não substitui a devolutiva profissional.

As faixas narrativas da Avaliação Conjugal são independentes das três faixas técnicas `Ruim / Regular / Bom` usadas na barra e no resultado persistido. Para percentuais com casas decimais, os intervalos narrativos são contínuos:

- `80,00–100,00%` — **Uma percepção compartilhada muito positiva**;
- `60,00–79,99%` — **Uma boa compreensão, com espaço para aprofundar o diálogo**;
- `40,00–59,99%` — **Uma oportunidade de se conhecerem melhor**;
- `20,00–39,99%` — **Um convite à redescoberta**;
- `0,00–19,99%` — **Um caminho para construir maior compreensão**.

Os textos completos dessas cinco faixas são parte da comunicação automática da Avaliação Conjugal e devem permanecer exatamente alinhados ao conteúdo aprovado para esse instrumento.

Esta regra **não substitui a devolutiva profissional** definida em D-036. O profissional continua podendo preparar, liberar e enviar posteriormente a devolutiva com síntese, observações, comentário profissional e link seguro.

A afirmação anterior de D-036 de que nenhum resultado seria incorporado ao corpo do e-mail fica parcialmente substituída: o e-mail automático pode conter os **scores agregados e o texto interpretativo automático**, enquanto a devolutiva profissional completa continua protegida por link seguro.

Como os textos aprovados usam linguagem própria de relacionamento conjugal, eles **não devem ser herdados automaticamente por outros instrumentos** da plataforma.

No fluxo de conclusão, se o SMTP falhar no momento do envio obrigatório desse resumo automático, a conclusão final do segundo participante é revertida e a interface informa que a finalização não foi concluída. Isso permite nova tentativa sem registrar a avaliação como concluída sem que o e-mail automático tenha sido entregue.

Nenhuma migration adicional é necessária para esta regra.


## D-047 — Resultado automático inclui PDF completo
**Data:** 2026-10-06  
**Status:** vigente

O e-mail automático definido em D-046 passa a incluir um PDF completo da Avaliação Conjugal, organizado como a tela profissional de resultados.

O PDF contém identificação da avaliação e participantes, versão do instrumento, score geral, texto interpretativo, scores individuais, resultados por seção, comparação item a item, itens Não se aplica, nome do profissional responsável e link para a plataforma.

O corpo do e-mail continua mostrando os scores e o texto correspondente ao score total. O PDF apresenta o detalhamento integral e informa que o resultado automático não substitui a devolutiva profissional.

A geração do PDF ocorre antes do envio SMTP. Se a geração do documento ou o envio falhar, a conclusão final do segundo participante é revertida para permitir nova tentativa.

A geração utiliza dompdf/dompdf no backend. Não é necessária migration de banco.

## D-048 — Bloco institucional de vídeo
**Data:** 2026-10-06  
**Status:** vigente

O site institucional passa a tratar `VIDEO` como bloco visual de primeira classe no editor e na página pública.

Regras:

- o profissional informa uma URL de vídeo;
- o editor apresenta pré-visualização antes de salvar;
- YouTube, YouTube Shorts e Vimeo são incorporados em player responsivo;
- URLs diretas de arquivos de vídeo usam o player HTML5 nativo;
- título, descrição/subtítulo, conteúdo complementar e descrição acessível continuam disponíveis;
- o player público mantém proporção 16:9 e suporte a tela cheia;
- não há upload de vídeo na V1; o bloco continua baseado em URL, conforme D-044.

Nenhuma migration adicional é necessária.

## D-049 — Exclusões destrutivas exigem confirmação e preservam avaliações por padrão
**Data:** 2026-10-06  
**Status:** vigente

Por solicitação explícita durante o estado `1.0.0-rc.1`, a área profissional passa a permitir exclusões físicas de instrumentos, versões e avaliações/aplicações, com proteção contra apagamentos indiretos de histórico.

Esta decisão altera especificamente as regras de exclusão de D-018 e D-019, sem alterar a imutabilidade de **edição** das versões publicadas e arquivadas.

Regras:

- toda exclusão disponível na interface exige confirmação explícita e aviso dos efeitos;
- uma avaliação/aplicação pode ser excluída pelo profissional proprietário, inclusive quando concluída;
- excluir uma avaliação remove participantes/snapshots da aplicação, acessos, respostas, itens “Não se aplica”, comparações, resultados, resultado geral e devolutiva;
- excluir uma avaliação não remove instrumento, versão, pessoas cadastradas nem vínculo administrativo;
- uma versão pode ser excluída em qualquer estado somente quando não possui aplicações vinculadas;
- excluir uma versão remove suas seções, itens, alternativas e faixas de resultado;
- um instrumento pode ser excluído somente quando nenhuma de suas versões possui aplicações vinculadas;
- excluir um instrumento remove todas as suas versões e respectivas estruturas;
- instrumentos e versões **não** apagam avaliações automaticamente; se houver aplicações, a exclusão é bloqueada até que as avaliações que realmente não devam ser preservadas sejam removidas individualmente;
- as exclusões são transacionais e registradas na auditoria.

A regra de imutabilidade continua válida para alterações de conteúdo: versões `PUBLICADA` e `ARQUIVADA` continuam não editáveis.

Como D-043 já retratou o fechamento anterior da V1, esta alteração entra no candidato ainda aberto antes do novo fechamento formal.

## D-050 — Lista de e-mails das avaliações usa contatos únicos e escopo profissional
**Data:** 2026-10-06  
**Status:** vigente

A área profissional de avaliações passa a oferecer uma lista consolidada dos e-mails usados como `email_contato` nas aplicações.

Regras:

- somente aplicações do profissional autenticado entram na consulta;
- os endereços são normalizados para minúsculas e apresentados sem duplicidades;
- para cada endereço, o sistema informa a quantidade de avaliações, a primeira utilização e a utilização mais recente;
- a lista pode ser copiada como texto simples, um endereço por linha;
- a lista pode ser exportada em CSV;
- a geração considera todas as aplicações do profissional e não herda os filtros atuais da listagem de avaliações;
- o evento `LISTA_EMAILS_GERADA` é registrado na auditoria apenas com totais agregados, sem armazenar os endereços no contexto do evento;
- nenhuma migration adicional é necessária, pois a origem dos dados permanece `aplicacoes.email_contato`.

## D-051 — Biblioteca pública de documentos
**Data:** 2026-10-06  
**Status:** vigente

A Plataforma Tânia passa a incluir um módulo de **Biblioteca pública**, independente das avaliações, para disponibilização gratuita de materiais aos visitantes.

Regras aprovadas:

- a biblioteca é acessada publicamente por `/biblioteca`, sem autenticação ou cadastro;
- o profissional gerencia os documentos em `/profissional/biblioteca`;
- cada documento possui título, descrição, arquivo e status;
- formatos permitidos: **PDF** e **PNG**;
- somente documentos com status `ATIVO` aparecem para visitantes;
- o visitante pode pesquisar por título, descrição ou nome do arquivo;
- os arquivos são validados por MIME real com `fileinfo`;
- o limite padrão é 20 MB, configurável por `LIBRARY_FILE_MAX_MB`;
- os arquivos são armazenados em `public/uploads/biblioteca/{profissional_id}/` com nomes aleatórios;
- a área pública do site oferece link para a biblioteca, e o editor institucional passa a aceitar o bloco `BIBLIOTECA`;
- criação, atualização e exclusão são registradas na auditoria;
- a migration oficial é `011_biblioteca_publica.sql`.



## D-052 — Atualizar firebase/php-jwt para 7.x no deploy

**Data:** 2026-10-07  
**Status:** vigente

Durante o primeiro `composer install --no-dev --optimize-autoloader` no servidor de produção, o Composer bloqueou `firebase/php-jwt ^6.10` por advisory de segurança que afeta versões anteriores à 7.0.0.

Decisão:

- atualizar a dependência para `firebase/php-jwt ^7.0`;
- não desabilitar `audit.block-insecure`;
- não adicionar exceção/ignore para o advisory;
- manter o uso atual de HS256 com `JWT_SECRET` de pelo menos 32 caracteres;
- validar novamente `composer check` e o login profissional após a instalação.

A API já usa a interface moderna `JWT::encode(...)` e `JWT::decode(..., new Key(...))`, compatível com a linha 7.x.


## D-053 — Redefinição de senha profissional por e-mail
**Data:** 2026-10-09
**Status:** implementação pendente de homologação

O fluxo vale somente para profissionais, sem alterar tokens dos participantes.
- solicitação pública com mensagem genérica para não revelar existência de contas;
- token aleatório de 32 bytes enviado via SMTP, armazenado como SHA-256;
- validade de 30 minutos, uso único e invalidação de solicitações anteriores;
- limites de taxa por IP e e-mail;
- nova senha protegida por password_hash(), com atualização de senha_alterada_em e invalidação dos JWTs existentes;
- nova migration 012_profissional_recuperacao_senha.sql; sem credenciais SMTP novas;
- não registrar tokens brutos nem senhas na auditoria.

Pendente: testes locais, SMTP e homologação antes de considerar funcionalidade concluída.

## D-054 — Título da aba do site institucional configurável
**Data:** 2026-10-10  
**Status:** vigente; implementação validada funcionalmente pelo usuário

O profissional edita um título HTML próprio pelo editor institucional. O valor pertence à conta profissional, armazenado em `profissionais.site_titulo` (migration 013), não aos blocos de conteúdo. O campo exige 1–160 caracteres. A API pública retorna o título, com fallback "Avaliação de Percepção Relacional"; o frontend atualiza `document.title` ao carregar o site institucional. Somente o profissional autenticado pode gravar o valor. Não confundir com o bloco de tipo TITULO nem modificar a identidade oficial do instrumento.

**Validação em 2026-10-10:** o usuário confirmou que a definição do título do site está funcionando perfeitamente. A funcionalidade fica registrada como concluída no fluxo validado pelo usuário; sem afirmação de testes automatizados nesta atualização.
