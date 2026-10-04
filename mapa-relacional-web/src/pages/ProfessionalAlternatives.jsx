import { useEffect, useState } from 'react'
import {
  createAlternative,
  deleteAlternative,
  getInstrument,
  listAlternatives,
  updateAlternative,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  valor: '',
  rotulo: '',
  ordem: '',
  ativo: true,
}

export default function ProfessionalAlternatives({
  instrumentId,
  versionId,
  sectionId,
  itemId,
}) {
  const [instrument, setInstrument] = useState(null)
  const [version, setVersion] = useState(null)
  const [section, setSection] = useState(null)
  const [item, setItem] = useState(null)
  const [alternatives, setAlternatives] = useState([])
  const [form, setForm] = useState(emptyForm)
  const [editingId, setEditingId] = useState(null)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load()
  }, [instrumentId, versionId, sectionId, itemId])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const [instrumentResult, alternativesResult] = await Promise.all([
        getInstrument(instrumentId),
        listAlternatives(instrumentId, versionId, sectionId, itemId),
      ])

      setInstrument(instrumentResult.instrumento)
      setVersion(alternativesResult.versao)
      setSection(alternativesResult.secao)
      setItem(alternativesResult.item)
      setAlternatives(alternativesResult.alternativas || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar as alternativas.',
      )
    }
  }

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function beginEdit(alternative) {
    if (version?.status !== 'RASCUNHO') return

    setEditingId(alternative.id)
    setForm({
      valor: alternative.valor || '',
      rotulo: alternative.rotulo || '',
      ordem: String(alternative.ordem),
      ativo: Boolean(alternative.ativo),
    })
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setForm(emptyForm)
    setMessage('')
  }

  async function refreshAlternatives() {
    const result = await listAlternatives(
      instrumentId,
      versionId,
      sectionId,
      itemId,
    )

    setVersion(result.versao)
    setSection(result.secao)
    setItem(result.item)
    setAlternatives(result.alternativas || [])
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    const payload = {
      valor: form.valor,
      rotulo: form.rotulo,
      ativo: form.ativo,
      ...(form.ordem === '' ? {} : { ordem: Number(form.ordem) }),
    }

    try {
      if (editingId) {
        await updateAlternative(
          instrumentId,
          versionId,
          sectionId,
          itemId,
          editingId,
          payload,
        )
        setMessage('Alternativa atualizada com sucesso.')
      } else {
        await createAlternative(
          instrumentId,
          versionId,
          sectionId,
          itemId,
          payload,
        )
        setMessage('Alternativa criada com sucesso.')
      }

      setEditingId(null)
      setForm(emptyForm)
      await refreshAlternatives()
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(
        error.message || 'Não foi possível salvar a alternativa.',
      )
    }
  }

  async function handleDelete(alternative) {
    const confirmed = window.confirm(
      `Excluir a alternativa "${alternative.rotulo}"? A exclusão só é permitida quando ela não possui respostas vinculadas.`,
    )

    if (!confirmed) return

    setMessage('')

    try {
      await deleteAlternative(
        instrumentId,
        versionId,
        sectionId,
        itemId,
        alternative.id,
      )
      await refreshAlternatives()

      if (editingId === alternative.id) {
        setEditingId(null)
        setForm(emptyForm)
      }

      setMessage('Alternativa excluída com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(
        error.message || 'Não foi possível excluir a alternativa.',
      )
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  const isDraft = version?.status === 'RASCUNHO'
  const backPath =
    `/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens`

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate(backPath)}
            className="text-left"
          >
            <p className="text-lg font-semibold">Alternativas do item</p>
            <p className="text-xs text-[#385048]/65">
              {instrument?.nome || 'Instrumento'} · versão{' '}
              {version?.numero_versao || '...'} ·{' '}
              {section?.titulo || 'Seção'} · {item?.codigo || 'Item'}
            </p>
          </button>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => navigate(backPath)}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Voltar
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

      <main className="mx-auto grid max-w-6xl gap-7 px-6 py-10 lg:grid-cols-[0.85fr_1.15fr]">
        <section className="h-fit rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Opções de resposta
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Editar alternativa' : 'Nova alternativa'}
          </h1>

          <p className="mt-3 text-sm leading-6 text-[#385048]/70">
            Use alternativas quando este item tiver respostas fechadas. O valor
            é o identificador armazenado e o rótulo é o texto exibido ao
            participante.
          </p>

          {item ? (
            <div className="mt-5 rounded-xl bg-[#A8C8B8]/12 px-4 py-3 text-sm">
              <strong>{item.codigo}</strong> — {item.texto}
            </div>
          ) : null}

          {!isDraft && version ? (
            <div className="mt-5 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm">
              Esta versão está {version.status}. As alternativas são somente
              leitura.
            </div>
          ) : null}

          {isDraft ? (
            <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
              <label className="block">
                <span className="text-sm font-medium">Valor</span>
                <input
                  type="text"
                  required
                  maxLength="100"
                  placeholder="Ex.: 1"
                  value={form.valor}
                  onChange={(event) =>
                    updateField('valor', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
                <span className="mt-2 block text-xs text-[#385048]/55">
                  O valor deve ser único dentro deste item.
                </span>
              </label>

              <label className="block">
                <span className="text-sm font-medium">Rótulo</span>
                <input
                  type="text"
                  required
                  maxLength="255"
                  placeholder="Ex.: Nunca"
                  value={form.rotulo}
                  onChange={(event) =>
                    updateField('rotulo', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Ordem</span>
                <input
                  type="number"
                  min="0"
                  step="1"
                  placeholder="Automática ao criar"
                  value={form.ordem}
                  onChange={(event) =>
                    updateField('ordem', event.target.value)
                  }
                  required={Boolean(editingId)}
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
                {!editingId ? (
                  <span className="mt-2 block text-xs text-[#385048]/55">
                    Se deixar vazio, a alternativa será colocada ao final.
                  </span>
                ) : null}
              </label>

              <label className="flex items-center gap-3">
                <input
                  type="checkbox"
                  checked={form.ativo}
                  onChange={(event) =>
                    updateField('ativo', event.target.checked)
                  }
                />
                <span className="text-sm font-medium">
                  Alternativa ativa
                </span>
              </label>

              {message ? (
                <div
                  role="alert"
                  className="rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm"
                >
                  {message}
                </div>
              ) : null}

              <div className="flex flex-wrap gap-3">
                <button
                  type="submit"
                  disabled={status === 'saving'}
                  className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
                >
                  {status === 'saving'
                    ? 'Salvando...'
                    : editingId
                      ? 'Salvar alterações'
                      : 'Criar alternativa'}
                </button>

                {editingId ? (
                  <button
                    type="button"
                    onClick={cancelEdit}
                    className="rounded-xl border border-[#385048]/20 px-5 py-3 text-sm font-semibold"
                  >
                    Cancelar
                  </button>
                ) : null}
              </div>
            </form>
          ) : null}

          {!isDraft && message ? (
            <div className="mt-5 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm">
              {message}
            </div>
          ) : null}
        </section>

        <section>
          <div className="mb-5">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Respostas fechadas
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Alternativas cadastradas
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando alternativas...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' &&
          status !== 'error' &&
          alternatives.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhuma alternativa cadastrada para este item.
            </div>
          ) : null}

          <div className="space-y-4">
            {alternatives.map((alternative) => (
              <article
                key={alternative.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                        Valor {alternative.valor}
                      </span>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        Ordem {alternative.ordem}
                      </span>
                      <span className="rounded-full bg-[#D8B078]/18 px-3 py-1 text-xs font-semibold">
                        {alternative.ativo ? 'ATIVA' : 'INATIVA'}
                      </span>
                    </div>

                    <p className="mt-3 font-medium">
                      {alternative.rotulo}
                    </p>

                    <p className="mt-3 text-xs text-[#385048]/50">
                      {alternative.total_respostas} resposta(s) vinculada(s)
                    </p>
                  </div>

                  {isDraft ? (
                    <div className="flex shrink-0 flex-wrap gap-2">
                      <button
                        type="button"
                        onClick={() => beginEdit(alternative)}
                        className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                      >
                        Editar
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(alternative)}
                        className="rounded-xl border border-[#D8B078]/60 px-4 py-2 text-sm font-semibold"
                      >
                        Excluir
                      </button>
                    </div>
                  ) : null}
                </div>
              </article>
            ))}
          </div>
        </section>
      </main>
    </div>
  )
}
