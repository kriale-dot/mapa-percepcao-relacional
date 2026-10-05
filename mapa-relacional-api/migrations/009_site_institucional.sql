CREATE TABLE IF NOT EXISTS site_blocos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    titulo VARCHAR(200) NULL,
    descricao TEXT NULL,
    conteudo TEXT NULL,
    midia_url VARCHAR(1000) NULL,
    texto_alternativo VARCHAR(255) NULL,
    link_url VARCHAR(1000) NULL,
    link_texto VARCHAR(120) NULL,
    ordem INT NOT NULL DEFAULT 0,
    visivel TINYINT(1) NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'ATIVO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_site_blocos_profissional_ordem (
        profissional_id,
        status,
        visivel,
        ordem,
        id
    ),
    CONSTRAINT fk_site_blocos_profissional
        FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_site_blocos_status
        CHECK (status IN ('ATIVO', 'INATIVO')),
    CONSTRAINT chk_site_blocos_visivel
        CHECK (visivel IN (0, 1))
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
    'PERFIL',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    10,
    1,
    'ATIVO'
FROM profissionais p
WHERE p.status = 'ATIVO'
  AND NOT EXISTS (
      SELECT 1
      FROM site_blocos sb
      WHERE sb.profissional_id = p.id
  );

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
    'TEXTO',
    'Avaliação de Percepção Relacional',
    'Instrumento de percepção mútua e conhecimento interpessoal.',
    'Uma experiência estruturada para comparar percepções e apoiar conversas mais conscientes entre pessoas que compartilham um vínculo.',
    NULL,
    NULL,
    20,
    1,
    'ATIVO'
FROM profissionais p
WHERE p.status = 'ATIVO'
  AND EXISTS (
      SELECT 1
      FROM site_blocos sb
      WHERE sb.profissional_id = p.id
        AND sb.tipo = 'PERFIL'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM site_blocos sb
      WHERE sb.profissional_id = p.id
        AND sb.tipo = 'TEXTO'
        AND sb.ordem = 20
  );

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
    'AVALIACAO',
    'Avaliações disponíveis',
    'Escolha uma avaliação e inicie o processo de forma autônoma.',
    NULL,
    '/avaliacoes',
    'Ver avaliações',
    30,
    1,
    'ATIVO'
FROM profissionais p
WHERE p.status = 'ATIVO'
  AND NOT EXISTS (
      SELECT 1
      FROM site_blocos sb
      WHERE sb.profissional_id = p.id
        AND sb.tipo = 'AVALIACAO'
  );
