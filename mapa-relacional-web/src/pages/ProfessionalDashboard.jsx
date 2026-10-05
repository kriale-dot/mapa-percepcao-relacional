import { useEffect, useState } from 'react'
import {
  getAuthenticatedProfessional,
  getProfessionalDashboard,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function statusLabel(status) {
  const labels = {
    RASCUNHO: 'Rascunho',
    PRONTA: 'Pronta',
    EM_ANDAMENTO: 'Em andamento',
    CONCLUIDA: 'Concluída',
    CANCELADA: 'Cancelada',
  }

  return labels[status] || status
}

export default function ProfessionalDashboard() {
  const [professional, setProfessional] = useState(null)
  const [summary, setSummary] = useState(null)
  const [recent, setRecent] = useState([])
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load()
  }, [])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const [professionalResult, dashboardResult] = await Promise.all([
        getAuthenticatedProfessional(),
        getProfessionalDashboard(),
      ])

      setProfessional(professionalResult.profissional)
      setSummary(dashboardResult.resumo)
      setRecent(dashboardResult.recentes || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar o painel profissional.',
      )
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] text-[#385048]">
        Carregando painel profissional...
      </div>
    )
  }

  if (status === 'error') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-xl rounded-3xl border border-[#D8B078]/45 bg-white p-8">
          <h1 className="text-2xl font-semibold">Painel indisponível</h1>
          <p className="mt-4 text-sm leading-6 text-[#385048]/70">
            {message}
          </p>
          <button
            type="button"
            onClick={load}
            className="mt-6 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
          >
            Tentar novamente
          </button>
        </div>
      </div>
    )
  }

  const metrics = [
    ['Total', summary?.total ?? 0],
    ['Prontas', summary?.pronta ?? 0],
    ['Em andamento', summary?.em_andamento ?? 0],
    ['Concluídas', summary?.concluida ?? 0],
    ['Resultados disponíveis', summary?.resultados_disponiveis ?? 0],
    ['Devolutivas liberadas', summary?.devolutivas_liberadas ?? 0],
  ]

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Área profissional</p>
            <p className="text-xs text-[#385048]/65">
              Avaliação de Percepção Relacional
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => navigate('/profissional/perfil')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Meu perfil
            </button>
            <button
              type="button"
              onClick={handleLogout}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Sair
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Visão geral
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            Olá, {professional?.nome}.
          </h1>
          <p className="mt-3 text-[#385048]/70">
            Acompanhe o andamento das avaliações e acesse rapidamente as
            atividades que precisam de atenção.
          </p>

          <div className="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {metrics.map(([label, value]) => (
              <div
                key={label}
                className="rounded-2xl bg-[#A8C8B8]/12 p-5"
              >
                <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                  {label}
                </p>
                <p className="mt-2 text-3xl font-semibold">{value}</p>
              </div>
            ))}
          </div>

          {(summary?.devolutivas_rascunho ?? 0) > 0 ? (
            <div className="mt-5 rounded-2xl bg-[#D8B078]/15 p-5 text-sm">
              Há {summary.devolutivas_rascunho} devolutiva(s) em rascunho
              aguardando revisão ou liberação.
            </div>
          ) : null}
        </section>

        <section className="mt-7 grid gap-4 md:grid-cols-3">
          {[
            {
              title: 'Avaliações',
              description:
                'Acompanhe aplicações, participantes, resultados e devolutivas.',
              path: '/profissional/avaliacoes',
            },
            {
              title: 'Site institucional',
              description:
                'Monte a página pública com textos, imagens, vídeos, links e chamadas para ação.',
              path: '/profissional/site',
            },
            {
              title: 'Instrumentos',
              description:
                'Gerencie instrumentos, versões, seções, itens e faixas.',
              path: '/profissional/instrumentos',
            },
            {
              title: 'Pessoas',
              description:
                'Consulte e mantenha os cadastros administrativos.',
              path: '/profissional/pessoas',
            },
            {
              title: 'Vínculos',
              description:
                'Gerencie vínculos administrativos entre duas pessoas.',
              path: '/profissional/vinculos',
            },
            {
              title: 'Meu perfil',
              description:
                'Atualize informações profissionais e dados públicos.',
              path: '/profissional/perfil',
            },
            {
              title: 'Senha',
              description:
                'Altere com segurança sua senha de acesso profissional.',
              path: '/profissional/senha',
            },
            {
              title: 'Auditoria',
              description:
                'Consulte ações relevantes registradas para rastreabilidade.',
              path: '/profissional/auditoria',
            },
          ].map((item) => (
            <button
              key={item.title}
              type="button"
              onClick={() => navigate(item.path)}
              className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6 text-left transition hover:-translate-y-0.5 hover:shadow-sm"
            >
              <p className="font-semibold">{item.title}</p>
              <p className="mt-2 text-sm leading-6 text-[#385048]/65">
                {item.description}
              </p>
            </button>
          ))}
        </section>

        <section className="mt-8">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                Atividade recente
              </p>
              <h2 className="mt-2 text-2xl font-semibold">
                Avaliações recentes
              </h2>
            </div>

            <button
              type="button"
              onClick={() => navigate('/profissional/avaliacoes')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Ver todas
            </button>
          </div>

          <div className="mt-5 grid gap-4 lg:grid-cols-2">
            {recent.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-7 text-sm text-[#385048]/65">
                Nenhuma avaliação registrada ainda.
              </div>
            ) : (
              recent.map((application) => (
                <article
                  key={application.id}
                  className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
                >
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                      {statusLabel(application.status)}
                    </span>
                    {application.devolutiva_status ? (
                      <span className="rounded-full bg-[#D8B078]/16 px-3 py-1 text-xs font-semibold">
                        Devolutiva {application.devolutiva_status.toLowerCase()}
                      </span>
                    ) : null}
                  </div>

                  <h3 className="mt-3 font-semibold">
                    {application.instrumento_nome}
                  </h3>
                  <p className="mt-2 text-sm text-[#385048]/65">
                    {application.participante_a?.nome_snapshot ||
                      'Participante A'}
                    {' ↔ '}
                    {application.participante_b?.nome_snapshot ||
                      'Participante B'}
                  </p>

                  <div className="mt-5 flex flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() =>
                        navigate(
                          `/profissional/avaliacoes/${application.id}`,
                        )
                      }
                      className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                    >
                      Ver detalhes
                    </button>

                    {application.status === 'CONCLUIDA' ? (
                      <button
                        type="button"
                        onClick={() =>
                          navigate(
                            `/profissional/avaliacoes/${application.id}/resultados`,
                          )
                        }
                        className="rounded-xl bg-[#385048] px-4 py-2 text-sm font-semibold text-white"
                      >
                        Ver resultados
                      </button>
                    ) : null}
                  </div>
                </article>
              ))
            )}
          </div>
        </section>
      </main>
    </div>
  )
}
