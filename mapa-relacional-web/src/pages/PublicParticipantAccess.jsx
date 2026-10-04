import { useEffect, useState } from 'react'
import {
  getParticipantAccess,
  getParticipantQuestionnaire,
  identifyParticipant,
} from '../services/api'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function needsIdentification(access) {
  return (
    access?.participante?.status === 'PENDENTE' ||
    access?.participante?.idade_snapshot == null ||
    !access?.participante?.genero_snapshot
  )
}

export default function PublicParticipantAccess({ token }) {
  const [access, setAccess] = useState(null)
  const [questionnaire, setQuestionnaire] = useState(null)
  const [form, setForm] = useState({
    nome: '',
    idade: '',
    genero: '',
  })
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    load()
  }, [token])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const result = await getParticipantAccess(token)
      const currentAccess = result.acesso

      setAccess(currentAccess)
      setForm({
        nome: currentAccess.participante.nome_snapshot || '',
        idade:
          currentAccess.participante.idade_snapshot == null
            ? ''
            : String(currentAccess.participante.idade_snapshot),
        genero: currentAccess.participante.genero_snapshot || '',
      })

      if (needsIdentification(currentAccess)) {
        setStatus('ready')
        return
      }

      const questionnaireResult = await getParticipantQuestionnaire(token)
      setQuestionnaire(questionnaireResult.questionario)
      setStatus('ready')
    } catch (error) {
      setStatus('error')
      setMessage(
        error.message || 'Não foi possível validar este acesso.',
      )
    }
  }

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  async function handleIdentification(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    try {
      const result = await identifyParticipant(token, {
        nome: form.nome,
        idade: Number(form.idade),
        genero: form.genero,
      })

      setAccess(result.acesso)

      const questionnaireResult = await getParticipantQuestionnaire(token)
      setQuestionnaire(questionnaireResult.questionario)
      setStatus('ready')
    } catch (error) {
      setStatus('ready')
      setMessage(
        error.message || 'Não foi possível registrar sua identificação.',
      )
    }
  }

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

  if (questionnaire) {
    return (
      <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
        <header className="border-b border-[#A8C8B8]/45 bg-white">
          <div className="mx-auto max-w-5xl px-6 py-5">
            <p className="text-lg font-semibold">
              {questionnaire.avaliacao_nome}
            </p>
            <p className="text-xs text-[#385048]/65">
              Participante {questionnaire.participante.lado} ·{' '}
              {questionnaire.participante.nome_snapshot}
            </p>
          </div>
        </header>

        <main className="mx-auto max-w-5xl px-6 py-10">
          <div className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Identificação concluída
            </p>
            <h1 className="mt-3 text-3xl font-semibold">
              Seu questionário está pronto
            </h1>
            <p className="mt-4 leading-7 text-[#385048]/70">
              Você responderá cada item em duas perspectivas: o que pensa
              sobre si e o que pensa sobre a outra pessoa.
            </p>

            <div className="mt-6 flex flex-wrap gap-3 text-sm">
              <span className="rounded-full bg-[#A8C8B8]/20 px-4 py-2 font-semibold">
                {questionnaire.total_itens} item(ns)
              </span>
              <span className="rounded-full bg-[#A8C8D0]/25 px-4 py-2 font-semibold">
                {questionnaire.secoes.length} seção(ões)
              </span>
            </div>
          </div>

          <div className="mt-7 space-y-6">
            {questionnaire.secoes.map((section) => (
              <section
                key={section.id}
                className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-7"
              >
                <h2 className="text-xl font-semibold">{section.titulo}</h2>
                {section.descricao ? (
                  <p className="mt-2 text-sm leading-6 text-[#385048]/65">
                    {section.descricao}
                  </p>
                ) : null}

                <div className="mt-6 space-y-4">
                  {section.itens.map((item) => (
                    <article
                      key={item.id}
                      className="rounded-2xl bg-[#FEFDFB] p-5 ring-1 ring-[#A8C8B8]/35"
                    >
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                          {item.codigo}
                        </span>
                        <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                          {item.tipo_resposta}
                        </span>
                        {item.permite_nao_se_aplica ? (
                          <span className="rounded-full bg-[#D8B078]/18 px-3 py-1 text-xs font-semibold">
                            Permite Não se aplica
                          </span>
                        ) : null}
                      </div>

                      <p className="mt-3 font-medium leading-7">
                        {item.texto}
                      </p>

                      {item.alternativas.length > 0 ? (
                        <div className="mt-4 flex flex-wrap gap-2">
                          {item.alternativas.map((alternative) => (
                            <span
                              key={alternative.id}
                              className="rounded-xl border border-[#385048]/15 bg-white px-3 py-2 text-sm"
                            >
                              {alternative.rotulo}
                            </span>
                          ))}
                        </div>
                      ) : null}

                      <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <div className="rounded-xl bg-[#A8C8B8]/12 p-4 text-sm">
                          <strong>Perspectiva 1:</strong> sobre mim
                        </div>
                        <div className="rounded-xl bg-[#A8C8D0]/14 p-4 text-sm">
                          <strong>Perspectiva 2:</strong> sobre a outra pessoa
                        </div>
                      </div>
                    </article>
                  ))}
                </div>
              </section>
            ))}
          </div>

          <div className="mt-7 rounded-2xl border border-[#D8B078]/45 bg-[#D8B078]/12 p-5 text-sm leading-6">
            A estrutura do questionário já foi carregada pelo seu acesso
            individual. Na próxima subetapa serão habilitadas as respostas e o
            salvamento progressivo de cada item.
          </div>
        </main>
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
        <div className="grid gap-7 lg:grid-cols-[0.8fr_1.2fr]">
          <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Seu acesso
            </p>
            <h1 className="mt-3 text-3xl font-semibold">
              Olá, {access.participante.nome_snapshot}
            </h1>
            <p className="mt-4 leading-7 text-[#385048]/70">
              Este link pertence ao participante {access.participante.lado}.
              Antes de começar, confirme sua identificação.
            </p>

            <div className="mt-6 rounded-2xl bg-[#D8B078]/15 p-5 text-sm">
              <p>
                <strong>Tipo de vínculo:</strong> {access.tipo_vinculo}
              </p>
              {access.tempo_uniao ? (
                <p className="mt-2">
                  <strong>Tempo de união:</strong> {access.tempo_uniao}
                </p>
              ) : null}
            </div>

            <div className="mt-6 rounded-2xl bg-[#A8C8B8]/12 p-5 text-sm leading-6">
              Cada participante responde separadamente. Você não verá as
              respostas da outra pessoa durante o preenchimento.
            </div>
          </section>

          <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Identificação inicial
            </p>
            <h2 className="mt-3 text-2xl font-semibold">
              Confirme seus dados
            </h2>

            <form className="mt-7 space-y-5" onSubmit={handleIdentification}>
              <label className="block">
                <span className="text-sm font-medium">Nome</span>
                <input
                  type="text"
                  required
                  maxLength="150"
                  value={form.nome}
                  onChange={(event) =>
                    updateField('nome', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Idade</span>
                <input
                  type="number"
                  required
                  min="1"
                  max="120"
                  value={form.idade}
                  onChange={(event) =>
                    updateField('idade', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Gênero</span>
                <input
                  type="text"
                  required
                  maxLength="30"
                  value={form.genero}
                  onChange={(event) =>
                    updateField('genero', event.target.value)
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
                  ? 'Salvando identificação...'
                  : 'Confirmar e iniciar'}
              </button>
            </form>
          </section>
        </div>
      </main>
    </div>
  )
}
