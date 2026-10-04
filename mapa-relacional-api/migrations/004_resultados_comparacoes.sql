CREATE TABLE IF NOT EXISTS resultado_faixas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrumento_versao_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    rotulo VARCHAR(60) NOT NULL,
    minimo DECIMAL(5,2) NOT NULL,
    maximo DECIMAL(5,2) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resultado_faixas_versao_codigo (instrumento_versao_id, codigo),
    KEY idx_resultado_faixas_versao_ordem (instrumento_versao_id, ordem),
    CONSTRAINT fk_resultado_faixas_versao
        FOREIGN KEY (instrumento_versao_id) REFERENCES instrumento_versoes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_resultado_faixas_limites
        CHECK (
            minimo >= 0
            AND maximo <= 100
            AND minimo <= maximo
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comparacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    sentido VARCHAR(30) NOT NULL,
    resposta_percebida_id BIGINT UNSIGNED NULL,
    resposta_autorreferida_id BIGINT UNSIGNED NULL,
    comparavel TINYINT(1) NOT NULL DEFAULT 0,
    coincide TINYINT(1) NULL,
    motivo_nao_comparavel VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_comparacoes_aplicacao_item_sentido (aplicacao_id, item_id, sentido),
    KEY idx_comparacoes_aplicacao_sentido (aplicacao_id, sentido),
    KEY idx_comparacoes_item (item_id),
    KEY idx_comparacoes_resposta_percebida (resposta_percebida_id),
    KEY idx_comparacoes_resposta_autorreferida (resposta_autorreferida_id),
    CONSTRAINT fk_comparacoes_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comparacoes_item
        FOREIGN KEY (item_id) REFERENCES itens(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comparacoes_resposta_percebida
        FOREIGN KEY (resposta_percebida_id) REFERENCES respostas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_comparacoes_resposta_autorreferida
        FOREIGN KEY (resposta_autorreferida_id) REFERENCES respostas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_comparacoes_sentido
        CHECK (sentido IN ('A_SOBRE_B', 'B_SOBRE_A')),
    CONSTRAINT chk_comparacoes_coincide
        CHECK (coincide IS NULL OR coincide IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resultados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    sentido VARCHAR(30) NOT NULL,
    comparacoes_validas INT UNSIGNED NOT NULL DEFAULT 0,
    coincidencias INT UNSIGNED NOT NULL DEFAULT 0,
    percentual DECIMAL(5,2) NULL,
    faixa_id BIGINT UNSIGNED NULL,
    faixa VARCHAR(60) NULL,
    algoritmo_versao VARCHAR(30) NOT NULL,
    calculado_em DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resultados_aplicacao_sentido (aplicacao_id, sentido),
    KEY idx_resultados_faixa (faixa_id),
    CONSTRAINT fk_resultados_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_resultados_faixa
        FOREIGN KEY (faixa_id) REFERENCES resultado_faixas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_resultados_sentido
        CHECK (sentido IN ('A_SOBRE_B', 'B_SOBRE_A')),
    CONSTRAINT chk_resultados_percentual
        CHECK (percentual IS NULL OR (percentual >= 0 AND percentual <= 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO resultado_faixas (
    instrumento_versao_id,
    codigo,
    rotulo,
    minimo,
    maximo,
    ordem
)
SELECT
    v.id,
    faixa.codigo,
    faixa.rotulo,
    faixa.minimo,
    faixa.maximo,
    faixa.ordem
FROM instrumento_versoes v
CROSS JOIN (
    SELECT 'RUIM' AS codigo, 'Ruim' AS rotulo, 0.00 AS minimo, 33.00 AS maximo, 1 AS ordem
    UNION ALL
    SELECT 'REGULAR', 'Regular', 34.00, 66.00, 2
    UNION ALL
    SELECT 'BOM', 'Bom', 67.00, 100.00, 3
) faixa
LEFT JOIN resultado_faixas existente
  ON existente.instrumento_versao_id = v.id
 AND existente.codigo = faixa.codigo
WHERE existente.id IS NULL;
