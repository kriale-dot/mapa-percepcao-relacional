-- Mapa de Percepcao Relacional
-- 001_base_dominio.sql
-- Base funcional: profissionais, pessoas, vinculos, instrumentos,
-- aplicacoes, dois acessos, exclusao por N/A e respostas.

CREATE TABLE IF NOT EXISTS profissionais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefone VARCHAR(30) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'ATIVO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_profissionais_email (email),
    KEY idx_profissionais_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pessoas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL,
    telefone VARCHAR(30) NULL,
    data_nascimento DATE NULL,
    observacao_administrativa TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'ATIVO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pessoas_profissional (profissional_id),
    KEY idx_pessoas_email (email),
    KEY idx_pessoas_status (status),
    CONSTRAINT fk_pessoas_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vinculos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    pessoa_a_id BIGINT UNSIGNED NOT NULL,
    pessoa_b_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    descricao_tipo VARCHAR(150) NULL,
    duracao_texto VARCHAR(100) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'ATIVO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_vinculos_profissional (profissional_id),
    KEY idx_vinculos_pessoa_a (pessoa_a_id),
    KEY idx_vinculos_pessoa_b (pessoa_b_id),
    CONSTRAINT fk_vinculos_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vinculos_pessoa_a
        FOREIGN KEY (pessoa_a_id) REFERENCES pessoas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vinculos_pessoa_b
        FOREIGN KEY (pessoa_b_id) REFERENCES pessoas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instrumentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'RASCUNHO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_instrumentos_profissional (profissional_id),
    KEY idx_instrumentos_status (status),
    CONSTRAINT fk_instrumentos_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instrumento_versoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrumento_id BIGINT UNSIGNED NOT NULL,
    numero_versao VARCHAR(30) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'RASCUNHO',
    publicado_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_instrumento_versao (instrumento_id, numero_versao),
    KEY idx_instrumento_versoes_status (status),
    CONSTRAINT fk_instrumento_versoes_instrumento
        FOREIGN KEY (instrumento_id) REFERENCES instrumentos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS secoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrumento_versao_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_secoes_versao_ordem (instrumento_versao_id, ordem),
    CONSTRAINT fk_secoes_instrumento_versao
        FOREIGN KEY (instrumento_versao_id) REFERENCES instrumento_versoes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(80) NOT NULL,
    texto TEXT NOT NULL,
    tipo_resposta VARCHAR(40) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    permite_nao_se_aplica TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itens_secao_codigo (secao_id, codigo),
    KEY idx_itens_secao_ordem (secao_id, ordem),
    CONSTRAINT fk_itens_secao
        FOREIGN KEY (secao_id) REFERENCES secoes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alternativas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id BIGINT UNSIGNED NOT NULL,
    valor VARCHAR(100) NOT NULL,
    rotulo VARCHAR(255) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_alternativas_item_valor (item_id, valor),
    KEY idx_alternativas_item_ordem (item_id, ordem),
    CONSTRAINT fk_alternativas_item
        FOREIGN KEY (item_id) REFERENCES itens(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aplicacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    vinculo_id BIGINT UNSIGNED NULL,
    instrumento_versao_id BIGINT UNSIGNED NOT NULL,
    email_contato VARCHAR(190) NOT NULL,
    tipo_vinculo_snapshot VARCHAR(50) NOT NULL,
    duracao_vinculo_texto VARCHAR(100) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'RASCUNHO',
    enviado_em DATETIME NULL,
    iniciada_em DATETIME NULL,
    concluida_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_aplicacoes_profissional (profissional_id),
    KEY idx_aplicacoes_vinculo (vinculo_id),
    KEY idx_aplicacoes_versao (instrumento_versao_id),
    KEY idx_aplicacoes_email (email_contato),
    KEY idx_aplicacoes_status (status),
    CONSTRAINT fk_aplicacoes_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_aplicacoes_vinculo
        FOREIGN KEY (vinculo_id) REFERENCES vinculos(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_aplicacoes_instrumento_versao
        FOREIGN KEY (instrumento_versao_id) REFERENCES instrumento_versoes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aplicacao_participantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    pessoa_id BIGINT UNSIGNED NULL,
    lado CHAR(1) NOT NULL,
    nome_snapshot VARCHAR(150) NULL,
    idade_snapshot SMALLINT UNSIGNED NULL,
    genero_snapshot VARCHAR(30) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
    iniciou_em DATETIME NULL,
    concluiu_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aplicacao_participante_lado (aplicacao_id, lado),
    UNIQUE KEY uq_aplicacao_participante_pessoa (aplicacao_id, pessoa_id),
    KEY idx_aplicacao_participantes_pessoa (pessoa_id),
    KEY idx_aplicacao_participantes_status (status),
    CONSTRAINT fk_aplicacao_participantes_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_aplicacao_participantes_pessoa
        FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_aplicacao_participantes_lado
        CHECK (lado IN ('A', 'B'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acessos_aplicacao (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_participante_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'ATIVO',
    enviado_em DATETIME NULL,
    primeiro_acesso_em DATETIME NULL,
    ultimo_acesso_em DATETIME NULL,
    concluido_em DATETIME NULL,
    revogado_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_acessos_participante (aplicacao_participante_id),
    UNIQUE KEY uq_acessos_token_hash (token_hash),
    KEY idx_acessos_status (status),
    CONSTRAINT fk_acessos_aplicacao_participante
        FOREIGN KEY (aplicacao_participante_id) REFERENCES aplicacao_participantes(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aplicacao_itens_excluidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    marcado_por_participante_id BIGINT UNSIGNED NOT NULL,
    motivo VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aplicacao_item_excluido (aplicacao_id, item_id),
    KEY idx_itens_excluidos_participante (marcado_por_participante_id),
    CONSTRAINT fk_itens_excluidos_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_excluidos_item
        FOREIGN KEY (item_id) REFERENCES itens(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_itens_excluidos_participante
        FOREIGN KEY (marcado_por_participante_id) REFERENCES aplicacao_participantes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS respostas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aplicacao_id BIGINT UNSIGNED NOT NULL,
    respondente_id BIGINT UNSIGNED NOT NULL,
    alvo_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    alternativa_id BIGINT UNSIGNED NULL,
    valor_texto TEXT NULL,
    valor_numero DECIMAL(18,6) NULL,
    nao_se_aplica TINYINT(1) NOT NULL DEFAULT 0,
    respondido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resposta_contexto (aplicacao_id, respondente_id, alvo_id, item_id),
    KEY idx_respostas_aplicacao_item (aplicacao_id, item_id),
    KEY idx_respostas_respondente (respondente_id),
    KEY idx_respostas_alvo (alvo_id),
    KEY idx_respostas_alternativa (alternativa_id),
    CONSTRAINT fk_respostas_aplicacao
        FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_respostas_respondente
        FOREIGN KEY (respondente_id) REFERENCES aplicacao_participantes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_respostas_alvo
        FOREIGN KEY (alvo_id) REFERENCES aplicacao_participantes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_respostas_item
        FOREIGN KEY (item_id) REFERENCES itens(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_respostas_alternativa
        FOREIGN KEY (alternativa_id) REFERENCES alternativas(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
