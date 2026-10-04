import { useEffect, useState } from 'react'
import {
  createPerson,
  deletePerson,
  listPeople,
  updatePerson,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  nome: '',
  email: '',
  telefone: '',
  data_nascimento: '',
  observacao_administrativa: '',
  status: 'ATIVO',
}

export default function ProfessionalPeople() {
  const [people, setPeople] = useState([])
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
      const result = await listPeople()
      setPeople(result.pessoas || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar as pessoas.')
    }
  }

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function beginEdit(person) {
    setEditingId(person.id)
    setForm({
      nome: person.nome || '',
      email: person.email || '',
      telefone: person.telefone || '',
      data_nascimento: person.data_nascimento || '',
      observacao_administrativa: person.observacao_administrativa || '',
      status: person.status || 'ATIVO',
    })
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setForm(emptyForm)
    setMessage('')
  }

  async function refreshPeople() {
    const result = await listPeople()
    setPeople(result.pessoas || [])
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    const payload = {
      nome: form.nome,
      email: form.email,
      telefone: form.telefone,
      data_nascimento: form.data_nascimento,
      observacao_administrativa: form.observacao_administrativa,
      status: form.status,
    }

    try {
      if (editingId) {
        await updatePerson(editingId, payload)
        setMessage('Pessoa atualizada com sucesso.')
      } else {
        await createPerson(payload)
        setMessage('Pessoa criada com sucesso.')
      }

      setEditingId(null)
      setForm(emptyForm)
      await refreshPeople()
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar a pessoa.')
    }
  }

  async function handleDelete(person) {
    const confirmed = window.confirm(
      `Excluir "${person.nome}"? Pessoas já usadas em vínculos ou aplicações devem ser mantidas e marcadas como inativas.`,
    )

    if (!confirmed) return

    setMessage('')

    try {
      await deletePerson(person.id)
      await refreshPeople()

      if (editingId === person.id) {
        setEditingId(null)
        setForm(emptyForm)
      }

      setMessage('Pessoa excluída com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir a pessoa.')
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
            <p className="text-lg font-semibold">Pessoas</p>
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

      <main className="mx-auto grid max-w-6xl gap-7 px-6 py-10 lg:grid-cols-[0.9fr_1.1fr]">
        <section className="h-fit rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            {editingId ? 'Editar pessoa' : 'Nova pessoa'}
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Atualizar cadastro' : 'Cadastrar pessoa'}
          </h1>

          <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
            <label className="block">
              <span className="text-sm font-medium">Nome</span>
              <input
                type="text"
                required
                maxLength="150"
                value={form.nome}
                onChange={(event) => updateField('nome', event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">E-mail</span>
              <input
                type="email"
                maxLength="190"
                value={form.email}
                onChange={(event) => updateField('email', event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">Telefone</span>
              <input
                type="text"
                maxLength="30"
                value={form.telefone}
                onChange={(event) =>
                  updateField('telefone', event.target.value)
                }
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">
                Data de nascimento
              </span>
              <input
                type="date"
                value={form.data_nascimento}
                onChange={(event) =>
                  updateField('data_nascimento', event.target.value)
                }
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">
                Observação administrativa
              </span>
              <textarea
                rows="4"
                value={form.observacao_administrativa}
                onChange={(event) =>
                  updateField(
                    'observacao_administrativa',
                    event.target.value,
                  )
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
                <option value="ATIVO">Ativo</option>
                <option value="INATIVO">Inativo</option>
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
                    : 'Cadastrar pessoa'}
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
              Cadastro
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Pessoas cadastradas
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando pessoas...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' &&
          status !== 'error' &&
          people.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhuma pessoa cadastrada ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {people.map((person) => (
              <article
                key={person.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="text-lg font-semibold">{person.nome}</h3>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        {person.status}
                      </span>
                    </div>

                    <div className="mt-3 space-y-1 text-sm text-[#385048]/65">
                      {person.email ? <p>{person.email}</p> : null}
                      {person.telefone ? <p>{person.telefone}</p> : null}
                      {person.data_nascimento ? (
                        <p>Nascimento: {person.data_nascimento}</p>
                      ) : null}
                    </div>

                    {person.observacao_administrativa ? (
                      <p className="mt-3 text-sm leading-6 text-[#385048]/65">
                        {person.observacao_administrativa}
                      </p>
                    ) : null}

                    <p className="mt-3 text-xs text-[#385048]/50">
                      {person.total_vinculos} vínculo(s) ·{' '}
                      {person.total_aplicacoes} aplicação(ões)
                    </p>
                  </div>

                  <div className="flex shrink-0 flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => beginEdit(person)}
                      className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(person)}
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
