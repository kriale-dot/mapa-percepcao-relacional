-- Avaliação Conjugal
-- Seed para a plataforma Avaliação de Percepção Relacional
-- Fonte: material "Avaliação Conjugal" de Tânia Lopes Santiago
--
-- Este script:
-- 1. usa o primeiro profissional ATIVO cadastrado;
-- 2. cria o instrumento "Avaliação Conjugal";
-- 3. cria/publica a versão 1.0;
-- 4. cria uma seção com 15 itens;
-- 5. cria as alternativas Bom / Regular / Fraco em todos os itens;
-- 6. permite "Não se aplica" apenas no item sobre filhos/cuidados;
-- 7. cria 5 faixas interpretativas compatíveis com a contagem original
--    quando os 15 itens são válidos.
--
-- IMPORTANTE:
-- O material original determina que, se o casal não tiver filhos, a última
-- linha seja eliminada e se subtraia 1 dos intervalos de interpretação.
-- A plataforma atual recalcula percentuais pelo número de itens válidos.
-- Portanto, a regra dinâmica dos intervalos para 14 itens não é reproduzida
-- exatamente por estas faixas fixas. Os itens e as alternativas, porém,
-- correspondem ao material original.

SET NAMES utf8mb4;

START TRANSACTION;

-- =========================================================
-- 1. PROFISSIONAL
-- =========================================================

SET @profissional_id := (
    SELECT id
    FROM profissionais
    WHERE status = 'ATIVO'
    ORDER BY id ASC
    LIMIT 1
);

-- =========================================================
-- 2. INSTRUMENTO
-- =========================================================

INSERT INTO instrumentos (
    profissional_id,
    nome,
    descricao,
    status
)
SELECT
    @profissional_id,
    'Avaliação Conjugal',
    'Teste de percepção a ser respondido separadamente pelos participantes. Cada pessoa avalia o que pensa sobre o relacionamento e o que imagina que o outro pensa. Depois, as percepções são comparadas e as respostas iguais contam como acertos. Este não é um teste absoluto e deve ser interpretado dentro da realidade, valores e contexto do relacionamento.',
    'ATIVO'
WHERE @profissional_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM instrumentos
      WHERE profissional_id = @profissional_id
        AND nome = 'Avaliação Conjugal'
  );

SET @instrumento_id := (
    SELECT id
    FROM instrumentos
    WHERE profissional_id = @profissional_id
      AND nome = 'Avaliação Conjugal'
    ORDER BY id ASC
    LIMIT 1
);

UPDATE instrumentos
SET
    descricao = 'Teste de percepção a ser respondido separadamente pelos participantes. Cada pessoa avalia o que pensa sobre o relacionamento e o que imagina que o outro pensa. Depois, as percepções são comparadas e as respostas iguais contam como acertos. Este não é um teste absoluto e deve ser interpretado dentro da realidade, valores e contexto do relacionamento.',
    status = 'ATIVO'
WHERE id = @instrumento_id;

-- =========================================================
-- 3. VERSÃO 1.0
-- =========================================================

INSERT INTO instrumento_versoes (
    instrumento_id,
    numero_versao,
    status,
    publicado_em
)
SELECT
    @instrumento_id,
    '1.0',
    'PUBLICADA',
    NOW()
WHERE @instrumento_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM instrumento_versoes
      WHERE instrumento_id = @instrumento_id
        AND numero_versao = '1.0'
  );

SET @versao_id := (
    SELECT id
    FROM instrumento_versoes
    WHERE instrumento_id = @instrumento_id
      AND numero_versao = '1.0'
    ORDER BY id ASC
    LIMIT 1
);

UPDATE instrumento_versoes
SET
    status = 'PUBLICADA',
    publicado_em = COALESCE(publicado_em, NOW())
WHERE id = @versao_id;

-- =========================================================
-- 4. SEÇÃO
-- =========================================================

