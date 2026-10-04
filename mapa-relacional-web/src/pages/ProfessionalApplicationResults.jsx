import { useEffect, useMemo, useState } from 'react'
import {
  calculateApplicationResults,
  getApplicationFeedback,
  getApplicationResults,
  releaseApplicationFeedback,
  saveApplicationFeedback,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function directionLabel(direction, application) {
  const a = application?.participante_a?.nome || 'Participante A'
  const b = application?.participante_b?.nome || 'Participante B'

  return direction === 'A_SOBRE_B'
    ? `Percepção de ${a} sobre ${b}`
    : `Percepção de ${b} sobre ${a}`
}

function comparisonLabels(direction, application) {
  const a = application?.participante_a?.nome || 'Participante A'
  const b = application?.participante_b?.nome || 'Participante B'

  return direction === 'A_SOBRE_B'
    ? {
        perceived: `O que ${a} percebe sobre ${b}`,
        self: `O que ${b} pensa sobre si`,
      }
    : {
        perceived: `O que ${b} percebe sobre ${a}`,
        self: `O que ${a} pensa sobre si`,
      }
}

function bandClasses(band) {
  const normalized = String(band || '').toLowerCase()

  if (normalized === 'bom') {
    return 'bg-[#88B098]/22 border-[#88B098]/55'
  }

  if (normalized === 'regular') {
    return 'bg-[#D8B078]/18 border-[#D8B078]/55'
  }

  if (normalized === 'ruim') {
    return 'bg-[#C97C5D]/14 border-[#C97C5D]/45'
  }

  return 'bg-[#A8C8D0]/15 border-[#A8C8D0]/45'
}

function ResultBar({ percentage }) {
  const value =
    percentage == null ? 0 : Math.max(0, Math.min(100, Number(percentage)))

  return (
    <div className="mt-4">
      <div className="h-3 overflow-hidden rounded-full bg-[#A8C8B8]/20">
        <div
          className="h-full rounded-full bg-[#385048]"
          style={{ width: `${value}%` }}
        />
      </div>
    </div>
  )
}

export default function ProfessionalApplicationResults({ applicationId }) {
  const [result, setResult] = useState(null)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [feedback, setFeedback] = useState(null)
  const [feedbackContext, setFeedbackContext] = useState(null)
  const [feedbackForm, setFeedbackForm] = useState({
    sintese: '',
    observacoes: '',
    comentario_profissional: '',
  })
  const [feedbackStatus, setFeedbackStatus] = useState('ready')
  const [feedbackMessage, setFeedbackMessage] = useState('')

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
      const [resultsResponse, feedbackResponse] = await Promise.all([
        getApplicationResults(applicationId),
        getApplicationFeedback(applicationId),
      ])

      setResult(resultsResponse.resultado)
      setFeedback(feedbackResponse.devolutiva)
      setFeedbackContext(feedbackResponse.aplicacao)
      setFeedbackForm({
        sintese: feedbackResponse.devolutiva?.sintese || '',
        observacoes: feedbackResponse.devolutiva?.observacoes || '',
        comentario_profissional:
          feedbackResponse.devolutiva?.comentario_profissional || '',
      })
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar os resultados.')
    }
  }

  async function handleCalculate() {
    setStatus('calculating')
    setMessage('')

    try {
      const response = await calculateApplicationResults(applicationId)
      setResult(response.resultado)
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível calcular os resultados.')
    }
  }

  function updateFeedbackField(field, value) {
    setFeedbackForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  async function handleSaveFeedback() {
    setFeedbackStatus('saving')
    setFeedbackMessage('')

    try {
      const response = await saveApplicationFeedback(
        applicationId,
        feedbackForm,
      )
      setFeedback(response.devolutiva)
      setFeedbackStatus('ready')
      setFeedbackMessage('Devolutiva salva como rascunho.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setFeedbackStatus('ready')
      setFeedbackMessage(
        error.message || 'Não foi possível salvar a devolutiva.',
      )
    }
  }

  async function handleReleaseFeedback() {
    const confirmed = window.confirm(
      'Liberar a devolutiva? O link será enviado para o e-mail cadastrado e o texto ficará congelado para preservar o histórico.',
    )

    if (!confirmed) return

    setFeedbackStatus('releasing')
    setFeedbackMessage('')

    try {
      const saved = await saveApplicationFeedback(
        applicationId,
        feedbackForm,
      )
      setFeedback(saved.devolutiva)

      const released = await releaseApplicationFeedback(applicationId)
      setFeedback(released.devolutiva)
      setFeedbackStatus('ready')
      setFeedbackMessage(
        'Devolutiva liberada e enviada para o e-mail cadastrado.',
      )
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setFeedbackStatus('ready')
      setFeedbackMessage(
        error.message || 'Não foi possível liberar a devolutiva.',
      )
    }
  }

  const resultsByDirection = useMemo(() => {
    const map = {}

    ;(result?.resultados || []).forEach((item) => {
      map[item.sentido] = item
    })

    return map
  }, [result])

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] p-8 text-[#385048]">
        Carregando resultados...
      </div>
    )
  }

  if (status === 'error') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#D8B078]/45 bg-white p-8">
          <h1 className="text-2xl font-semibold">
            Resultados indisponíveis
          </h1>
          <p className="mt-4 text-sm leading-6 text-[#385048]/70">
            {message}
          </p>
          <div className="mt-7 flex flex-wrap gap-3">
            <button
              type="button"
              onClick={load}
              className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
            >
              Tentar novamente
            </button>
            <button
              type="button"
              onClick={() => navigate('/profissional/avaliacoes')}
              className="rounded-xl border border-[#385048]/20 px-5 py-3 text-sm font-semibold"
            >
              Voltar às avaliações
            </button>
          </div>
        </div>
      </div>
    )
  }

  const application = result?.aplicacao
  const hasResults = (result?.resultados || []).length > 0

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Resultados da avaliação</p>
            <p className="text-xs text-[#385048]/65">
              {application?.instrumento_nome || 'Avaliação de Percepção Relacional'}
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            {application?.instrumento_id &&
            application?.instrumento_versao_id ? (
              <button
                type="button"
                onClick={() =>
                  navigate(
                    `/profissional/instrumentos/${application.instrumento_id}/versoes/${application.instrumento_versao_id}/faixas-resultados`,
                  )
                }
                className="rounded-xl border border-[#D8B078]/65 px-4 py-2 text-sm font-semibold"
              >
                Ver faixas
              </button>
            ) : null}
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
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Comparação relacional
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            {application?.participante_a?.nome} ↔{' '}
            {application?.participante_b?.nome}
          </h1>
          <p className="mt-4 max-w-3xl leading-7 text-[#385048]/70">
            O resultado compara a percepção que cada participante tem do outro
            com a forma como o outro se percebe. Itens marcados como “Não se
            aplica” ficam fora do cálculo.
          </p>

          <p className="mt-3 text-sm text-[#385048]/55">
            Versão do instrumento: {application?.numero_versao}
          </p>

          {!hasResults ? (
            <div className="mt-6 rounded-2xl bg-[#D8B078]/15 p-5">
              <p className="text-sm">
                Esta aplicação está concluída, mas ainda não possui um cálculo
                persistido de resultados.
              </p>
              <button
                type="button"
                disabled={status === 'calculating'}
                onClick={handleCalculate}
                className="mt-4 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
              >
                {status === 'calculating'
                  ? 'Calculando...'
                  : 'Calcular resultados'}
              </button>
            </div>
          ) : null}
        </section>

        {hasResults ? (
          <>
            <div className="mt-7 grid gap-5 lg:grid-cols-2">
              {['A_SOBRE_B', 'B_SOBRE_A'].map((direction) => {
                const item = resultsByDirection[direction]

                return (
                  <section
                    key={direction}
                    className={[
                      'rounded-3xl border p-7 shadow-sm',
                      bandClasses(item?.faixa),
                    ].join(' ')}
                  >
                    <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#385048]/55">
                      {direction === 'A_SOBRE_B'
                        ? 'A → B × B → B'
                        : 'B → A × A → A'}
                    </p>
                    <h2 className="mt-3 text-xl font-semibold">
                      {directionLabel(direction, application)}
                    </h2>

                    <div className="mt-5 flex items-end gap-3">
                      <span className="text-4xl font-semibold">
                        {item?.percentual == null
                          ? '—'
                          : `${Number(item.percentual).toFixed(2)}%`}
                      </span>
                      {item?.faixa ? (
                        <span className="mb-1 rounded-full bg-white/70 px-3 py-1 text-sm font-semibold">
                          {item.faixa}
                        </span>
                      ) : null}
                    </div>

                    <ResultBar percentage={item?.percentual} />

                    <p className="mt-4 text-sm text-[#385048]/65">
                      {item?.coincidencias ?? 0} coincidência(s) em{' '}
                      {item?.comparacoes_validas ?? 0} comparação(ões) válida(s)
                    </p>
                  </section>
                )
              })}
            </div>

            {(result.secoes || []).length > 0 ? (
              <section className="mt-7 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7">
                <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                  Resultado por tópico
                </p>
                <h2 className="mt-2 text-2xl font-semibold">
                  Seções da avaliação
                </h2>

                <div className="mt-6 space-y-5">
                  {result.secoes.map((section) => (
                    <div
                      key={section.id}
                      className="rounded-2xl bg-[#FEFDFB] p-5 ring-1 ring-[#A8C8B8]/35"
                    >
                      <h3 className="font-semibold">{section.titulo}</h3>
                      <div className="mt-4 grid gap-4 md:grid-cols-2">
                        {['A_SOBRE_B', 'B_SOBRE_A'].map((direction) => {
                          const sectionResult = section[direction]

                          return (
                            <div
                              key={direction}
                              className="rounded-xl bg-white p-4"
                            >
                              <p className="text-sm font-semibold">
                                {directionLabel(direction, application)}
                              </p>
                              <p className="mt-2 text-2xl font-semibold">
                                {sectionResult.percentual == null
                                  ? '—'
                                  : `${Number(sectionResult.percentual).toFixed(2)}%`}
                              </p>
                              <p className="mt-1 text-xs text-[#385048]/60">
                                {sectionResult.faixa || 'Sem faixa'} ·{' '}
                                {sectionResult.coincidencias} de{' '}
                                {sectionResult.comparacoes_validas}
                              </p>
                            </div>
                          )
                        })}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            <section className="mt-7 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7">
              <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                Coincidências e divergências
              </p>
              <h2 className="mt-2 text-2xl font-semibold">
                Comparação item a item
              </h2>

              <div className="mt-6 space-y-4">
                {(result.comparacoes || []).map((comparison) => {
                  const labels = comparisonLabels(
                    comparison.sentido,
                    application,
                  )

                  return (
                    <article
                      key={comparison.id}
                      className="rounded-2xl bg-[#FEFDFB] p-5 ring-1 ring-[#A8C8B8]/30"
                    >
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                          {comparison.item_codigo}
                        </span>
                        <span
                          className={[
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            comparison.coincide
                              ? 'bg-[#88B098]/22'
                              : comparison.comparavel
                                ? 'bg-[#C97C5D]/14'
                                : 'bg-[#D8B078]/18',
                          ].join(' ')}
                        >
                          {comparison.comparavel
                            ? comparison.coincide
                              ? 'Coincide'
                              : 'Diverge'
                            : 'Não comparável'}
                        </span>
                      </div>

                      <p className="mt-3 font-medium leading-6">
                        {comparison.item_texto}
                      </p>

                      <div className="mt-4 grid gap-3 md:grid-cols-2">
                        <div className="rounded-xl bg-white p-4">
                          <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                            {labels.perceived}
                          </p>
                          <p className="mt-2 text-sm font-medium">
                            {comparison.resposta_percebida ?? 'Sem resposta'}
                          </p>
                        </div>
                        <div className="rounded-xl bg-white p-4">
                          <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                            {labels.self}
                          </p>
                          <p className="mt-2 text-sm font-medium">
                            {comparison.resposta_autorreferida ?? 'Sem resposta'}
                          </p>
                        </div>
                      </div>
                    </article>
                  )
                })}
              </div>
            </section>

            {(result.itens_excluidos || []).length > 0 ? (
              <section className="mt-7 rounded-3xl border border-[#D8B078]/45 bg-white p-7">
                <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                  Fora do cálculo
                </p>
                <h2 className="mt-2 text-2xl font-semibold">
                  Itens marcados como “Não se aplica”
                </h2>

                <div className="mt-5 space-y-3">
                  {result.itens_excluidos.map((item) => (
                    <div
                      key={item.item_id}
                      className="rounded-2xl bg-[#D8B078]/10 p-5"
                    >
                      <p className="font-semibold">
                        {item.item_codigo} · {item.item_texto}
                      </p>
                      <p className="mt-2 text-sm text-[#385048]/65">
                        Marcado por {item.marcado_por_nome || `Participante ${item.marcado_por_lado}`}
                      </p>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            <section className="mt-7 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
              <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                Devolutiva profissional
              </p>
              <h2 className="mt-2 text-2xl font-semibold">
                Preparar conteúdo para os participantes
              </h2>
              <p className="mt-3 max-w-3xl text-sm leading-6 text-[#385048]/65">
                O cálculo técnico permanece separado da interpretação
                profissional. Ao liberar, um link seguro será enviado ao
                e-mail cadastrado
                {feedbackContext?.email_contato
                  ? ` (${feedbackContext.email_contato})`
                  : ''}.
              </p>

              {feedback?.status === 'LIBERADA' ? (
                <div className="mt-6 rounded-2xl bg-[#A8C8B8]/16 p-5 text-sm leading-6">
                  <p className="font-semibold">Devolutiva liberada</p>
                  <p className="mt-2 text-[#385048]/70">
                    O conteúdo está congelado e o link foi enviado ao e-mail
                    cadastrado.
                    {feedback.liberada_em
                      ? ` Liberação: ${feedback.liberada_em}.`
                      : ''}
                  </p>
                </div>
              ) : (
                <div className="mt-6 rounded-2xl bg-[#D8B078]/12 p-5 text-sm leading-6">
                  A devolutiva está em rascunho. Salve quantas vezes precisar
                  antes de liberar aos participantes.
                </div>
              )}

              <div className="mt-6 space-y-5">
                <label className="block">
                  <span className="text-sm font-medium">Síntese</span>
                  <textarea
                    rows="4"
                    maxLength="5000"
                    disabled={feedback?.status === 'LIBERADA'}
                    value={feedbackForm.sintese}
                    onChange={(event) =>
                      updateFeedbackField('sintese', event.target.value)
                    }
                    className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none disabled:opacity-70"
                    placeholder="Síntese geral da avaliação para os participantes."
                  />
                </label>

                <label className="block">
                  <span className="text-sm font-medium">Observações</span>
                  <textarea
                    rows="5"
                    maxLength="10000"
                    disabled={feedback?.status === 'LIBERADA'}
                    value={feedbackForm.observacoes}
                    onChange={(event) =>
                      updateFeedbackField('observacoes', event.target.value)
                    }
                    className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none disabled:opacity-70"
                    placeholder="Pontos de atenção, contexto e orientações que acompanham o resultado."
                  />
                </label>

                <label className="block">
                  <span className="text-sm font-medium">
                    Comentário profissional
                  </span>
                  <textarea
                    rows="6"
                    maxLength="10000"
                    disabled={feedback?.status === 'LIBERADA'}
                    value={feedbackForm.comentario_profissional}
                    onChange={(event) =>
                      updateFeedbackField(
                        'comentario_profissional',
                        event.target.value,
                      )
                    }
                    className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none disabled:opacity-70"
                    placeholder="Comentário profissional que será apresentado junto ao resultado."
                  />
                </label>
              </div>

              {feedbackMessage ? (
                <div className="mt-5 rounded-2xl bg-[#A8C8D0]/14 px-5 py-4 text-sm">
                  {feedbackMessage}
                </div>
              ) : null}

              {feedback?.status !== 'LIBERADA' ? (
                <div className="mt-6 flex flex-wrap gap-3">
                  <button
                    type="button"
                    disabled={feedbackStatus !== 'ready'}
                    onClick={handleSaveFeedback}
                    className="rounded-xl border border-[#385048]/20 px-5 py-3 text-sm font-semibold disabled:opacity-50"
                  >
                    {feedbackStatus === 'saving'
                      ? 'Salvando...'
                      : 'Salvar rascunho'}
                  </button>

                  <button
                    type="button"
                    disabled={feedbackStatus !== 'ready'}
                    onClick={handleReleaseFeedback}
                    className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-50"
                  >
                    {feedbackStatus === 'releasing'
                      ? 'Liberando e enviando...'
                      : 'Liberar e enviar por e-mail'}
                  </button>
                </div>
              ) : null}
            </section>

            <div className="mt-7 rounded-2xl bg-[#A8C8D0]/14 p-5 text-sm leading-6">
              Algoritmo de comparação: versão{' '}
              {result.resultados?.[0]?.algoritmo_versao || '1.0'}. O resultado
              técnico não representa diagnóstico clínico automático.
            </div>
          </>
        ) : null}
      </main>
    </div>
  )
}
