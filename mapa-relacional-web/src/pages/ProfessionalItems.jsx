import { useEffect, useState } from 'react'
import {
  createItem,
  deleteItem,
  getInstrument,
  listItems,
  updateItem,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  texto: '',
  tipo_resposta: '',
  ordem: '',
  permite_nao_se_aplica: false,
  ativo: true,
}

export default function ProfessionalItems({
  instrumentId,
  versionId,
  sectionId,
}) {
  const [instrument, setInstrument] = useState(null)
  const [version, setVersion] = useState(null)
  const [section, setSection] = useState(null)
  const [items, setItems] = useState([])
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
  }, [instrumentId, versionId, sectionId])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const [instrumentResult, itemsResult] = await Promise.all([
        getInstrument(instrumentId),
        listItems(instrumentId, versionId, sectionId),
      ])

      setInstrument(instrumentResult.instrumento)
      setVersion(itemsResult.versao)
      setSection(itemsResult.secao)
      setItems(itemsResult.itens || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar os itens.')
    }
  }

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function beginEdit(item) {
    if (version?.status !== 'RASCUNHO') return

    setEditingId(item.id)
    setForm({
      texto: item.texto || '',
      tipo_resposta: item.tipo_resposta || '',
      ordem: String(item.ordem),
      permite_nao_se_aplica: Boolean(item.permite_nao_se_aplica),
      ativo: Boolean(item.ativo),
    })
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setForm(emptyForm)
    setMessage('')
  }

  async function refreshItems() {
    const result = await listItems(instrumentId, versionId, sectionId)
    setVersion(result.versao)
    setSection(result.secao)
    setItems(result.itens || [])
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    const payload = {
      texto: form.texto,
      tipo_resposta: form.tipo_resposta,
      permite_nao_se_aplica: form.permite_nao_se_aplica,
      ativo: form.ativo,
      ...(form.ordem === '' ? {} : { ordem: Number(form.ordem) }),
    }

    try {
      if (editingId) {
        await updateItem(
          instrumentId,
          versionId,
          sectionId,
          editingId,
          payload,
        )
        setMessage('Item atualizado com sucesso.')
      } else {
        await createItem(instrumentId, versionId, sectionId, payload)
        setMessage('Item criado com sucesso.')
      }

      setEditingId(null)
      setForm(emptyForm)
      await refreshItems()
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar o item.')
    }
  }

  async function handleDelete(item) {
    const confirmed = window.confirm(
      `Excluir o item "${item.codigo}"? A exclusão só é permitida quando não houver alternativas ou respostas vinculadas.`,
    )

    if (!confirmed) return

    setMessage('')

    try {
      await deleteItem(instrumentId, versionId, sectionId, item.id)
      await refreshItems()

      if (editingId === item.id) {
        setEditingId(null)
        setForm(emptyForm)
      }

      setMessage('Item excluído com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir o item.')
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  const isDraft = version?.status === 'RASCUNHO'
  const backPath =
    `/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes`

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate(backPath)}
            className="text-left"
          >
            <p className="text-lg font-semibold">Itens da seção</p>
            <p className="text-xs text-[#385048]/65">
              {instrument?.nome || 'Instrumento'} · versão{' '}
              {version?.numero_versao || '...'} ·{' '}
              {section?.titulo || 'Seção'}
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
            Perguntas da seção
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Editar item' : 'Novo item'}
          </h1>

          {!isDraft && version ? (
            <div className="mt-5 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm">
              Esta versão está {version.status}. Os itens são somente leitura.
            </div>
          ) : null}

          {isDraft ? (
            <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
              <label className="block">
                <span className="text-sm font-medium">Código</span>
                <input
                  type="text"
                  readOnly
                  value={
                    editingId
                      ? items.find((item) => item.id === editingId)?.codigo || ''
                      : 'Gerado automaticamente ao salvar'
                  }
                  className="mt-2 w-full cursor-not-allowed rounded-xl border border-[#385048]/15 bg-[#385048]/5 px-4 py-3 text-[#385048]/65 outline-none"
                />
                <span className="mt-2 block text-xs text-[#385048]/55">
                  O sistema gera um código único automaticamente, como ITEM_001.
                </span>
              </label>

              <label className="block">
                <span className="text-sm font-medium">Texto / pergunta</span>
                <textarea
                  rows="5"
                  required
                  value={form.texto}
                  onChange={(event) =>
                    updateField('texto', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Tipo de resposta</span>
                <select
                  required
                  value={form.tipo_resposta}
                  onChange={(event) =>
                    updateField('tipo_resposta', event.target.value)
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                >
                  <option value="">Selecione</option>
                  <option value="ESCOLHA_UNICA">Escolha única</option>
                  <option value="NUMERICO">Numérico</option>
                  <option value="TEXTO">Texto livre</option>
                </select>
                <span className="mt-2 block text-xs text-[#385048]/55">
                  Escolha única usa alternativas cadastradas. Numérico abre um
                  campo de número. Texto livre abre um campo de texto.
                </span>
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
                    Se deixar vazio, o item será colocado ao final.
                  </span>
                ) : null}
              </label>

              <div className="space-y-3">
                <label className="flex items-center gap-3">
                  <input
                    type="checkbox"
                    checked={form.permite_nao_se_aplica}
                    onChange={(event) =>
                      updateField(
                        'permite_nao_se_aplica',
                        event.target.checked,
                      )
                    }
                  />
                  <span className="text-sm font-medium">
                    Permitir “Não se aplica”
                  </span>
                </label>

                <label className="flex items-center gap-3">
                  <input
                    type="checkbox"
                    checked={form.ativo}
                    onChange={(event) =>
                      updateField('ativo', event.target.checked)
                    }
                  />
                  <span className="text-sm font-medium">Item ativo</span>
                </label>
              </div>

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
                      : 'Criar item'}
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
              Conteúdo
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Itens cadastrados
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando itens...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' &&
          status !== 'error' &&
          items.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhum item cadastrado ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {items.map((item) => (
              <article
                key={item.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                        {item.codigo}
                      </span>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        Ordem {item.ordem}
                      </span>
                      <span className="rounded-full bg-[#D8B078]/18 px-3 py-1 text-xs font-semibold">
                        {item.tipo_resposta}
                      </span>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        {item.ativo ? 'ATIVO' : 'INATIVO'}
                      </span>
                    </div>

                    <p className="mt-3 text-sm leading-6">
                      {item.texto}
                    </p>

                    <p className="mt-3 text-xs text-[#385048]/55">
                      Não se aplica:{' '}
                      {item.permite_nao_se_aplica ? 'permitido' : 'não permitido'}
                      {' · '}
                      {item.total_alternativas} alternativa(s)
                    </p>
                  </div>

                  <div className="flex shrink-0 flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() =>
                        navigate(
                          `/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${item.id}/alternativas`,
                        )
                      }
                      className="rounded-xl border border-[#A8C8D0] px-4 py-2 text-sm font-semibold"
                    >
                      Alternativas
                    </button>

                    {isDraft ? (
                      <>
                        <button
                          type="button"
                          onClick={() => beginEdit(item)}
                          className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                        >
                          Editar
                        </button>
                        <button
                          type="button"
                          onClick={() => handleDelete(item)}
                          className="rounded-xl border border-[#D8B078]/60 px-4 py-2 text-sm font-semibold"
                        >
                          Excluir
                        </button>
                      </>
                    ) : null}
                  </div>
                </div>
              </article>
            ))}
          </div>
        </section>
      </main>
    </div>
  )
}