INSERT INTO secoes (
    instrumento_versao_id,
    titulo,
    descricao,
    ordem,
    ativo
)
SELECT
    @versao_id,
    'Percepção do relacionamento',
    'Em cada área, responda separadamente: “O que eu penso” e “O que eu imagino que ele/ela pensa”. As respostas disponíveis são Bom, Regular e Fraco.',
    1,
    1
WHERE @versao_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM secoes
      WHERE instrumento_versao_id = @versao_id
        AND ordem = 1
  );

SET @secao_id := (
    SELECT id
    FROM secoes
    WHERE instrumento_versao_id = @versao_id
      AND ordem = 1
    ORDER BY id ASC
    LIMIT 1
);

UPDATE secoes
SET
    titulo = 'Percepção do relacionamento',
    descricao = 'Em cada área, responda separadamente: “O que eu penso” e “O que eu imagino que ele/ela pensa”. As respostas disponíveis são Bom, Regular e Fraco.',
    ativo = 1
WHERE id = @secao_id;

-- =========================================================
-- 5. ITENS
-- =========================================================

INSERT INTO itens (
    secao_id,
    codigo,
    texto,
    tipo_resposta,
    ordem,
    permite_nao_se_aplica,
    ativo
)
VALUES
    (@secao_id, 'COMUNICACAO', 'Comunicação', 'ESCOLHA_UNICA', 1, 0, 1),
    (@secao_id, 'COMPANHEIRISMO', 'Companheirismo', 'ESCOLHA_UNICA', 2, 0, 1),
    (@secao_id, 'RESPEITO_MUTUO', 'Respeito mútuo', 'ESCOLHA_UNICA', 3, 0, 1),
    (@secao_id, 'DIVIDIR_ALEGRIAS', 'Dividir alegrias', 'ESCOLHA_UNICA', 4, 0, 1),
    (@secao_id, 'DIVIDIR_TRISTEZAS', 'Dividir tristezas', 'ESCOLHA_UNICA', 5, 0, 1),
    (@secao_id, 'DIVIDIR_PROBLEMAS', 'Dividir problemas', 'ESCOLHA_UNICA', 6, 0, 1),
    (@secao_id, 'AFINIDADE_IDEIAS', 'Afinidade de idéias', 'ESCOLHA_UNICA', 7, 0, 1),
    (@secao_id, 'AFINIDADE_VALORES_MORAIS', 'Afinidade de valores morais', 'ESCOLHA_UNICA', 8, 0, 1),
    (@secao_id, 'AFINIDADE_RELIGIOSA_ESPIRITUAL', 'Afinidade religiosa/espiritual', 'ESCOLHA_UNICA', 9, 0, 1),
    (@secao_id, 'AFINIDADE_FINANCEIRA_INVESTIMENTOS', 'Afinidade financeira/investimentos', 'ESCOLHA_UNICA', 10, 0, 1),
    (@secao_id, 'AFINIDADE_INTELECTUAL', 'Afinidade intelectual', 'ESCOLHA_UNICA', 11, 0, 1),
    (@secao_id, 'RELACIONAMENTO_SEXUAL', 'Relacionamento sexual', 'ESCOLHA_UNICA', 12, 0, 1),
    (@secao_id, 'QUALIDADE_TEMPO', 'Qualidade de tempo gasto um com o outro', 'ESCOLHA_UNICA', 13, 0, 1),
    (@secao_id, 'DIVISAO_TRABALHO_DOMESTICO', 'Divisão do trabalho doméstico', 'ESCOLHA_UNICA', 14, 0, 1),
    (@secao_id, 'DIVISAO_TRABALHO_FILHOS_CUIDADOS', 'Divisão do trabalho com filhos/cuidados', 'ESCOLHA_UNICA', 15, 1, 1)
