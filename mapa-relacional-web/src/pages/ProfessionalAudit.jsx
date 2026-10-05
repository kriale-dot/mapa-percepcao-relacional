import { useEffect, useState } from 'react'
import { listAuditEvents } from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function formatDate(value) {
  if (!value) return '—'

  const date = new Date(value.replace(' ', 'T'))

  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date)
}

function actionLabel(action) {
  const labels = {
    LOGIN_SUCESSO: 'Login realizado',
    SENHA_ALTERADA: 'Senha alterada',
    PERFIL_ATUALIZADO: 'Perfil atualizado',
    VERSAO_CRIADA: 'Versão criada',
    VERSAO_PUBLICADA: 'Versão publicada',
    VERSAO_ARQUIVADA: 'Versão arquivada',
    DEVOLUTIVA_RASCUNHO_SALVA: 'Devolutiva salva',
    DEVOLUTIVA_LIBERADA: 'Devolutiva liberada',
    DEVOLUTIVA_REENVIADA: 'Devolutiva reenviada',
    ACESSO_PARTICIPANTE_REENVIADO: 'Acesso de participante reenviado',
    SITE_BLOCO_CRIADO: 'Bloco do site criado',
    SITE_BLOCO_ATUALIZADO: 'Bloco do site atualizado',
    SITE_BLOCO_EXCLUIDO: 'Bloco do site excluído',
    SITE_BLOCO_REORDENADO: 'Bloco do site reordenado',
    SITE_IMAGEM_ENVIADA: 'Imagem do site enviada',
  }

  return labels[action] || action
}

export default function ProfessionalAudit() {
  const [events, setEvents] = useState([])
  const [filters, setFilters] = useState({
    acao: '',
    entidade_tipo: '',
    limite: 100,
  })
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load(filters)
  }, [])

  async function load(activeFilters) {
    setStatus('loading')
    setMessage('')

    try {
      const result = await listAuditEvents(activeFilters)
      setEvents(result.eventos || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar a auditoria.',
      )
    }
  }

  function updateFilter(field, value) {
    setFilters((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function handleSubmit(event) {
    event.preventDefault()
    load(filters)
  }

  function handleReset() {
    const empty = {
      acao: '',
      entidade_tipo: '',
      limite: 100,
    }

    setFilters(empty)
    load(empty)
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Auditoria</p>
            <p className="text-xs text-[#385048]/65">
              Histórico de ações relevantes da área profissional
            </p>
          </div>

          <button
            type="button"
            onClick={() => navigate('/profissional')}
            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
          >
            Voltar
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Rastreabilidade
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            Eventos registrados
          </h1>
          <p className="mt-4 max-w-3xl text-sm leading-7 text-[#385048]/70">
            A trilha registra ações relevantes sem armazenar senhas, tokens,
            JWT ou credenciais SMTP.
          </p>

          <form
            className="mt-6 grid gap-3 md:grid-cols-[1fr_1fr_auto_auto]"
            onSubmit={handleSubmit}
          >
            <input
              type="text"
              placeholder="Ação exata, ex.: DEVOLUTIVA_LIBERADA"
              value={filters.acao}
              onChange={(event) =>
                updateFilter('acao', event.target.value)
              }
              className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
            />

            <input
              type="text"
              placeholder="Entidade, ex.: APLICACAO"
              value={filters.entidade_tipo}
              onChange={(event) =>
                updateFilter('entidade_tipo', event.target.value)
              }
              className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
            />

            <button
              type="submit"
              className="rounded-xl bg-[#385048] px-4 py-3 text-sm font-semibold text-white"
            >
              Filtrar
            </button>

            <button
              type="button"
              onClick={handleReset}
              className="rounded-xl border border-[#385048]/20 px-4 py-3 text-sm font-semibold"
            >
              Limpar
            </button>
          </form>
        </section>

        {status === 'loading' ? (
          <div className="mt-6 rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
            Carregando eventos...
          </div>
        ) : null}

        {status === 'error' ? (
          <div className="mt-6 rounded-2xl border border-[#D8B078]/45 bg-white p-6">
            {message}
          </div>
        ) : null}

        {status === 'ready' && events.length === 0 ? (
          <div className="mt-6 rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
            Nenhum evento encontrado com os filtros atuais.
          </div>
        ) : null}

        <div className="mt-6 space-y-4">
          {events.map((event) => (
            <article
              key={event.id}
              className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
            >
              <div className="flex flex-wrap items-center gap-2">
                <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                  {actionLabel(event.acao)}
                </span>
                {event.entidade_tipo ? (
                  <span className="rounded-full bg-[#A8C8D0]/22 px-3 py-1 text-xs font-semibold">
                    {event.entidade_tipo}
                    {event.entidade_id
                      ? ` #${event.entidade_id}`
                      : ''}
                  </span>
                ) : null}
              </div>

              <p className="mt-3 text-sm text-[#385048]/65">
                {formatDate(event.created_at)} · ator {event.ator_tipo}
                {event.ator_id ? ` #${event.ator_id}` : ''}
              </p>

              {event.contexto &&
              Object.keys(event.contexto).length > 0 ? (
                <pre className="mt-4 overflow-x-auto rounded-xl bg-[#FEFDFB] p-4 text-xs leading-5 text-[#385048]/75">
                  {JSON.stringify(event.contexto, null, 2)}
                </pre>
              ) : null}

              {event.ip || event.user_agent ? (
                <details className="mt-4 text-xs text-[#385048]/55">
                  <summary className="cursor-pointer font-semibold">
                    Contexto técnico
                  </summary>
                  <div className="mt-2 space-y-1">
                    <p>IP: {event.ip || '—'}</p>
                    <p className="break-all">
                      User agent: {event.user_agent || '—'}
                    </p>
                  </div>
                </details>
              ) : null}
            </article>
          ))}
        </div>
      </main>
    </div>
  )
}
