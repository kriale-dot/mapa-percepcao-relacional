import { useEffect, useState } from 'react'
import {
  createSection,
  deleteSection,
  getInstrument,
  listSections,
  updateSection,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  titulo: '',
  descricao: '',
  ordem: '',
  ativo: true,
}

export default function ProfessionalSections({ instrumentId, versionId }) {
  const [instrument, setInstrument] = useState(null)
  const [version, setVersion] = useState(null)
  const [sections, setSections] = useState([])
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
  }, [instrumentId, versionId])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const [instrumentResult, sectionsResult] = await Promise.all([
        getInstrument(instrumentId),
        listSections(instrumentId, versionId),
      ])

      setInstrument(instrumentResult.instrumento)
      setVersion(sectionsResult.versao)
      setSections(sectionsResult.secoes || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar as seções.')
    }
  }

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function beginEdit(section) {
    if (version?.status !== 'RASCUNHO') return

    setEditingId(section.id)
    setForm({
      titulo: section.titulo || '',
      descricao: section.descricao || '',
      ordem: String(section.ordem),
      ativo: Boolean(section.ativo),
    })
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setForm(emptyForm)
    setMessage('')
  }

  async function refreshSections() {
    const result = await listSections(instrumentId, versionId)
    setVersion(result.versao)
    setSections(result.secoes || [])
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    const payload = {
      titulo: form.titulo,
      descricao: form.descricao,
      ativo: form.ativo,
      ...(form.ordem === '' ? {} : { ordem: Number(form.ordem) }),
    }

    try {
      if (editingId) {
        await updateSection(
          instrumentId,
          versionId,
          editingId,
          payload,
        )
        setMessage('Seção atualizada com sucesso.')
      } else {
        await createSection(instrumentId, versionId, payload)
        setMessage('Seção criada com sucesso.')
      }

      setEditingId(null)
      setForm(emptyForm)
      await refreshSections()
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar a seção.')
    }
  }

  async function handleDelete(section) {
    const confirmed = window.confirm(
      `Excluir a seção "${section.titulo}"? A exclusão só é permitida enquanto ela não possuir itens.`,
    )

    if (!confirmed) return

    setMessage('')

    try {
      await deleteSection(instrumentId, versionId, section.id)
      await refreshSections()

      if (editingId === section.id) {
        setEditingId(null)
        setForm(emptyForm)
      }

      setMessage('Seção excluída com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir a seção.')
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  const isDraft = version?.status === 'RASCUNHO'

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() =>
              navigate(
                `/profissional/instrumentos/${instrumentId}/versoes`,
              )
            }
            className="text-left"
          >
            <p className="text-lg font-semibold">Seções da versão</p>
            <p className="text-xs text-[#385048]/65">
              {instrument?.nome || 'Instrumento'} · versão{' '}
              {version?.numero_versao || '...'}
            </p>
          </button>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() =>
                navigate(
                  `/profissional/instrumentos/${instrumentId}/versoes`,
                )
              }
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

      <main className="mx-auto grid max-w-6xl gap-7 px-6 py-10 lg:grid-cols-[0.8fr_1.2fr]">
        <section className="h-fit rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Estrutura da versão
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Editar seção' : 'Nova seção'}
          </h1>

          {!isDraft && version ? (
            <div className="mt-5 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm">
              Esta versão está {version.status}. A estrutura é somente leitura.
            </div>
          ) : null}

          {isDraft ? (
            <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
              <label className="block">
                <span className="text-sm font-medium">Título</span>
                <input
                  type="text"
                  required
                  maxLength="180"
                  value={form.titulo}
                  onChange={(event) =>
                    updateField('titulo', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Descrição</span>
                <textarea
                  rows="4"
                  value={form.descricao}
                  onChange={(event) =>
                    updateField('descricao', event.target.value)
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
                    Se deixar vazio, a seção será colocada ao final.
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
                <span className="text-sm font-medium">Seção ativa</span>
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
                      : 'Criar seção'}
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
              Tópicos
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Seções cadastradas
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando seções...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' &&
          status !== 'error' &&
          sections.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhuma seção cadastrada ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {sections.map((section) => (
              <article
                key={section.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                        Ordem {section.ordem}
                      </span>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        {section.ativo ? 'ATIVA' : 'INATIVA'}
                      </span>
                    </div>

                    <h3 className="mt-3 text-lg font-semibold">
                      {section.titulo}
                    </h3>
                    <p className="mt-2 text-sm leading-6 text-[#385048]/65">
                      {section.descricao || 'Sem descrição.'}
                    </p>
                    <p className="mt-3 text-xs text-[#385048]/50">
                      {section.total_itens} item(ns)
                    </p>
                  </div>

                  {isDraft ? (
                    <div className="flex shrink-0 flex-wrap gap-2">
                      <button
                        type="button"
                        onClick={() => beginEdit(section)}
                        className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                      >
                        Editar
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(section)}
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
