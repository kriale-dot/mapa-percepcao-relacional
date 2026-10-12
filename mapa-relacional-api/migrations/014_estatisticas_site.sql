-- Estatisticas exclusivamente do site institucional (sem eventos de avaliacoes).
CREATE TABLE IF NOT EXISTS site_analytics_config (
  profissional_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  registrar_visitas TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_contador TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_site_analytics_config_profissional FOREIGN KEY (profissional_id)
    REFERENCES profissionais(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_analytics_visitas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profissional_id BIGINT UNSIGNED NOT NULL,
  sessao_hash CHAR(64) NOT NULL,
  pagina VARCHAR(80) NOT NULL,
  origem_dominio VARCHAR(190) NULL,
  dispositivo VARCHAR(12) NOT NULL DEFAULT 'outro',
  pais VARCHAR(100) NULL,
  regiao VARCHAR(120) NULL,
  cidade VARCHAR(120) NULL,
  iniciada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_site_analytics_sessao (profissional_id, sessao_hash),
  KEY idx_site_analytics_periodo (profissional_id, iniciada_em),
  CONSTRAINT fk_site_analytics_visitas_profissional FOREIGN KEY (profissional_id)
    REFERENCES profissionais(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_analytics_paginas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visita_id BIGINT UNSIGNED NOT NULL,
  pagina VARCHAR(80) NOT NULL,
  visualizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_site_analytics_pagina_sessao (visita_id, pagina),
  KEY idx_site_analytics_paginas_visita (visita_id),
  CONSTRAINT fk_site_analytics_paginas_visita FOREIGN KEY (visita_id)
    REFERENCES site_analytics_visitas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
