import { useEffect, useState } from 'react'
import {
  getParticipantAccess,
  completeParticipantEvaluation,
  getParticipantQuestionnaire,
  identifyParticipant,
  markParticipantItemNotApplicable,
  saveParticipantResponse,
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

function answerKey(itemId, perspective) {
  return `${itemId}:${perspective}`
}

function perspectiveApiName(perspective) {
  return perspective === 'sobre_mim' ? 'SOBRE_MIM' : 'SOBRE_OUTRO'
}

function buildDrafts(questionnaire) {
  const drafts = {}

  questionnaire.secoes.forEach((section) => {
    section.itens.forEach((item) => {
      ;['sobre_mim', 'sobre_outro'].forEach((perspective) => {
        const response = item.respostas?.[perspective]
        const key = answerKey(item.id, perspective)

        if (response?.alternativa_id != null) {
          drafts[key] = String(response.alternativa_id)
        } else if (response?.valor_numero != null) {
          drafts[key] = String(response.valor_numero)
        } else {
          drafts[key] = response?.valor_texto || ''
        }
      })
    })
  })

  return drafts
}

function isNumericItem(item) {
  if (item.alternativas.length > 0) return false

  return /NUM|NUMBER|NOTA|ESCALA|PONT/i.test(item.tipo_resposta)
}

function SaveState({ state }) {
  if (!state) return null

  if (state.status === 'saving') {
    return (
      <span className="text-xs font-medium text-[#385048]/55">
        Salvando...
      </span>
    )
  }

  if (state.status === 'saved') {
    return (
      <span className="text-xs font-medium text-[#385048]/65">
        Salvo
      </span>
    )
  }

  if (state.status === 'error') {
    return (
      <span className="text-xs font-medium text-[#C97C5D]">
        {state.message || 'Não foi possível salvar.'}
      </span>
    )
  }

  return null
}

export default function PublicParticipantAccess({ token }) {
  const [access, setAccess] = useState(null)
  const [questionnaire, setQuestionnaire] = useState(null)
  const [drafts, setDrafts] = useState({})
  const [saveStates, setSaveStates] = useState({})
  const [form, setForm] = useState({
    nome: '',
    idade: '',
    genero: '',
  })
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [questionnaireNotice, setQuestionnaireNotice] = useState('')
  const [excludingItemId, setExcludingItemId] = useState(null)
  const [completion, setCompletion] = useState(null)

  useEffect(() => {
    load()
  }, [token])

  function setQuestionnaireData(nextQuestionnaire) {
    setQuestionnaire(nextQuestionnaire)
    setDrafts(buildDrafts(nextQuestionnaire))
  }

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const result = await getParticipantAccess(token)

      if (!result?.acesso?.participante) {
        throw new Error(
          'A API não retornou os dados esperados para este acesso.',
        )
      }

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

      if (!questionnaireResult?.questionario) {
        throw new Error(
          'A API não retornou a estrutura esperada do questionário.',
        )
      }

      setQuestionnaireData(questionnaireResult.questionario)
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

  function updateDraft(itemId, perspective, value) {
    const key = answerKey(itemId, perspective)

    setDrafts((current) => ({
      ...current,
      [key]: value,
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

      if (!result?.acesso?.participante) {
        throw new Error(
          'A API não retornou a identificação atualizada do participante.',
        )
      }

      setAccess(result.acesso)

      const questionnaireResult = await getParticipantQuestionnaire(token)

      if (!questionnaireResult?.questionario) {
        throw new Error(
          'A API não retornou a estrutura esperada do questionário.',
        )
      }

      setQuestionnaireData(questionnaireResult.questionario)
      setStatus('ready')
    } catch (error) {
      setStatus('ready')
      setMessage(
        error.message || 'Não foi possível registrar sua identificação.',
      )
    }
  }

  function applySavedResponse(itemId, perspective, result) {
    if (!result?.resposta) return

    setQuestionnaire((current) => {
      if (!current) return current

      return {
        ...current,
        progresso: result.progresso || current.progresso,
        secoes: current.secoes.map((section) => ({
          ...section,
          itens: section.itens.map((item) =>
            item.id === itemId
              ? {
                  ...item,
                  respostas: {
                    ...item.respostas,
                    [perspective]: result.resposta,
                  },
                }
              : item,
          ),
        })),
      }
    })
  }

  async function persistResponse(item, perspective, payload) {
    const key = answerKey(item.id, perspective)

    setSaveStates((current) => ({
      ...current,
      [key]: { status: 'saving', message: '' },
    }))

    try {
      const result = await saveParticipantResponse(token, item.id, {
        perspectiva: perspectiveApiName(perspective),
        ...payload,
      })

      applySavedResponse(item.id, perspective, result)

      setSaveStates((current) => ({
        ...current,
        [key]: { status: 'saved', message: '' },
      }))
    } catch (error) {
      setSaveStates((current) => ({
        ...current,
        [key]: {
          status: 'error',
          message: error.message || 'Não foi possível salvar.',
        },
      }))
    }
  }

  function handleAlternative(item, perspective, alternativeId) {
    updateDraft(item.id, perspective, String(alternativeId))

    persistResponse(item, perspective, {
      alternativa_id: alternativeId,
    })
  }

  function handleOpenSave(item, perspective) {
    const key = answerKey(item.id, perspective)
    const value = String(drafts[key] ?? '').trim()

    if (value === '') return

    if (isNumericItem(item)) {
      persistResponse(item, perspective, {
        valor_numero: value,
      })
      return
    }

    persistResponse(item, perspective, {
      valor_texto: value,
    })
  }

  async function reloadQuestionnaire() {
    const result = await getParticipantQuestionnaire(token)

    if (!result?.questionario) {
      throw new Error(
        'A API não retornou a estrutura esperada do questionário.',
      )
    }

    setQuestionnaireData(result.questionario)
    return result.questionario
  }

  async function handleNotApplicable(item) {
    const confirmed = window.confirm(
      'Ao marcar “Não se aplica”, este item será retirado desta avaliação para os dois participantes e não entrará no resultado. Deseja continuar?',
    )

    if (!confirmed) return

    setExcludingItemId(item.id)
    setQuestionnaireNotice('')

    try {
      await markParticipantItemNotApplicable(token, item.id)
      await reloadQuestionnaire()
      setQuestionnaireNotice(
        'Item marcado como “Não se aplica” e removido da avaliação para os dois participantes.',
      )
    } catch (error) {
      setQuestionnaireNotice(
        error.message || 'Não foi possível marcar este item como “Não se aplica”.',
      )
    } finally {
      setExcludingItemId(null)
    }
  }

  async function handleComplete() {
    setQuestionnaireNotice('')

    try {
      const result = await completeParticipantEvaluation(token)
      setCompletion(result)
    } catch (error) {
      if (error?.data?.progresso && questionnaire) {
        setQuestionnaire((current) =>
          current
            ? {
                ...current,
                progresso: error.data.progresso,
              }
            : current,
        )
      }

      setQuestionnaireNotice(
        error.message || 'Não foi possível concluir sua participação.',
      )
    }
  }

  function renderAnswerControl(item, perspective, label) {
    const key = answerKey(item.id, perspective)
    const state = saveStates[key]
    const draft = drafts[key] ?? ''

    return (
      <div
        className={[
          'rounded-2xl p-5',
          perspective === 'sobre_mim'
            ? 'bg-[#A8C8B8]/12'
            : 'bg-[#A8C8D0]/14',
        ].join(' ')}
      >
        <div className="flex items-center justify-between gap-3">
          <p className="font-semibold">{label}</p>
          <SaveState state={state} />
        </div>

        {item.alternativas.length > 0 ? (
          <div className="mt-4 space-y-2">
            {item.alternativas.map((alternative) => (
              <label
                key={alternative.id}
                className="flex cursor-pointer items-start gap-3 rounded-xl border border-[#385048]/12 bg-white px-4 py-3 text-sm"
              >
                <input
                  type="radio"
                  name={key}
                  value={alternative.id}
                  checked={draft === String(alternative.id)}
                  disabled={state?.status === 'saving'}
                  onChange={() =>
                    handleAlternative(
                      item,
                      perspective,
                      alternative.id,
                    )
                  }
                  className="mt-1"
                />
                <span>{alternative.rotulo}</span>
              </label>
            ))}
          </div>
        ) : isNumericItem(item) ? (
          <div className="mt-4">
            <input
              type="number"
              value={draft}
              onChange={(event) =>
                updateDraft(item.id, perspective, event.target.value)
              }
              onBlur={() => handleOpenSave(item, perspective)}
              className="w-full rounded-xl border border-[#385048]/20 bg-white px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
            />
            <div className="mt-3 flex items-center justify-between gap-3">
              <p className="text-xs text-[#385048]/55">
                A resposta também é salva ao sair do campo.
              </p>
              <button
                type="button"
                disabled={String(draft).trim() === '' || state?.status === 'saving'}
                onClick={() => handleOpenSave(item, perspective)}
                className="rounded-lg border border-[#385048]/15 bg-white px-3 py-2 text-xs font-semibold disabled:opacity-45"
              >
                Salvar resposta
              </button>
            </div>
          </div>
        ) : (
          <div className="mt-4">
            <textarea
              rows="4"
              value={draft}
              onChange={(event) =>
                updateDraft(item.id, perspective, event.target.value)
              }
              onBlur={() => handleOpenSave(item, perspective)}
              className="w-full resize-y rounded-xl border border-[#385048]/20 bg-white px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
            />
            <div className="mt-3 flex items-center justify-between gap-3">
              <p className="text-xs text-[#385048]/55">
                A resposta também é salva ao sair do campo.
              </p>
              <button
                type="button"
                disabled={String(draft).trim() === '' || state?.status === 'saving'}
                onClick={() => handleOpenSave(item, perspective)}
                className="rounded-lg border border-[#385048]/15 bg-white px-3 py-2 text-xs font-semibold disabled:opacity-45"
              >
                Salvar resposta
              </button>
            </div>
          </div>
        )}
      </div>
    )
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

  if (completion) {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-12 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#A8C8B8]/45 bg-white p-8 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Participação concluída
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            Suas respostas foram finalizadas
          </h1>
          <p className="mt-4 leading-7 text-[#385048]/70">
            {completion.ambos_concluidos
              ? 'Os dois participantes concluíram a avaliação. Ela agora está pronta para a etapa de comparação e resultados.'
              : 'Sua parte foi concluída com sucesso. A avaliação ficará aguardando a conclusão do outro participante.'}
          </p>

          <div className="mt-7 rounded-2xl bg-[#A8C8B8]/12 p-5 text-sm">
            <p>
              <strong>Status da sua participação:</strong> CONCLUÍDO
            </p>
            <p className="mt-2">
              <strong>Status da avaliação:</strong>{' '}
              {completion.aplicacao_status}
            </p>
          </div>

          <p className="mt-6 text-sm leading-6 text-[#385048]/60">
            Depois de concluir, este acesso não permite alterar as respostas.
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
    const progress = questionnaire.progresso || {
      respondidas: 0,
      total: questionnaire.total_itens * 2,
      percentual: 0,
    }
    const isComplete = progress.respondidas === progress.total
    const hasSaving = Object.values(saveStates).some(
      (state) => state?.status === 'saving',
    )

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
              Preenchimento individual
            </p>
            <h1 className="mt-3 text-3xl font-semibold">
              Responda nas duas perspectivas
            </h1>
            <p className="mt-4 leading-7 text-[#385048]/70">
              Em cada item, responda primeiro sobre você e depois sobre a outra
              pessoa. Suas respostas são salvas progressivamente e podem ser
              retomadas por este mesmo link.
            </p>

            <div className="mt-6">
              <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                <span className="font-semibold">
                  Progresso: {progress.respondidas} de {progress.total}{' '}
                  respostas
                </span>
                <span className="text-[#385048]/65">
                  {progress.percentual}%
                </span>
              </div>
              <div className="mt-2 h-2 overflow-hidden rounded-full bg-[#A8C8B8]/20">
                <div
                  className="h-full rounded-full bg-[#385048]"
                  style={{
                    width: `${Math.min(100, progress.percentual)}%`,
                  }}
                />
              </div>
            </div>

            <div className="mt-6 flex flex-wrap gap-3 text-sm">
              <span className="rounded-full bg-[#A8C8B8]/20 px-4 py-2 font-semibold">
                {questionnaire.total_itens} item(ns)
              </span>
              <span className="rounded-full bg-[#A8C8D0]/25 px-4 py-2 font-semibold">
                {questionnaire.secoes.length} seção(ões)
              </span>
            </div>
          </div>

          {questionnaireNotice ? (
            <div className="mt-5 rounded-2xl bg-[#D8B078]/15 px-5 py-4 text-sm leading-6">
              {questionnaireNotice}
            </div>
          ) : null}

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

                <div className="mt-6 space-y-5">
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
                      </div>

                      <p className="mt-3 font-medium leading-7">
                        {item.texto}
                      </p>

                      {item.permite_nao_se_aplica ? (
                        <div className="mt-4">
                          <button
                            type="button"
                            disabled={excludingItemId === item.id}
                            onClick={() => handleNotApplicable(item)}
                            className="rounded-xl border border-[#D8B078]/65 bg-[#D8B078]/10 px-4 py-2 text-sm font-semibold transition hover:bg-[#D8B078]/18 disabled:opacity-60"
                          >
                            {excludingItemId === item.id
                              ? 'Marcando...'
                              : 'Não se aplica'}
                          </button>
                          <p className="mt-2 text-xs leading-5 text-[#385048]/55">
                            Esta opção remove o item desta avaliação para os dois
                            participantes e também do cálculo final.
                          </p>
                        </div>
                      ) : null}

                      <div className="mt-5 grid gap-4 lg:grid-cols-2">
                        {renderAnswerControl(
                          item,
                          'sobre_mim',
                          'Sobre mim',
                        )}
                        {renderAnswerControl(
                          item,
                          'sobre_outro',
                          'Sobre a outra pessoa',
                        )}
                      </div>
                    </article>
                  ))}
                </div>
              </section>
            ))}
          </div>

          <div className="mt-7 rounded-2xl border border-[#A8C8D0]/45 bg-[#A8C8D0]/12 p-5 text-sm leading-6">
            Você pode interromper o preenchimento e retornar depois pelo mesmo
            link. As respostas já salvas serão carregadas novamente.
          </div>

          <div className="mt-6 rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <h2 className="text-xl font-semibold">Concluir participação</h2>
            <p className="mt-3 text-sm leading-6 text-[#385048]/65">
              A conclusão só é liberada quando todas as duas perspectivas dos
              itens válidos estiverem respondidas. Depois de concluir, as
              respostas não poderão mais ser alteradas por este link.
            </p>

            <button
              type="button"
              disabled={!isComplete || hasSaving || excludingItemId !== null}
              onClick={handleComplete}
              className="mt-5 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-45"
            >
              {isComplete
                ? 'Concluir minha participação'
                : `Faltam ${Math.max(0, progress.total - progress.respondidas)} resposta(s)`}
            </button>
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
