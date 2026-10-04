CREATE TABLE IF NOT EXISTS devolutivas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    sintese TEXT NULL,
    observacoes TEXT NULL,
    comentario_profissional TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'RASCUNHO',
    token_hash CHAR(64) NULL,
    liberada_em DATETIME NULL,
    enviado_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_devolutivas_aplicacao (aplicacao_id),
    UNIQUE KEY uq_devolutivas_token_hash (token_hash),
    KEY idx_devolutivas_profissional_status (profissional_id, status),
    CONSTRAINT fk_devolutivas_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_devolutivas_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_devolutivas_status
        CHECK (status IN ('RASCUNHO', 'LIBERADA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
