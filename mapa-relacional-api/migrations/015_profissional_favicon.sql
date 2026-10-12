-- Ícone configurável da aba do navegador (favicon) por profissional.
ALTER TABLE profissionais ADD COLUMN favicon_url VARCHAR(500) NULL AFTER site_titulo;
