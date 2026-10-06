import { useEffect, useMemo, useState } from 'react'
import {
  deleteApplication,
  getApplication,
  resendApplicationFeedback,
  resendParticipantAccess,
} from '../services/api'
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
    timeStyle: 'short',
  }).format(date)
}

function statusLabel(status) {
  const labels = {
    RASCUNHO: 'Rascunho',
    PRONTA: 'Pronta',
    EM_ANDAMENTO: 'Em andamento',
    CONCLUIDA: 'Concluída',
    CANCELADA: 'Cancelada',
    PENDENTE: 'Pendente',
    CONCLUIDO: 'Concluído',
  }

  return labels[status] || status || '—'
}

export default function ProfessionalApplicationDetail({ applicationId }) {
  const [application, setApplication] = useState(null)
  const [excludedItems, setExcludedItems] = useState([])
  const [results, setResults] = useState([])
  const [generalResult, setGeneralResult] = useState(null)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [actionStatus, setActionStatus] = useState('')
  const [actionMessage, setActionMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load()
  }, [applicationId])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const response = await getApplication(applicationId)
      setApplication(response.aplicacao)
      setExcludedItems(response.itens_excluidos || [])
      setResults(response.resultados || [])
      setGeneralResult(response.resultado_geral || null)
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar a avaliação.',
      )
    }
  }

  async function handleResendParticipant(side) {
    const participant =
      side === 'A'
        ? application?.participante_a
        : application?.participante_b

    const confirmed = window.confirm(
      `Gerar um novo link para o participante ${side} (${participant?.nome_snapshot || 'participante'})? O link anterior deixará de funcionar.`,
    )

    if (!confirmed) return

    setActionStatus(`participant-${side}`)
    setActionMessage('')

    try {
      const result = await resendParticipantAccess(
        applicationId,
        side,
      )
      setActionMessage(result.message)
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setActionMessage(
        error.message || 'Não foi possível reenviar o acesso.',
      )
    } finally {
      setActionStatus('')
    }
  }

  async function handleResendFeedback() {
    const confirmed = window.confirm(
      'Gerar um novo link da devolutiva e reenviar por e-mail? O link anterior deixará de funcionar.',
    )

    if (!confirmed) return

    setActionStatus('feedback')
    setActionMessage('')

    try {
      const result = await resendApplicationFeedback(applicationId)
      setActionMessage(result.message)
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setActionMessage(
        error.message || 'Não foi possível reenviar a devolutiva.',
      )
    } finally {
      setActionStatus('')
    }
  }

  async function handleDeleteEvaluation() {
    const isCompleted = application?.status === 'CONCLUIDA'
    const label = isCompleted ? 'avaliação concluída' : 'avaliação'

    const confirmed = window.confirm(
      `Excluir permanentemente esta ${label}?\n\nSerão apagados os participantes desta aplicação, links de acesso, respostas, itens marcados como "Não se aplica", comparações, resultados e eventual devolutiva profissional.\n\nO instrumento, a versão, as pessoas cadastradas e o vínculo não serão excluídos.\n\nEsta ação não pode ser desfeita.`,
    )

    if (!confirmed) return

    setActionStatus('delete')
    setActionMessage('')

    try {
      await deleteApplication(applicationId)
      navigate('/profissional/avaliacoes')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setActionMessage(
        error.message || 'Não foi possível excluir a avaliação.',
      )
      setActionStatus('')
    }
  }

  const resultsByDirection = useMemo(() => {
    const map = {}
    results.forEach((result) => {
      map[result.sentido] = result
    })
    return map
  }, [results])

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] p-8 text-[#385048]">
        Carregando detalhes da avaliação...
      </div>
    )
  }

  if (status === 'error' || !application) {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#D8B078]/45 bg-white p-8">
          <h1 className="text-2xl font-semibold">Avaliação indisponível</h1>
          <p className="mt-4 text-sm text-[#385048]/70">{message}</p>
          <button
            type="button"
            onClick={() => navigate('/profissional/avaliacoes')}
            className="mt-6 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
          >
            Voltar às avaliações
          </button>
        </div>
      </div>
    )
  }

  const participantCards = [
    ['A', application.participante_a],
    ['B', application.participante_b],
  ]

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Detalhe da avaliação</p>
            <p className="text-xs text-[#385048]/65">
              Aplicação #{application.id}
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
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
            <button
              type="button"
              disabled={actionStatus !== ''}
              onClick={handleDeleteEvaluation}
              className="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 disabled:opacity-50"
            >
              {actionStatus === 'delete'
                ? 'Excluindo...'
                : 'Excluir avaliação'}
            </button>
            <button
              type="button"
              onClick={() => navigate('/profissional/avaliacoes')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Voltar
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <div className="flex flex-wrap items-center gap-2">
            <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
              {statusLabel(application.status)}
            </span>
            <span className="rounded-full bg-[#A8C8D0]/22 px-3 py-1 text-xs font-semibold">
              {application.tipo_vinculo_snapshot}
            </span>
            {application.devolutiva_status ? (
              <span className="rounded-full bg-[#D8B078]/16 px-3 py-1 text-xs font-semibold">
                Devolutiva {application.devolutiva_status.toLowerCase()}
              </span>
            ) : null}
          </div>

          <h1 className="mt-4 text-3xl font-semibold">
            {application.instrumento_nome}
          </h1>
          <p className="mt-2 text-sm text-[#385048]/65">
            Versão {application.numero_versao}
          </p>

          <div className="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                E-mail
              </p>
              <p className="mt-2 break-all text-sm font-medium">
                {application.email_contato}
              </p>
            </div>
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Tempo de união
              </p>
              <p className="mt-2 text-sm font-medium">
                {application.duracao_vinculo_texto || '—'}
              </p>
            </div>
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Criada em
              </p>
              <p className="mt-2 text-sm font-medium">
                {formatDate(application.created_at)}
              </p>
            </div>
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Concluída em
              </p>
              <p className="mt-2 text-sm font-medium">
                {formatDate(application.concluida_em)}
              </p>
            </div>
          </div>

          {actionMessage ? (
            <div className="mt-5 rounded-2xl bg-[#A8C8D0]/14 px-5 py-4 text-sm">
              {actionMessage}
            </div>
          ) : null}

          {application.devolutiva_status === 'LIBERADA' ? (
            <button
              type="button"
              disabled={actionStatus !== ''}
              onClick={handleResendFeedback}
              className="mt-5 rounded-xl border border-[#D8B078]/65 px-4 py-2 text-sm font-semibold disabled:opacity-50"
            >
              {actionStatus === 'feedback'
                ? 'Reenviando devolutiva...'
                : 'Reenviar devolutiva por e-mail'}
            </button>
          ) : null}
        </section>

        <section className="mt-7">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Participantes
          </p>
          <div className="mt-4 grid gap-5 md:grid-cols-2">
            {participantCards.map(([side, participant]) => (
              <article
                key={side}
                className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex items-center justify-between gap-3">
                  <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                    Participante {side}
                  </p>
                  <span className="rounded-full bg-[#A8C8B8]/18 px-3 py-1 text-xs font-semibold">
                    {statusLabel(participant?.status)}
                  </span>
                </div>

                <h2 className="mt-3 text-xl font-semibold">
                  {participant?.nome_snapshot || 'Identificação pendente'}
                </h2>

                <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                  <div>
                    <dt className="text-[#385048]/55">Idade</dt>
                    <dd className="mt-1 font-medium">
                      {participant?.idade_snapshot ?? '—'}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[#385048]/55">Gênero</dt>
                    <dd className="mt-1 font-medium">
                      {participant?.genero_snapshot || '—'}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[#385048]/55">Iniciou</dt>
                    <dd className="mt-1 font-medium">
                      {formatDate(participant?.iniciou_em)}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[#385048]/55">Concluiu</dt>
                    <dd className="mt-1 font-medium">
                      {formatDate(participant?.concluiu_em)}
                    </dd>
                  </div>
                </dl>

                {participant?.status !== 'CONCLUIDO' &&
                !['CONCLUIDA', 'CANCELADA'].includes(
                  application.status,
                ) ? (
                  <button
                    type="button"
                    disabled={actionStatus !== ''}
                    onClick={() => handleResendParticipant(side)}
                    className="mt-5 rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold disabled:opacity-50"
                  >
                    {actionStatus === `participant-${side}`
                      ? 'Reenviando...'
                      : `Reenviar acesso do participante ${side}`}
                  </button>
                ) : null}
              </article>
            ))}
          </div>
        </section>

        <section className="mt-7 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                Resultado técnico
              </p>
              <h2 className="mt-2 text-2xl font-semibold">Resumo</h2>
            </div>
            {application.status === 'CONCLUIDA' ? (
              <button
                type="button"
                onClick={() =>
                  navigate(
                    `/profissional/avaliacoes/${application.id}/resultados`,
                  )
                }
                className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
              >
                Abrir painel completo
              </button>
            ) : null}
          </div>

          {results.length === 0 ? (
            <p className="mt-5 text-sm text-[#385048]/65">
              {application.status === 'CONCLUIDA'
                ? 'A avaliação está concluída, mas ainda não há resultado persistido.'
                : 'Os resultados serão disponibilizados depois da conclusão dos dois participantes.'}
            </p>
          ) : (
            <>
              {generalResult ? (
                <div className="mt-5 rounded-2xl border border-[#A8C8B8]/40 bg-white p-5">
                  <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                    Score geral do casal
                  </p>
                  <p className="mt-2 text-3xl font-semibold">
                    {generalResult.percentual == null
                      ? '—'
                      : `${Number(generalResult.percentual).toFixed(2)}%`}
                  </p>
                  <p className="mt-1 text-sm text-[#385048]/65">
                    {generalResult.acertos_gerais} de{' '}
                    {generalResult.itens_validos} item(ns) ·{' '}
                    {generalResult.faixa || 'Sem faixa'}
                  </p>
                </div>
              ) : null}

              <div className="mt-5 grid gap-4 md:grid-cols-2">
              {['A_SOBRE_B', 'B_SOBRE_A'].map((direction) => {
                const result = resultsByDirection[direction]

                return (
                  <div
                    key={direction}
                    className="rounded-2xl bg-[#A8C8B8]/12 p-5"
                  >
                    <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                      {direction === 'A_SOBRE_B'
                        ? 'A → B × B → B'
                        : 'B → A × A → A'}
                    </p>
                    <p className="mt-2 text-3xl font-semibold">
                      {result?.percentual == null
                        ? '—'
                        : `${Number(result.percentual).toFixed(2)}%`}
                    </p>
                    <p className="mt-1 text-sm text-[#385048]/65">
                      {result?.faixa || 'Sem faixa'}
                    </p>
                  </div>
                )
              })}
              </div>
            </>
          )}
        </section>

        <section className="mt-7 rounded-3xl border border-[#D8B078]/40 bg-white p-7">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Itens fora do cálculo
          </p>
          <h2 className="mt-2 text-2xl font-semibold">Não se aplica</h2>

          {excludedItems.length === 0 ? (
            <p className="mt-4 text-sm text-[#385048]/65">
              Nenhum item foi excluído desta avaliação.
            </p>
          ) : (
            <div className="mt-5 space-y-3">
              {excludedItems.map((item) => (
                <article
                  key={item.item_id}
                  className="rounded-2xl bg-[#D8B078]/10 p-5"
                >
                  <p className="font-semibold">
                    {item.item_codigo} · {item.item_texto}
                  </p>
                  <p className="mt-1 text-xs text-[#385048]/55">
                    {item.secao_titulo}
                  </p>
                  <p className="mt-2 text-sm text-[#385048]/65">
                    Marcado por{' '}
                    {item.marcado_por_nome ||
                      `Participante ${item.marcado_por_lado}`}
                  </p>
                </article>
              ))}
            </div>
          )}
        </section>
      </main>
    </div>
  )
}