ON DUPLICATE KEY UPDATE
    texto = VALUES(texto),
    tipo_resposta = VALUES(tipo_resposta),
    ordem = VALUES(ordem),
    permite_nao_se_aplica = VALUES(permite_nao_se_aplica),
    ativo = VALUES(ativo);

-- =========================================================
-- 6. ALTERNATIVAS
-- =========================================================

INSERT INTO alternativas (
    item_id,
    valor,
    rotulo,
    ordem,
    ativo
)
SELECT
    i.id,
    a.valor,
    a.rotulo,
    a.ordem,
    1
FROM itens i
CROSS JOIN (
    SELECT 'BOM' AS valor, 'Bom' AS rotulo, 1 AS ordem
    UNION ALL
    SELECT 'REGULAR', 'Regular', 2
    UNION ALL
    SELECT 'FRACO', 'Fraco', 3
) a
WHERE i.secao_id = @secao_id
ON DUPLICATE KEY UPDATE
    rotulo = VALUES(rotulo),
    ordem = VALUES(ordem),
    ativo = VALUES(ativo);

-- =========================================================
-- 7. FAIXAS DE RESULTADO
-- =========================================================
--
-- A fonte trabalha com ACERTOS em 15 itens:
--   0-3   -> abaixo de 4
--   4-7   -> descoberta / reinvestimento
--   8-11  -> pontos de atenção
--   12-13 -> boa percepção
--   14-15 -> ótima percepção
--
-- Como a plataforma calcula percentual, os limites abaixo usam pontos
-- intermediários entre as porcentagens possíveis de cada contagem.

INSERT INTO resultado_faixas (
    instrumento_versao_id,
    codigo,
    rotulo,
    minimo,
    maximo,
    ordem
)
VALUES
    (@versao_id, 'MUITO_BAIXA', 'Percepção muito baixa', 0.00, 23.33, 1),
    (@versao_id, 'DESCOBERTA', 'Em fase de descoberta', 23.34, 50.00, 2),
    (@versao_id, 'ATENCAO', 'Percepção com pontos de atenção', 50.01, 76.66, 3),
    (@versao_id, 'BOA', 'Boa percepção', 76.67, 89.99, 4),
    (@versao_id, 'OTIMA', 'Ótima percepção', 90.00, 100.00, 5)
ON DUPLICATE KEY UPDATE
    rotulo = VALUES(rotulo),
    minimo = VALUES(minimo),
    maximo = VALUES(maximo),
    ordem = VALUES(ordem);

COMMIT;

-- =========================================================
-- 8. CONFERÊNCIA
-- =========================================================

SELECT
    @profissional_id AS profissional_id,
    @instrumento_id AS instrumento_id,
    @versao_id AS versao_id,
    @secao_id AS secao_id;

SELECT
    i.nome AS instrumento,
    i.status AS instrumento_status,
    v.numero_versao,
    v.status AS versao_status,
    COUNT(DISTINCT it.id) AS total_itens,
    COUNT(a.id) AS total_alternativas
FROM instrumentos i
INNER JOIN instrumento_versoes v
    ON v.instrumento_id = i.id
INNER JOIN secoes s
    ON s.instrumento_versao_id = v.id
INNER JOIN itens it
    ON it.secao_id = s.id
LEFT JOIN alternativas a
    ON a.item_id = it.id
WHERE i.id = @instrumento_id
  AND v.id = @versao_id
GROUP BY
    i.nome,
    i.status,
    v.numero_versao,
    v.status;

SELECT
    it.ordem,
    it.codigo,
    it.texto,
    it.permite_nao_se_aplica,
    GROUP_CONCAT(
        a.rotulo
        ORDER BY a.ordem
        SEPARATOR ' | '
    ) AS alternativas
FROM itens it
LEFT JOIN alternativas a
    ON a.item_id = it.id
WHERE it.secao_id = @secao_id
GROUP BY
    it.id,
    it.ordem,
    it.codigo,
    it.texto,
    it.permite_nao_se_aplica
ORDER BY it.ordem;
