import { useEffect, useState } from 'react'
import {
  getPublicEvaluation,
  startPublicEvaluation,
} from '../services/api'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const initialForm = {
  participante_a_nome: '',
  participante_b_nome: '',
  email_contato: '',
  tipo_vinculo: '',
  tempo_uniao: '',
}

export default function PublicEvaluationStart({ versionId }) {
  const [evaluation, setEvaluation] = useState(null)
  const [form, setForm] = useState(initialForm)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [createdApplication, setCreatedApplication] = useState(null)

  useEffect(() => {
    getPublicEvaluation(versionId)
      .then((result) => {
        setEvaluation(result.avaliacao)
        setStatus('ready')
      })
      .catch((error) => {
        setStatus('error')
        setMessage(
          error.message || 'Não foi possível abrir esta avaliação.',
        )
      })
  }, [versionId])

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    try {
      const result = await startPublicEvaluation(versionId, form)
      setCreatedApplication(result.aplicacao)
      setStatus('created')
    } catch (error) {
      setStatus('ready')
      setMessage(error.message || 'Não foi possível iniciar a avaliação.')
    }
  }

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] p-8 text-[#385048]">
        Carregando avaliação...
      </div>
    )
  }

  if (status === 'error') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-xl rounded-3xl border border-[#D8B078]/40 bg-white p-8">
          <h1 className="text-2xl font-semibold">
            Avaliação indisponível
          </h1>
          <p className="mt-3 text-sm text-[#385048]/70">{message}</p>
          <button
            type="button"
            onClick={() => navigate('/avaliacoes')}
            className="mt-6 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
          >
            Ver avaliações
          </button>
        </div>
      </div>
    )
  }

  if (createdApplication) {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#A8C8B8]/45 bg-white p-8 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Avaliação registrada
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            Sua avaliação foi iniciada
          </h1>
          <p className="mt-4 leading-7 text-[#385048]/70">
            Os links de acesso dos dois participantes foram enviados para o
            e-mail cadastrado. Cada participante deve usar o seu próprio link
            para responder sua parte da avaliação.
          </p>

          <div className="mt-7 grid gap-4 sm:grid-cols-2">
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Participante A
              </p>
              <p className="mt-2 font-semibold">
                {createdApplication.participante_a.nome_snapshot}
              </p>
            </div>
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Participante B
              </p>
              <p className="mt-2 font-semibold">
                {createdApplication.participante_b.nome_snapshot}
              </p>
            </div>
          </div>

          <div className="mt-6 rounded-2xl bg-[#D8B078]/15 p-5 text-sm">
            <p>
              <strong>Tipo de vínculo:</strong>{' '}
              {createdApplication.tipo_vinculo}
            </p>
            {createdApplication.tempo_uniao ? (
              <p className="mt-2">
                <strong>Tempo de união:</strong>{' '}
                {createdApplication.tempo_uniao}
              </p>
            ) : null}
          </div>

          <div className="mt-6 rounded-2xl bg-[#A8C8D0]/18 p-5 text-sm leading-6">
            <p className="font-semibold">Verifique o e-mail cadastrado</p>
            <p className="mt-2 text-[#385048]/70">
              Enviamos dois links diferentes: um para o participante A e outro
              para o participante B. Não troque os links entre os participantes.
            </p>
          </div>

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
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/avaliacoes')}
            className="text-left"
          >
            <p className="text-lg font-semibold">
              {evaluation?.nome || 'Avaliação'}
            </p>
            <p className="text-xs text-[#385048]/65">
              Avaliação de Percepção Relacional
            </p>
          </button>

          <button
            type="button"
            onClick={() => navigate('/avaliacoes')}
            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
          >
            Voltar
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-5xl px-6 py-12">
        <div className="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
          <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Sobre esta avaliação
            </p>
            <h1 className="mt-3 text-2xl font-semibold">
              {evaluation?.nome}
            </h1>
            <p className="mt-4 text-sm leading-7 text-[#385048]/70">
              {evaluation?.descricao ||
                'Instrumento de percepção mútua e conhecimento interpessoal.'}
            </p>

            <div className="mt-6 rounded-2xl bg-[#A8C8B8]/12 p-5 text-sm leading-6">
              <p>
                Esta avaliação é respondida separadamente por duas pessoas.
              </p>
              <p className="mt-2">
                Cada participante terá seu próprio acesso individual.
              </p>
            </div>
          </section>

          <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Iniciar avaliação
            </p>
            <h2 className="mt-3 text-2xl font-semibold">
              Informe os participantes
            </h2>

            <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
              <label className="block">
                <span className="text-sm font-medium">
                  Nome do participante A
                </span>
                <input
                  type="text"
                  required
                  maxLength="150"
                  value={form.participante_a_nome}
                  onChange={(event) =>
                    updateField('participante_a_nome', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">
                  Nome do participante B
                </span>
                <input
                  type="text"
                  required
                  maxLength="150"
                  value={form.participante_b_nome}
                  onChange={(event) =>
                    updateField('participante_b_nome', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">
                  E-mail de contato
                </span>
                <input
                  type="email"
                  required
                  maxLength="190"
                  value={form.email_contato}
                  onChange={(event) =>
                    updateField('email_contato', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Tipo de vínculo</span>
                <input
                  type="text"
                  required
                  maxLength="50"
                  list="public-relationship-types"
                  placeholder="Ex.: CASAL"
                  value={form.tipo_vinculo}
                  onChange={(event) =>
                    updateField('tipo_vinculo', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
                <datalist id="public-relationship-types">
                  <option value="CASAL" />
                  <option value="PAIS_E_FILHOS" />
                  <option value="AMIZADE" />
                  <option value="FAMILIAR" />
                  <option value="PROFISSIONAL" />
                  <option value="LIDERANCA" />
                  <option value="OUTRO" />
                </datalist>
              </label>

              <label className="block">
                <span className="text-sm font-medium">Tempo de união</span>
                <input
                  type="text"
                  maxLength="100"
                  placeholder="Ex.: 12 anos"
                  value={form.tempo_uniao}
                  onChange={(event) =>
                    updateField('tempo_uniao', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              {message ? (
                <div
                  role="alert"
                  className="rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm"
                >
                  {message}
                </div>
              ) : null}

              <button
                type="submit"
                disabled={status === 'saving'}
                className="w-full rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
              >
                {status === 'saving'
                  ? 'Iniciando...'
                  : 'Iniciar avaliação'}
              </button>
            </form>
          </section>
        </div>
      </main>
    </div>
  )
}
