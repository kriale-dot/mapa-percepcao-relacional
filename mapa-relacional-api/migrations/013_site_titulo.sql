-- Titulo da aba do navegador configuravel pelo profissional.
ALTER TABLE profissionais ADD COLUMN site_titulo VARCHAR(160) NULL AFTER dados_contato;
