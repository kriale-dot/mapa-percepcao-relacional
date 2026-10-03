-- Avaliacao de Percepcao Relacional
-- 002_profissional_autenticacao.sql
-- Estrutura minima para autenticacao do profissional por e-mail e senha.

ALTER TABLE profissionais
    ADD COLUMN senha_hash VARCHAR(255) NULL AFTER email,
    ADD COLUMN senha_alterada_em DATETIME NULL AFTER status,
    ADD COLUMN ultimo_login_em DATETIME NULL AFTER senha_alterada_em;
