import { useEffect, useMemo, useState } from 'react'
import {
  createRelationship,
  deleteRelationship,
  listPeople,
  listRelationships,
  updateRelationship,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  pessoa_a_id: '',
  pessoa_b_id: '',
  tipo: '',
  descricao_tipo: '',
  duracao_texto: '',
  status: 'ATIVO',
}

export default function ProfessionalRelationships() {
  const [people, setPeople] = useState([])
  const [relationships, setRelationships] = useState([])
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
      const [peopleResult, relationshipsResult] = await Promise.all([
        listPeople(),
        listRelationships(),
      ])

      setPeople(peopleResult.pessoas || [])
      setRelationships(relationshipsResult.vinculos || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar os vínculos.')
    }
  }

  const peopleById = useMemo(
    () => new Map(people.map((person) => [person.id, person])),
    [people],
  )

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function beginEdit(relationship) {
    setEditingId(relationship.id)
    setForm({
      pessoa_a_id: String(relationship.pessoa_a_id),
      pessoa_b_id: String(relationship.pessoa_b_id),
      tipo: relationship.tipo || '',
      descricao_tipo: relationship.descricao_tipo || '',
      duracao_texto: relationship.duracao_texto || '',
      status: relationship.status || 'ATIVO',
    })
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setForm(emptyForm)
    setMessage('')
  }

  async function refreshRelationships() {
    const result = await listRelationships()
    setRelationships(result.vinculos || [])
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setMessage('')

    if (!editingId && form.pessoa_a_id === form.pessoa_b_id) {
      setMessage('Selecione duas pessoas diferentes para o vínculo.')
      return
    }

    setStatus('saving')

    try {
      if (editingId) {
        await updateRelationship(editingId, {
          tipo: form.tipo,
          descricao_tipo: form.descricao_tipo,
          duracao_texto: form.duracao_texto,
          status: form.status,
        })
        setMessage('Vínculo atualizado com sucesso.')
      } else {
        await createRelationship({
          pessoa_a_id: Number(form.pessoa_a_id),
          pessoa_b_id: Number(form.pessoa_b_id),
          tipo: form.tipo,
          descricao_tipo: form.descricao_tipo,
          duracao_texto: form.duracao_texto,
          status: form.status,
        })
        setMessage('Vínculo criado com sucesso.')
      }

      setEditingId(null)
      setForm(emptyForm)
      await refreshRelationships()
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar o vínculo.')
    }
  }

  async function handleDelete(relationship) {
    const confirmed = window.confirm(
      `Excluir o vínculo entre "${relationship.pessoa_a_nome}" e "${relationship.pessoa_b_nome}"? Vínculos já usados em aplicações devem ser mantidos e marcados como inativos.`,
    )

    if (!confirmed) return

    setMessage('')

    try {
      await deleteRelationship(relationship.id)
      await refreshRelationships()

      if (editingId === relationship.id) {
        setEditingId(null)
        setForm(emptyForm)
      }

      setMessage('Vínculo excluído com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir o vínculo.')
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  const selectedA = peopleById.get(Number(form.pessoa_a_id))
  const selectedB = peopleById.get(Number(form.pessoa_b_id))

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/profissional')}
            className="text-left"
          >
            <p className="text-lg font-semibold">Vínculos</p>
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
            {editingId ? 'Editar vínculo' : 'Novo vínculo'}
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Atualizar vínculo' : 'Relacionar duas pessoas'}
          </h1>

          {people.length < 2 && !editingId ? (
            <div className="mt-5 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm">
              Cadastre pelo menos duas pessoas antes de criar um vínculo.
            </div>
          ) : null}

          <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
            {editingId ? (
              <div className="rounded-2xl bg-[#A8C8B8]/12 p-5 text-sm">
                <p className="font-semibold">Lados operacionais preservados</p>
                <p className="mt-2">
                  Lado A: {selectedA?.nome || 'Pessoa'}
                </p>
                <p className="mt-1">
                  Lado B: {selectedB?.nome || 'Pessoa'}
                </p>
                <p className="mt-3 text-xs text-[#385048]/60">
                  As pessoas não são trocadas durante a edição para manter a
                  ordem A/B estável no histórico do vínculo.
                </p>
              </div>
            ) : (
              <>
                <label className="block">
                  <span className="text-sm font-medium">Pessoa — lado A</span>
                  <select
                    required
                    value={form.pessoa_a_id}
                    onChange={(event) =>
                      updateField('pessoa_a_id', event.target.value)
                    }
                    className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                  >
                    <option value="">Selecione uma pessoa</option>
                    {people.map((person) => (
                      <option key={person.id} value={person.id}>
                        {person.nome} {person.status === 'INATIVO' ? '(inativo)' : ''}
                      </option>
                    ))}
                  </select>
                </label>

                <label className="block">
                  <span className="text-sm font-medium">Pessoa — lado B</span>
                  <select
                    required
                    value={form.pessoa_b_id}
                    onChange={(event) =>
                      updateField('pessoa_b_id', event.target.value)
                    }
                    className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                  >
                    <option value="">Selecione uma pessoa</option>
                    {people.map((person) => (
                      <option key={person.id} value={person.id}>
                        {person.nome} {person.status === 'INATIVO' ? '(inativo)' : ''}
                      </option>
                    ))}
                  </select>
                </label>
              </>
            )}

            <label className="block">
              <span className="text-sm font-medium">Tipo de vínculo</span>
              <input
                type="text"
                required
                maxLength="50"
                list="relationship-types"
                placeholder="Ex.: CASAL"
                value={form.tipo}
                onChange={(event) => updateField('tipo', event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
              <datalist id="relationship-types">
                <option value="CASAL" />
                <option value="PAIS_E_FILHOS" />
                <option value="AMIZADE" />
                <option value="FAMILIAR" />
                <option value="PROFISSIONAL" />
                <option value="LIDERANCA" />
                <option value="OUTRO" />
              </datalist>
              <span className="mt-2 block text-xs text-[#385048]/55">
                O campo é aberto. Para um tipo personalizado, use OUTRO e
                descreva abaixo.
              </span>
            </label>

            <label className="block">
              <span className="text-sm font-medium">
                Descrição do tipo
              </span>
              <input
                type="text"
                maxLength="150"
                placeholder="Obrigatória quando o tipo for OUTRO"
                value={form.descricao_tipo}
                onChange={(event) =>
                  updateField('descricao_tipo', event.target.value)
                }
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">
                Duração do vínculo
              </span>
              <input
                type="text"
                maxLength="100"
                placeholder="Ex.: 12 anos"
                value={form.duracao_texto}
                onChange={(event) =>
                  updateField('duracao_texto', event.target.value)
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
                disabled={status === 'saving' || (!editingId && people.length < 2)}
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
              >
                {status === 'saving'
                  ? 'Salvando...'
                  : editingId
                    ? 'Salvar alterações'
                    : 'Criar vínculo'}
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
              Relações
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Vínculos cadastrados
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando vínculos...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' &&
          status !== 'error' &&
          relationships.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhum vínculo cadastrado ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {relationships.map((relationship) => (
              <article
                key={relationship.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        {relationship.status}
                      </span>
                      <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                        {relationship.tipo}
                      </span>
                    </div>

                    <h3 className="mt-3 text-lg font-semibold">
                      {relationship.pessoa_a_nome}
                      {' ↔ '}
                      {relationship.pessoa_b_nome}
                    </h3>

                    <p className="mt-2 text-xs text-[#385048]/55">
                      Lado A: {relationship.pessoa_a_nome} · Lado B:{' '}
                      {relationship.pessoa_b_nome}
                    </p>

                    {relationship.descricao_tipo ? (
                      <p className="mt-3 text-sm text-[#385048]/65">
                        {relationship.descricao_tipo}
                      </p>
                    ) : null}

                    {relationship.duracao_texto ? (
                      <p className="mt-2 text-sm text-[#385048]/65">
                        Duração: {relationship.duracao_texto}
                      </p>
                    ) : null}

                    <p className="mt-3 text-xs text-[#385048]/50">
                      {relationship.total_aplicacoes} aplicação(ões)
                    </p>
                  </div>

                  <div className="flex shrink-0 flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => beginEdit(relationship)}
                      className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(relationship)}
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
