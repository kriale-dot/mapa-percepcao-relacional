# Mapa de Percepção Relacional

Plataforma digital para percepção mútua e conhecimento interpessoal.

O sistema compara, de forma estruturada, como duas pessoas percebem a si mesmas, a outra pessoa e a relação entre elas. O uso não é limitado a casais: pode abranger pais e filhos, amigos, familiares, relações profissionais e outros vínculos definidos pelo profissional.

## Estrutura do projeto

```text
mapa-percepcao-relacional/
├── mapa-relacional-api/
├── mapa-relacional-web/
├── docs/
│   ├── MAPA_RELACIONAL_CONTEXT.md
│   └── MAPA_RELACIONAL_STATUS.md
├── deploy/
├── README.md
└── .gitignore
```

## Documentos prioritários

Antes de qualquer nova etapa de desenvolvimento, consultar:

1. `docs/MAPA_RELACIONAL_CONTEXT.md` — decisões permanentes, arquitetura, regras e identidade do produto.
2. `docs/MAPA_RELACIONAL_STATUS.md` — etapa atual, o que foi concluído, pendências e próximo passo.

## Stack planejada

**Backend:** PHP 8.2+, Slim 4, MySQL 8, PDO, JWT, PHP dotenv e Monolog.

**Frontend:** React, Vite, JavaScript/JSX e Tailwind CSS v4.

## Regra de continuidade com ChatGPT

Em uma nova conversa, usar:

```text
Acesse o repositório kriale-dot/mapa-percepcao-relacional no GitHub.

Leia primeiro:
- docs/MAPA_RELACIONAL_CONTEXT.md
- docs/MAPA_RELACIONAL_STATUS.md

Depois consulte somente os arquivos necessários para a tarefa atual.
Não altere decisões estruturais registradas no CONTEXT sem me avisar.
Ao concluir uma etapa relevante, atualize o STATUS.
```

## Estado atual

Consulte `docs/MAPA_RELACIONAL_STATUS.md`.
