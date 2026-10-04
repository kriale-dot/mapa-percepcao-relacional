CREATE TABLE IF NOT EXISTS rate_limites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escopo VARCHAR(60) NOT NULL,
    chave_hash CHAR(64) NOT NULL,
    contador INT UNSIGNED NOT NULL DEFAULT 1,
    janela_expira_em DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rate_limites_escopo_chave (escopo, chave_hash),
    KEY idx_rate_limites_expira (janela_expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
