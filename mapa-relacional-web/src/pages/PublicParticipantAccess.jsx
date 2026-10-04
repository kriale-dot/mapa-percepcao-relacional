import { useEffect, useState } from 'react'
import { getParticipantAccess } from '../services/api'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export default function PublicParticipantAccess({ token }) {
  const [access, setAccess] = useState(null)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    getParticipantAccess(token)
      .then((result) => {
        setAccess(result.acesso)
        setStatus('ready')
      })
      .catch((error) => {
        setStatus('error')
        setMessage(
          error.message || 'Não foi possível validar este acesso.',
        )
      })
  }, [token])

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-xl rounded-3xl border border-[#A8C8B8]/45 bg-white p-8">
          Validando seu acesso...
        </div>
      </div>
    )
  }

  if (status === 'error') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-xl rounded-3xl border border-[#D8B078]/40 bg-white p-8 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Acesso indisponível
          </p>
          <h1 className="mt-3 text-2xl font-semibold">
            Não foi possível abrir este link
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
        <div className="mx-auto max-w-4xl px-6 py-5">
          <p className="text-lg font-semibold">
            {access.avaliacao_nome}
          </p>
          <p className="text-xs text-[#385048]/65">
            Acesso individual do participante
          </p>
        </div>
      </header>

      <main className="mx-auto max-w-4xl px-6 py-12">
        <div className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-8 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Acesso confirmado
          </p>

          <h1 className="mt-3 text-3xl font-semibold">
            Olá, {access.participante.nome_snapshot}
          </h1>

          <p className="mt-4 leading-7 text-[#385048]/70">
            Este é o acesso individual do participante {access.participante.lado}.
            Use somente este link para responder a sua parte da avaliação.
          </p>

          <div className="mt-7 grid gap-4 sm:grid-cols-2">
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Participante
              </p>
              <p className="mt-2 font-semibold">
                {access.participante.nome_snapshot}
              </p>
              <p className="mt-1 text-sm text-[#385048]/65">
                Lado {access.participante.lado}
              </p>
            </div>

            <div className="rounded-2xl bg-[#D8B078]/15 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Relação
              </p>
              <p className="mt-2 font-semibold">{access.tipo_vinculo}</p>
              {access.tempo_uniao ? (
                <p className="mt-1 text-sm text-[#385048]/65">
                  Tempo de união: {access.tempo_uniao}
                </p>
              ) : null}
            </div>
          </div>

          <div className="mt-7 rounded-2xl border border-[#A8C8D0]/45 bg-[#A8C8D0]/12 p-5 text-sm leading-6">
            O link está válido e identificado corretamente. Na próxima etapa do
            desenvolvimento, esta tela seguirá para a identificação e o
            preenchimento individual da avaliação.
          </div>
        </div>
      </main>
    </div>
  )
}
