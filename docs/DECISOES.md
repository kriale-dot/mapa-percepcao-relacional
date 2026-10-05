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
**Status:** vigente

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
**Status:** vigente

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
**Status:** vigente

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
