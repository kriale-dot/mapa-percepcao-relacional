-- Favicon configuravel pelo profissional para a aba do navegador.
ALTER TABLE profissionais
    ADD COLUMN favicon_url VARCHAR(500) NULL AFTER site_titulo;
