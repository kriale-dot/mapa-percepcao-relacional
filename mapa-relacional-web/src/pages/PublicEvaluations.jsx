import { useEffect, useState } from 'react'
import { listPublicEvaluations } from '../services/api'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export default function PublicEvaluations() {
  const [evaluations, setEvaluations] = useState([])
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    listPublicEvaluations()
      .then((result) => {
        setEvaluations(result.avaliacoes || [])
        setStatus('ready')
      })
      .catch((error) => {
        setStatus('error')
        setMessage(
          error.message || 'Não foi possível carregar as avaliações.',
        )
      })
  }, [])

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/')}
            className="text-left"
          >
            <p className="text-lg font-semibold">
              Avaliação de Percepção Relacional
            </p>
            <p className="text-xs text-[#385048]/65">
              Escolha uma avaliação para começar
            </p>
          </button>

          <button
            type="button"
            onClick={() => navigate('/')}
            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
          >
            Voltar ao início
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-12">
        <div className="max-w-3xl">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Avaliações disponíveis
          </p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">
            Escolha a avaliação que deseja fazer
          </h1>
          <p className="mt-4 text-base leading-7 text-[#385048]/70">
            Você pode iniciar uma avaliação diretamente pelo site. Não é
            necessário ter cadastro prévio nem solicitar liberação ao
            profissional.
          </p>
        </div>

        {status === 'loading' ? (
          <div className="mt-8 rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
            Carregando avaliações...
          </div>
        ) : null}

        {status === 'error' ? (
          <div className="mt-8 rounded-2xl border border-[#D8B078]/40 bg-white p-6">
            {message}
          </div>
        ) : null}

        {status === 'ready' && evaluations.length === 0 ? (
          <div className="mt-8 rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center">
            <p className="font-medium">
              Nenhuma avaliação está disponível no momento.
            </p>
            <p className="mt-2 text-sm text-[#385048]/60">
              Volte em outro momento ou entre em contato com o profissional.
            </p>
          </div>
        ) : null}

        <div className="mt-8 grid gap-5 md:grid-cols-2">
          {evaluations.map((evaluation) => (
            <article
              key={evaluation.versao_id}
              className="flex flex-col rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm"
            >
              <div className="flex-1">
                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#385048]/50">
                  Avaliação
                </p>
                <h2 className="mt-2 text-2xl font-semibold">
                  {evaluation.nome}
                </h2>
                <p className="mt-4 text-sm leading-6 text-[#385048]/70">
                  {evaluation.descricao ||
                    'Avaliação de percepção mútua e conhecimento interpessoal.'}
                </p>
              </div>

              <button
                type="button"
                onClick={() =>
                  navigate(
                    `/avaliacao/${evaluation.versao_id}/iniciar`,
                  )
                }
                className="mt-7 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
              >
                Fazer avaliação
              </button>
            </article>
          ))}
        </div>
      </main>
    </div>
  )
}
