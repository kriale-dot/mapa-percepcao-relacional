import { useEffect, useMemo, useState } from 'react'
import {
  createApplication,
  getApplicationOptions,
  listApplications,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyForm = {
  instrumento_versao_id: '',
  vinculo_id: '',
  email_contato: '',
  tipo_vinculo_snapshot: '',
  duracao_vinculo_texto: '',
}

export default function ProfessionalApplications() {
  const [applications, setApplications] = useState([])
  const [versions, setVersions] = useState([])
  const [relationships, setRelationships] = useState([])
  const [form, setForm] = useState(emptyForm)
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
      const [applicationsResult, optionsResult] = await Promise.all([
        listApplications(),
        getApplicationOptions(),
      ])

      setApplications(applicationsResult.aplicacoes || [])
      setVersions(optionsResult.versoes || [])
      setRelationships(optionsResult.vinculos || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar as avaliações.')
    }
  }

  const selectedRelationship = useMemo(
    () =>
      relationships.find(
        (relationship) => relationship.id === Number(form.vinculo_id),
      ) || null,
    [relationships, form.vinculo_id],
  )

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function handleRelationshipChange(value) {
    const relationship =
      relationships.find((item) => item.id === Number(value)) || null

    setForm((current) => ({
      ...current,
      vinculo_id: value,
      tipo_vinculo_snapshot: relationship?.tipo || '',
      duracao_vinculo_texto: relationship?.duracao_texto || '',
    }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    try {
      const payload = {
        instrumento_versao_id: Number(form.instrumento_versao_id),
        vinculo_id: form.vinculo_id ? Number(form.vinculo_id) : null,
        email_contato: form.email_contato,
        tipo_vinculo_snapshot: form.tipo_vinculo_snapshot,
        duracao_vinculo_texto: form.duracao_vinculo_texto,
      }

      await createApplication(payload)

      const result = await listApplications()
      setApplications(result.aplicacoes || [])
      setForm(emptyForm)
      setStatus('ready')
      setMessage('Avaliação criada em rascunho com os participantes A e B.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível criar a avaliação.')
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
            <p className="text-lg font-semibold">Avaliações</p>
            <p className="text-xs text-[#385048]/65">
              Aplicações da Avaliação de Percepção Relacional
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
            Nova aplicação
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            Criar avaliação
          </h1>

          <p className="mt-3 text-sm leading-6 text-[#385048]/70">
            A aplicação sempre usa uma versão publicada do instrumento e cria
            exatamente dois lados operacionais: A e B.
          </p>

          {versions.length === 0 ? (
            <div className="mt-5 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm">
              Não existe versão publicada disponível. Publique uma versão do
              instrumento antes de criar uma avaliação.
            </div>
          ) : null}

          <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
            <label className="block">
              <span className="text-sm font-medium">
                Instrumento / versão publicada
              </span>
              <select
                required
                value={form.instrumento_versao_id}
                onChange={(event) =>
                  updateField('instrumento_versao_id', event.target.value)
                }
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              >
                <option value="">Selecione uma versão</option>
                {versions.map((version) => (
                  <option key={version.id} value={version.id}>
                    {version.instrumento_nome} — versão {version.numero_versao}
                  </option>
                ))}
              </select>
            </label>

            <label className="block">
              <span className="text-sm font-medium">
                Vínculo existente
              </span>
              <select
                value={form.vinculo_id}
                onChange={(event) =>
                  handleRelationshipChange(event.target.value)
                }
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              >
                <option value="">Sem vínculo cadastrado</option>
                {relationships.map((relationship) => (
                  <option key={relationship.id} value={relationship.id}>
                    {relationship.pessoa_a_nome} ↔ {relationship.pessoa_b_nome}
                    {' — '}
                    {relationship.tipo}
                  </option>
                ))}
              </select>
            </label>

            {selectedRelationship ? (
              <div className="rounded-2xl bg-[#A8C8B8]/12 p-5 text-sm">
                <p>
                  <strong>Lado A:</strong>{' '}
                  {selectedRelationship.pessoa_a_nome}
                </p>
                <p className="mt-1">
                  <strong>Lado B:</strong>{' '}
                  {selectedRelationship.pessoa_b_nome}
                </p>
                <p className="mt-3 text-xs text-[#385048]/60">
                  O tipo e a duração atuais do vínculo serão copiados para a
                  aplicação como snapshot.
                </p>
              </div>
            ) : null}

            <label className="block">
              <span className="text-sm font-medium">E-mail de contato</span>
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

            {!selectedRelationship ? (
              <>
                <label className="block">
                  <span className="text-sm font-medium">
                    Tipo do vínculo
                  </span>
                  <input
                    type="text"
                    required
                    maxLength="50"
                    placeholder="Ex.: CASAL"
                    value={form.tipo_vinculo_snapshot}
                    onChange={(event) =>
                      updateField(
                        'tipo_vinculo_snapshot',
                        event.target.value,
                      )
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
                    placeholder="Ex.: 8 anos"
                    value={form.duracao_vinculo_texto}
                    onChange={(event) =>
                      updateField(
                        'duracao_vinculo_texto',
                        event.target.value,
                      )
                    }
                    className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
                  />
                </label>
              </>
            ) : null}

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
              disabled={status === 'saving' || versions.length === 0}
              className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
            >
              {status === 'saving' ? 'Criando...' : 'Criar avaliação'}
            </button>
          </form>
        </section>

        <section>
          <div className="mb-5">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Acompanhamento
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Avaliações cadastradas
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando avaliações...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' &&
          status !== 'error' &&
          applications.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhuma avaliação criada ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {applications.map((application) => (
              <article
                key={application.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-wrap items-center gap-2">
                  <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                    {application.status}
                  </span>
                  <span className="rounded-full bg-[#A8C8D0]/25 px-3 py-1 text-xs font-semibold">
                    {application.tipo_vinculo_snapshot}
                  </span>
                </div>

                <h3 className="mt-3 text-lg font-semibold">
                  {application.instrumento_nome} — versão{' '}
                  {application.numero_versao}
                </h3>

                <p className="mt-2 text-sm text-[#385048]/65">
                  Contato: {application.email_contato}
                </p>

                {application.duracao_vinculo_texto ? (
                  <p className="mt-1 text-sm text-[#385048]/65">
                    Duração: {application.duracao_vinculo_texto}
                  </p>
                ) : null}

                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                  <div className="rounded-2xl bg-[#A8C8B8]/12 p-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                      Participante A
                    </p>
                    <p className="mt-2 font-medium">
                      {application.participante_a?.nome_snapshot ||
                        'Identificação pendente'}
                    </p>
                    <p className="mt-1 text-xs text-[#385048]/60">
                      {application.participante_a?.status || 'PENDENTE'}
                    </p>
                  </div>

                  <div className="rounded-2xl bg-[#A8C8B8]/12 p-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                      Participante B
                    </p>
                    <p className="mt-2 font-medium">
                      {application.participante_b?.nome_snapshot ||
                        'Identificação pendente'}
                    </p>
                    <p className="mt-1 text-xs text-[#385048]/60">
                      {application.participante_b?.status || 'PENDENTE'}
                    </p>
                  </div>
                </div>

                <p className="mt-4 text-xs text-[#385048]/50">
                  Aplicação #{application.id}
                  {application.vinculo_id
                    ? ` · vínculo #${application.vinculo_id}`
                    : ' · sem vínculo prévio'}
                </p>
              </article>
            ))}
          </div>
        </section>
      </main>
    </div>
  )
}
