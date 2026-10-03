-- Avaliacao de Percepcao Relacional
-- 003_profissional_perfil.sql
-- Campos complementares do perfil profissional.

ALTER TABLE profissionais
    ADD COLUMN descricao TEXT NULL AFTER telefone,
    ADD COLUMN atuacao TEXT NULL AFTER descricao,
    ADD COLUMN foto_url VARCHAR(500) NULL AFTER atuacao,
    ADD COLUMN logo_url VARCHAR(500) NULL AFTER foto_url,
    ADD COLUMN dados_contato TEXT NULL AFTER logo_url;
