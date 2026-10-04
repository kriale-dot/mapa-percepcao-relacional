CREATE TABLE IF NOT EXISTS auditoria_eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NULL,
    ator_tipo VARCHAR(30) NOT NULL,
    ator_id BIGINT UNSIGNED NULL,
    acao VARCHAR(80) NOT NULL,
    entidade_tipo VARCHAR(80) NULL,
    entidade_id BIGINT UNSIGNED NULL,
    contexto_json JSON NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_profissional_data (profissional_id, created_at),
    KEY idx_auditoria_entidade (entidade_tipo, entidade_id, created_at),
    KEY idx_auditoria_acao_data (acao, created_at),
    CONSTRAINT fk_auditoria_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
