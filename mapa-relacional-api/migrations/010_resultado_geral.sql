CREATE TABLE IF NOT EXISTS resultados_gerais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    itens_validos INT UNSIGNED NOT NULL DEFAULT 0,
    acertos_gerais INT UNSIGNED NOT NULL DEFAULT 0,
    percentual DECIMAL(5,2) NULL,
    faixa_id BIGINT UNSIGNED NULL,
    faixa VARCHAR(60) NULL,
    algoritmo_versao VARCHAR(30) NOT NULL,
    calculado_em DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resultados_gerais_aplicacao (aplicacao_id),
    KEY idx_resultados_gerais_faixa (faixa_id),
    CONSTRAINT fk_resultados_gerais_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_resultados_gerais_faixa
        FOREIGN KEY (faixa_id) REFERENCES resultado_faixas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_resultados_gerais_percentual
        CHECK (
            percentual IS NULL
            OR (percentual >= 0 AND percentual <= 100)
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
