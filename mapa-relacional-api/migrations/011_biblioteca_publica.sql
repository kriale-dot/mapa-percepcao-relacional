CREATE TABLE IF NOT EXISTS biblioteca_documentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NULL,
    arquivo_url VARCHAR(1000) NOT NULL,
    arquivo_caminho VARCHAR(1000) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    extensao VARCHAR(10) NOT NULL,
    tamanho_bytes BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ATIVO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_biblioteca_profissional_status (
        profissional_id,
        status,
        updated_at,
        id
    ),
    KEY idx_biblioteca_titulo (
        profissional_id,
        titulo
    ),
    CONSTRAINT fk_biblioteca_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_biblioteca_status
        CHECK (status IN ('ATIVO', 'INATIVO')),
    CONSTRAINT chk_biblioteca_extensao
        CHECK (extensao IN ('pdf', 'png'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_blocos (
    profissional_id,
    tipo,
    titulo,
    descricao,
    conteudo,
    link_url,
    link_texto,
    ordem,
    visivel,
    status
)
SELECT
    p.id,
    'BIBLIOTECA',
    'Biblioteca pública',
    'Documentos e materiais disponíveis livremente para consulta.',
    NULL,
    '/biblioteca',
    'Abrir biblioteca',
    40,
    1,
    'ATIVO'
FROM profissionais p
WHERE p.status = 'ATIVO'
  AND NOT EXISTS (
      SELECT 1
      FROM site_blocos sb
      WHERE sb.profissional_id = p.id
        AND sb.tipo = 'BIBLIOTECA'
  );
