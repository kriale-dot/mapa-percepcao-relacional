-- Recuperacao de senha profissional: tokens de uso unico com expiracao.
CREATE TABLE IF NOT EXISTS profissional_recuperacoes_senha (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    utilizado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recuperacao_token_hash (token_hash),
    KEY idx_recuperacao_profissional (profissional_id, expira_em),
    CONSTRAINT fk_recuperacao_profissional
      FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
