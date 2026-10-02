# mapa-relacional-web

Frontend da plataforma **Mapa de Percepção Relacional**.

## Stack

- React 19
- Vite 8
- Tailwind CSS v4
- JavaScript/JSX

## Preparação local

A partir da raiz do repositório:

```powershell
cd mapa-relacional-web
Copy-Item .env.example .env
npm install
npm run dev
```

A aplicação ficará disponível em:

```text
http://localhost:5173
```

A API local deve estar executando em:

```text
http://localhost:8383
```

O arquivo `.env` contém:

```env
VITE_API_URL=http://localhost:8383
```

## Teste de comunicação

Ao abrir a página inicial, o frontend consulta:

```text
GET /api/health
```

Se a comunicação estiver correta, o cabeçalho exibirá:

```text
API conectada
```

## Fundação visual

A página inicial já usa a identidade visual definida no projeto:

- verde escuro `#385048`;
- verde médio `#88B098`;
- verde claro `#A8C8B8`;
- dourado/bege `#D8B078`;
- azul suave `#A8C8D0`;
- fundo claro `#FEFDFB`.

Esta tela é apenas a fundação visual e técnica. O conteúdo institucional definitivo será desenvolvido nas etapas próprias do site.

## Continuidade

Antes de mudanças estruturais, consultar:

- `../docs/MAPA_RELACIONAL_CONTEXT.md`
- `../docs/MAPA_RELACIONAL_STATUS.md`
