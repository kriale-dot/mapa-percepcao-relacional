import { useEffect, useMemo, useState } from 'react'
import { getPublicResult } from '../services/api'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function resultLabel(direction, feedback) {
  return direction === 'A_SOBRE_B'
    ? `Score de ${feedback.participante_a}`
    : `Score de ${feedback.participante_b}`
}

function bandClasses(band) {
  const normalized = String(band || '').toLowerCase()

  if (normalized === 'bom') {
    return 'bg-green-50 border-green-300'
  }

  if (normalized === 'regular') {
    return 'bg-yellow-50 border-yellow-300'
  }

  if (normalized === 'ruim') {
    return 'bg-red-50 border-red-300'
  }

  return 'bg-[#A8C8D0]/15 border-[#A8C8D0]/45'
}

export default function PublicResult({ token }) {
  const [feedback, setFeedback] = useState(null)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    getPublicResult(token)
      .then((response) => {
        setFeedback(response.devolutiva)
        setStatus('ready')
      })
      .catch((error) => {
        setStatus('error')
        setMessage(
          error.message || 'Não foi possível abrir esta devolutiva.',
        )
      })
  }, [token])

  const resultsByDirection = useMemo(() => {
    const map = {}

    ;(feedback?.resultados || []).forEach((item) => {
      map[item.sentido] = item
    })

    return map
  }, [feedback])

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#A8C8B8]/45 bg-white p-8">
          Carregando devolutiva...
        </div>
      </div>
    )
  }

  if (status === 'error' || !feedback) {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#D8B078]/45 bg-white p-8 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Devolutiva indisponível
          </p>
          <h1 className="mt-3 text-2xl font-semibold">
            Não foi possível abrir este resultado
          </h1>
          <p className="mt-4 text-sm leading-6 text-[#385048]/70">
            {message}
          </p>
          <button
            type="button"
            onClick={() => navigate('/')}
            className="mt-7 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
          >
            Voltar ao início
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto max-w-6xl px-6 py-5">
          <p className="text-lg font-semibold">{feedback.avaliacao_nome}</p>
          <p className="text-xs text-[#385048]/65">
            Devolutiva da Avaliação de Percepção Relacional
          </p>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Resultado liberado
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            {feedback.participante_a} ↔ {feedback.participante_b}
          </h1>
          <p className="mt-4 max-w-3xl leading-7 text-[#385048]/70">
            Este resultado compara a percepção que cada participante tem da
            outra pessoa com a forma como essa pessoa se percebe.
          </p>

          <div className="mt-6 flex flex-wrap gap-3 text-sm">
            <span className="rounded-full bg-[#A8C8B8]/20 px-4 py-2 font-semibold">
              {feedback.tipo_vinculo}
            </span>
            {feedback.tempo_uniao ? (
              <span className="rounded-full bg-[#D8B078]/16 px-4 py-2 font-semibold">
                Tempo de união: {feedback.tempo_uniao}
              </span>
            ) : null}
          </div>
        </section>

        {feedback.resultado_geral ? (
          <section
            className={[
              'mt-7 rounded-3xl border p-7 shadow-sm',
              bandClasses(feedback.resultado_geral.faixa),
            ].join(' ')}
          >
            <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#385048]/55">
              Score geral do casal
            </p>
            <h2 className="mt-3 text-2xl font-semibold">
              {feedback.resultado_geral.acertos_gerais} de{' '}
              {feedback.resultado_geral.itens_validos} acertos completos
            </h2>

            <div className="mt-5 flex flex-wrap items-end gap-3">
              <span className="text-5xl font-semibold">
                {feedback.resultado_geral.percentual == null
                  ? '—'
                  : `${Number(feedback.resultado_geral.percentual).toFixed(2)}%`}
              </span>
              {feedback.resultado_geral.faixa ? (
                <span className="mb-1 rounded-full bg-white/80 px-3 py-1 text-sm font-semibold">
                  {feedback.resultado_geral.faixa}
                </span>
              ) : null}
            </div>

            <div className="mt-4 h-3 overflow-hidden rounded-full bg-black/10">
              <div
                className={[
                  'h-full rounded-full',
                  String(feedback.resultado_geral.faixa || '').toLowerCase() === 'bom'
                    ? 'bg-green-500'
                    : String(feedback.resultado_geral.faixa || '').toLowerCase() === 'regular'
                      ? 'bg-yellow-400'
                      : String(feedback.resultado_geral.faixa || '').toLowerCase() === 'ruim'
                        ? 'bg-red-500'
                        : 'bg-[#385048]',
                ].join(' ')}
                style={{
                  width: `${Math.max(
                    0,
                    Math.min(
                      100,
                      Number(feedback.resultado_geral.percentual || 0),
                    ),
                  )}%`,
                }}
              />
            </div>

            <p className="mt-4 text-sm leading-6 text-[#385048]/70">
              O score geral soma 1 ponto somente quando as duas percepções do
              mesmo item coincidem entre os cônjuges.
            </p>
          </section>
        ) : null}

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
                  {resultLabel(direction, feedback)}
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

                <div className="mt-4 h-3 overflow-hidden rounded-full bg-white/55">
                  <div
                    className={[
                      'h-full rounded-full',
                      String(item?.faixa || '').toLowerCase() === 'bom'
                        ? 'bg-green-500'
                        : String(item?.faixa || '').toLowerCase() === 'regular'
                          ? 'bg-yellow-400'
                          : String(item?.faixa || '').toLowerCase() === 'ruim'
                            ? 'bg-red-500'
                            : 'bg-[#385048]',
                    ].join(' ')}
                    style={{
                      width: `${Math.max(
                        0,
                        Math.min(100, Number(item?.percentual || 0)),
                      )}%`,
                    }}
                  />
                </div>

                <p className="mt-4 text-sm text-[#385048]/65">
                  {item?.coincidencias ?? 0} coincidência(s) em{' '}
                  {item?.comparacoes_validas ?? 0} comparação(ões) válida(s)
                </p>
              </section>
            )
          })}
        </div>

        {(feedback.secoes || []).length > 0 ? (
          <section className="mt-7 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Resultado por tópico
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Seções da avaliação
            </h2>

            <div className="mt-6 space-y-5">
              {feedback.secoes.map((section) => (
                <article
                  key={section.id}
                  className="rounded-2xl bg-[#FEFDFB] p-5 ring-1 ring-[#A8C8B8]/30"
                >
                  <h3 className="font-semibold">{section.titulo}</h3>

                  <div className="mt-4 grid gap-3 md:grid-cols-2">
                    {['A_SOBRE_B', 'B_SOBRE_A'].map((direction) => {
                      const item = section[direction]

                      return (
                        <div
                          key={direction}
                          className="rounded-xl bg-white p-4"
                        >
                          <p className="text-sm font-semibold">
                            {resultLabel(direction, feedback)}
                          </p>
                          <p className="mt-2 text-2xl font-semibold">
                            {item.percentual == null
                              ? '—'
                              : `${Number(item.percentual).toFixed(2)}%`}
                          </p>
                          <p className="mt-1 text-xs text-[#385048]/60">
                            {item.faixa || 'Sem faixa'} ·{' '}
                            {item.coincidencias} de{' '}
                            {item.comparacoes_validas}
                          </p>
                        </div>
                      )
                    })}
                  </div>
                </article>
              ))}
            </div>
          </section>
        ) : null}

        <section className="mt-7 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Devolutiva profissional
          </p>
          <h2 className="mt-2 text-2xl font-semibold">
            Síntese e observações
          </h2>

          {feedback.sintese ? (
            <div className="mt-6">
              <h3 className="font-semibold">Síntese</h3>
              <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-[#385048]/75">
                {feedback.sintese}
              </p>
            </div>
          ) : null}

          {feedback.observacoes ? (
            <div className="mt-6">
              <h3 className="font-semibold">Observações</h3>
              <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-[#385048]/75">
                {feedback.observacoes}
              </p>
            </div>
          ) : null}

          {feedback.comentario_profissional ? (
            <div className="mt-6 rounded-2xl bg-[#A8C8B8]/12 p-5">
              <h3 className="font-semibold">Comentário profissional</h3>
              <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-[#385048]/75">
                {feedback.comentario_profissional}
              </p>
            </div>
          ) : null}

          {!feedback.sintese &&
          !feedback.observacoes &&
          !feedback.comentario_profissional ? (
            <p className="mt-5 text-sm text-[#385048]/65">
              O profissional liberou o resultado sem comentários adicionais.
            </p>
          ) : null}
        </section>

        {(feedback.itens_excluidos || []).length > 0 ? (
          <section className="mt-7 rounded-3xl border border-[#D8B078]/45 bg-white p-7">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Fora do cálculo
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Itens marcados como “Não se aplica”
            </h2>

            <div className="mt-5 space-y-3">
              {feedback.itens_excluidos.map((item, index) => (
                <div
                  key={`${item.item_codigo}-${index}`}
                  className="rounded-2xl bg-[#D8B078]/10 p-5"
                >
                  <p className="font-semibold">
                    {item.item_codigo} · {item.item_texto}
                  </p>
                  <p className="mt-1 text-xs text-[#385048]/55">
                    {item.secao_titulo}
                  </p>
                </div>
              ))}
            </div>
          </section>
        ) : null}

        <div className="mt-7 rounded-2xl bg-[#A8C8D0]/14 p-5 text-sm leading-6">
          Este resultado é uma comparação técnica de percepções e não constitui
          diagnóstico clínico automático.
        </div>
      </main>
    </div>
  )
}
