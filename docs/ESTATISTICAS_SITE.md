# Estatísticas do site institucional — implementação em desenvolvimento

Branch: `feature/estatisticas-site`. Versão-base: `1.0.0-rc.1`.

Escopo: site institucional; **não registrar nem exibir estatísticas de avaliações ou pacientes**.

## Instalação local

1. Atualizar dependências da API (`composer update geoip2/geoip2 --with-all-dependencies`, verificando a resolução antes de aplicar em produção).
2. Providenciar legalmente o banco atualizado `GeoLite2-City.mmdb` da MaxMind e guardá-lo fora do Git em diretório legível pelo PHP.
3. Configurar `SITE_GEOIP_DB=/caminho/seguro/GeoLite2-City.mmdb` no `.env` da API.
4. Executar `composer migrate` para aplicar `014_estatisticas_site.sql`.
5. Executar `composer check` e `npm run build`; validar os endpoints e o comportamento em browser antes do merge.

Se o arquivo GeoIP estiver ausente ou ilegível, o sistema registra visitas com localização desconhecida. Não faz consulta externa automática nem persiste IP bruto. Atualizar o banco GeoLite2 periodicamente.

**Atenção a proxy/CDN:** o controlador usa apenas `REMOTE_ADDR`, não os cabeçalhos `X-Forwarded-For` fornecidos pelo cliente. Quando a API está atrás de proxy, isso pode identificar o proxy e não o visitante. A configuração de proxies confiáveis, preservando a origem sem confiar em cabeçalhos arbitrários, deve ser validada na implantação.

## Rotas

- `POST /api/public/site/visita`: registra sessão única para `/` (e aceita `/biblioteca` no backend, cujo registro no frontend ainda precisa ser integrado).
- `GET /api/public/site/contador`: devolve total somente quando a exibição pública foi habilitada.
- `GET /api/profissional/site/estatisticas?dias=30`: resumo, evolução, dispositivos, origem por domínio e localização agregada.
- `GET/PUT /api/profissional/site/estatisticas/configuracoes`: registro e exibição pública.

A geolocalização é aproximada e pode ser imprecisa por VPN, operadoras móveis, proxies e bases desatualizadas. Exibir valores desconhecidos como "Não identificado". O rastreamento é feito pela homepage; páginas adicionais precisam ser instrumentadas separadamente.

## Validação pendente

- Instalar dependências; análise sintática PHP e build React.
- Migration em MySQL de teste.
- Verificar deduplicação de visitas, configurações, controles de acesso e consultas agregadas.
- Validar geolocalização com IP público em ambiente sem proxy e atrás de proxy confiável.
- Revisar aviso de privacidade, base legal, consentimento quando aplicável e retenção dos registros.
- Checar contagem de sessões entre abas/dispositivos e retenção; não equiparar sessões a pessoas únicas.
- Concluir testes antes de merge para `main` ou deploy.
