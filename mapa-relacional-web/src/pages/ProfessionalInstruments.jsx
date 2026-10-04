import { useEffect, useState } from 'react'
import {
  createInstrument,
  deleteInstrument,
  listInstruments,
  updateInstrument,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  nome: '',
  descricao: '',
  status: 'RASCUNHO',
}

export default function ProfessionalInstruments() {
  const [instruments, setInstruments] = useState([])
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
  }, [])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const result = await listInstruments()
      setInstruments(result.instrumentos || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar os instrumentos.')
    }
  }

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function beginEdit(instrument) {
    setEditingId(instrument.id)
    setForm({
      nome: instrument.nome || '',
      descricao: instrument.descricao || '',
      status: instrument.status || 'RASCUNHO',
    })
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setForm(emptyForm)
    setMessage('')
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    try {
      const payload = {
        nome: form.nome,
        descricao: form.descricao,
        status: form.status,
      }

      if (editingId) {
        await updateInstrument(editingId, payload)
        setMessage('Instrumento atualizado com sucesso.')
      } else {
        await createInstrument(payload)
        setMessage('Instrumento criado com sucesso.')
      }

      setEditingId(null)
      setForm(emptyForm)

      const result = await listInstruments()
      setInstruments(result.instrumentos || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar o instrumento.')
    }
  }

  async function handleDelete(instrument) {
    const confirmed = window.confirm(
      `Excluir o instrumento "${instrument.nome}"? Esta ação só é permitida quando ele ainda não possui versões.`,
    )

    if (!confirmed) return

    setMessage('')

    try {
      await deleteInstrument(instrument.id)
      setInstruments((current) =>
        current.filter((item) => item.id !== instrument.id),
      )

      if (editingId === instrument.id) {
        cancelEdit()
      }

      setMessage('Instrumento excluído com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir o instrumento.')
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/profissional')}
            className="text-left"
          >
            <p className="text-lg font-semibold">Instrumentos</p>
            <p className="text-xs text-[#385048]/65">
              Avaliação de Percepção Relacional
            </p>
          </button>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => navigate('/profissional')}
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
            {editingId ? 'Editar instrumento' : 'Novo instrumento'}
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Atualizar cadastro' : 'Criar instrumento'}
          </h1>

          <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
            <label className="block">
              <span className="text-sm font-medium">Nome</span>
              <input
                type="text"
                required
                maxLength="180"
                value={form.nome}
                onChange={(event) => updateField('nome', event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">Descrição</span>
              <textarea
                rows="5"
                value={form.descricao}
                onChange={(event) =>
                  updateField('descricao', event.target.value)
                }
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">Status</span>
              <select
                value={form.status}
                onChange={(event) => updateField('status', event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              >
                <option value="RASCUNHO">Rascunho</option>
                <option value="ATIVO">Ativo</option>
                <option value="ARQUIVADO">Arquivado</option>
              </select>
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
                    : 'Criar instrumento'}
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
        </section>

        <section>
          <div className="mb-5">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Catálogo
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Instrumentos cadastrados
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando instrumentos...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' && status !== 'error' && instruments.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhum instrumento cadastrado ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {instruments.map((instrument) => (
              <article
                key={instrument.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="text-lg font-semibold">{instrument.nome}</h3>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        {instrument.status}
                      </span>
                    </div>
                    <p className="mt-2 text-sm leading-6 text-[#385048]/65">
                      {instrument.descricao || 'Sem descrição.'}
                    </p>
                    <p className="mt-3 text-xs text-[#385048]/50">
                      {instrument.total_versoes} versão(ões)
                    </p>
                  </div>

                  <div className="flex shrink-0 flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => beginEdit(instrument)}
                      className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(instrument)}
                      className="rounded-xl border border-[#D8B078]/60 px-4 py-2 text-sm font-semibold"
                    >
                      Excluir
                    </button>
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
