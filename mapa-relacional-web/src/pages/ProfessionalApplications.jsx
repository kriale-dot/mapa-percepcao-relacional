import { useEffect, useMemo, useState } from 'react'
import {
  createApplication,
  deleteApplication,
  getApplicationOptions,
  listApplicationEmails,
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

const emptyFilters = {
  participante: '',
  status: '',
  instrumento_id: '',
  vinculo_id: '',
  tipo_vinculo: '',
  data_de: '',
  data_ate: '',
}

export default function ProfessionalApplications() {
  const [applications, setApplications] = useState([])
  const [versions, setVersions] = useState([])
  const [relationships, setRelationships] = useState([])
  const [instruments, setInstruments] = useState([])
  const [filterRelationships, setFilterRelationships] = useState([])
  const [form, setForm] = useState(emptyForm)
  const [filters, setFilters] = useState(emptyFilters)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [deletingId, setDeletingId] = useState(null)
  const [emailList, setEmailList] = useState([])
  const [emailSummary, setEmailSummary] = useState({
    total_emails_unicos: 0,
    total_avaliacoes: 0,
  })
  const [emailListStatus, setEmailListStatus] = useState('idle')
  const [emailListOpen, setEmailListOpen] = useState(false)
  const [emailListMessage, setEmailListMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load()
  }, [])

  async function load(activeFilters = filters) {
    setStatus('loading')
    setMessage('')

    try {
      const [applicationsResult, optionsResult] = await Promise.all([
        listApplications(activeFilters),
        getApplicationOptions(),
      ])

      setApplications(applicationsResult.aplicacoes || [])
      setVersions(optionsResult.versoes || [])
      setRelationships(optionsResult.vinculos || [])
      setInstruments(optionsResult.instrumentos || [])
      setFilterRelationships(optionsResult.vinculos_filtro || [])
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

  const emailText = useMemo(
    () => emailList.map((item) => item.email).join('\n'),
    [emailList],
  )

  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function updateFilter(field, value) {
    setFilters((current) => ({
      ...current,
      [field]: value,
    }))
  }

  function handleFilterSubmit(event) {
    event.preventDefault()
    load(filters)
  }

  function handleFilterReset() {
    setFilters(emptyFilters)
    load(emptyFilters)
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

      const result = await listApplications(filters)
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

  async function handleDelete(application) {
    const isCompleted = application.status === 'CONCLUIDA'
    const label = isCompleted ? 'avaliação concluída' : 'avaliação'

    const confirmed = window.confirm(
      `Excluir permanentemente esta ${label}?\n\nSerão apagados os participantes desta aplicação, links de acesso, respostas, itens marcados como "Não se aplica", comparações, resultados e eventual devolutiva profissional.\n\nO instrumento, a versão, as pessoas cadastradas e o vínculo não serão excluídos.\n\nEsta ação não pode ser desfeita.`,
    )

    if (!confirmed) return

    setDeletingId(application.id)
    setMessage('')

    try {
      await deleteApplication(application.id)
      setApplications((current) =>
        current.filter((item) => item.id !== application.id),
      )
      setMessage('Avaliação excluída com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir a avaliação.')
    } finally {
      setDeletingId(null)
    }
  }

  async function handleGenerateEmailList() {
    setEmailListOpen(true)
    setEmailListStatus('loading')
    setEmailListMessage('')

    try {
      const result = await listApplicationEmails()
      setEmailList(result.emails || [])
      setEmailSummary(
        result.resumo || {
          total_emails_unicos: 0,
          total_avaliacoes: 0,
        },
      )
      setEmailListStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setEmailListStatus('error')
      setEmailListMessage(
        error.message || 'Não foi possível gerar a lista de e-mails.',
      )
    }
  }

  async function handleCopyEmails() {
    if (!emailText) return

    try {
      await navigator.clipboard.writeText(emailText)
      setEmailListMessage('Lista de e-mails copiada.')
    } catch {
      const textarea = document.createElement('textarea')
      textarea.value = emailText
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
      setEmailListMessage('Lista de e-mails copiada.')
    }
  }

  function handleDownloadEmailCsv() {
    if (emailList.length === 0) return

    const escapeCsv = (value) =>
      `"${String(value ?? '').replaceAll('"', '""')}"`

    const rows = [
      ['E-mail', 'Avaliações', 'Primeira avaliação', 'Última avaliação'],
      ...emailList.map((item) => [
        item.email,
        item.total_avaliacoes,
        item.primeira_avaliacao_em,
        item.ultima_avaliacao_em,
      ]),
    ]

    const csv = rows
      .map((row) => row.map(escapeCsv).join(';'))
      .join('\r\n')

    const blob = new Blob(['\uFEFF', csv], {
      type: 'text/csv;charset=utf-8',
    })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'emails-avaliacoes.csv'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)

    setEmailListMessage('Arquivo CSV gerado.')
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
                  O tipo e o tempo de união atuais serão copiados para a
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
                    Tempo de união
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

          <div className="mb-5 rounded-2xl border border-[#A8C8B8]/40 bg-white p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p className="text-sm font-semibold">Lista de e-mails</p>
                <p className="mt-1 text-xs text-[#385048]/60">
                  Gera uma lista única com todos os e-mails de contato das
                  avaliações, independentemente dos filtros abaixo.
                </p>
              </div>

              <button
                type="button"
                onClick={handleGenerateEmailList}
                disabled={emailListStatus === 'loading'}
                className="rounded-xl border border-[#88B098] px-4 py-2 text-sm font-semibold disabled:opacity-50"
              >
                {emailListStatus === 'loading'
                  ? 'Gerando...'
                  : 'Gerar lista de e-mails'}
              </button>
            </div>

            {emailListOpen ? (
              <div className="mt-5 border-t border-[#A8C8B8]/30 pt-5">
                {emailListStatus === 'error' ? (
                  <div
                    role="alert"
                    className="rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm"
                  >
                    {emailListMessage}
                  </div>
                ) : null}

                {emailListStatus === 'ready' ? (
                  <>
                    <div className="flex flex-wrap gap-3 text-sm">
                      <span className="rounded-full bg-[#A8C8B8]/18 px-3 py-1 font-semibold">
                        {emailSummary.total_emails_unicos} e-mail(s) único(s)
                      </span>
                      <span className="rounded-full bg-[#A8C8D0]/18 px-3 py-1 font-semibold">
                        {emailSummary.total_avaliacoes} avaliação(ões)
                      </span>
                    </div>

                    {emailList.length === 0 ? (
                      <p className="mt-4 text-sm text-[#385048]/65">
                        Ainda não há e-mails cadastrados em avaliações.
                      </p>
                    ) : (
                      <>
                        <textarea
                          readOnly
                          rows={Math.min(12, Math.max(4, emailList.length))}
                          value={emailText}
                          className="mt-4 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 font-mono text-sm outline-none"
                          aria-label="Lista de e-mails das avaliações"
                        />

                        <div className="mt-3 flex flex-wrap gap-2">
                          <button
                            type="button"
                            onClick={handleCopyEmails}
                            className="rounded-xl bg-[#385048] px-4 py-2 text-sm font-semibold text-white"
                          >
                            Copiar e-mails
                          </button>
                          <button
                            type="button"
                            onClick={handleDownloadEmailCsv}
                            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                          >
                            Baixar CSV
                          </button>
                          <button
                            type="button"
                            onClick={() => setEmailListOpen(false)}
                            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                          >
                            Fechar
                          </button>
                        </div>

                        {emailListMessage ? (
                          <p className="mt-3 text-xs text-[#385048]/65">
                            {emailListMessage}
                          </p>
                        ) : null}

                        <div className="mt-4 max-h-72 overflow-auto rounded-xl border border-[#A8C8B8]/30">
                          {emailList.map((item) => (
                            <div
                              key={item.email}
                              className="flex flex-col gap-1 border-b border-[#A8C8B8]/20 px-4 py-3 text-sm last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                              <span className="break-all font-medium">
                                {item.email}
                              </span>
                              <span className="text-xs text-[#385048]/55">
                                {item.total_avaliacoes} avaliação(ões)
                              </span>
                            </div>
                          ))}
                        </div>
                      </>
                    )}
                  </>
                ) : null}
              </div>
            ) : null}
          </div>

          <form
            className="mb-5 rounded-2xl border border-[#A8C8B8]/40 bg-white p-5"
            onSubmit={handleFilterSubmit}
          >
            <p className="text-sm font-semibold">Filtros</p>

            <div className="mt-4 grid gap-3 sm:grid-cols-2">
              <input
                type="search"
                placeholder="Participante ou e-mail"
                value={filters.participante}
                onChange={(event) =>
                  updateFilter('participante', event.target.value)
                }
                className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
              />

              <select
                value={filters.status}
                onChange={(event) =>
                  updateFilter('status', event.target.value)
                }
                className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
              >
                <option value="">Todos os status</option>
                <option value="RASCUNHO">Rascunho</option>
                <option value="PRONTA">Pronta</option>
                <option value="EM_ANDAMENTO">Em andamento</option>
                <option value="CONCLUIDA">Concluída</option>
                <option value="CANCELADA">Cancelada</option>
              </select>

              <select
                value={filters.instrumento_id}
                onChange={(event) =>
                  updateFilter('instrumento_id', event.target.value)
                }
                className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
              >
                <option value="">Todos os instrumentos</option>
                {instruments.map((instrument) => (
                  <option key={instrument.id} value={instrument.id}>
                    {instrument.nome}
                  </option>
                ))}
              </select>

              <select
                value={filters.vinculo_id}
                onChange={(event) =>
                  updateFilter('vinculo_id', event.target.value)
                }
                className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
              >
                <option value="">Todos os vínculos cadastrados</option>
                {filterRelationships.map((relationship) => (
                  <option key={relationship.id} value={relationship.id}>
                    {relationship.pessoa_a_nome} ↔ {relationship.pessoa_b_nome}
                    {relationship.status === 'INATIVO' ? ' — inativo' : ''}
                  </option>
                ))}
              </select>

              <input
                type="text"
                placeholder="Tipo de vínculo"
                value={filters.tipo_vinculo}
                onChange={(event) =>
                  updateFilter('tipo_vinculo', event.target.value)
                }
                className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 text-sm outline-none"
              />

              <div className="grid grid-cols-2 gap-2">
                <input
                  type="date"
                  aria-label="Data inicial"
                  value={filters.data_de}
                  onChange={(event) =>
                    updateFilter('data_de', event.target.value)
                  }
                  className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-3 py-3 text-sm outline-none"
                />
                <input
                  type="date"
                  aria-label="Data final"
                  value={filters.data_ate}
                  onChange={(event) =>
                    updateFilter('data_ate', event.target.value)
                  }
                  className="rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-3 py-3 text-sm outline-none"
                />
              </div>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
              <button
                type="submit"
                className="rounded-xl bg-[#385048] px-4 py-2 text-sm font-semibold text-white"
              >
                Aplicar filtros
              </button>
              <button
                type="button"
                onClick={handleFilterReset}
                className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
              >
                Limpar
              </button>
            </div>
          </form>

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
                  {application.devolutiva_status ? (
                    <span className="rounded-full bg-[#D8B078]/16 px-3 py-1 text-xs font-semibold">
                      Devolutiva {application.devolutiva_status.toLowerCase()}
                    </span>
                  ) : null}
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
                    Tempo de união: {application.duracao_vinculo_texto}
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

                <div className="mt-5 flex flex-wrap gap-2">
                  <button
                    type="button"
                    onClick={() =>
                      navigate(
                        `/profissional/avaliacoes/${application.id}`,
                      )
                    }
                    className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                  >
                    Ver detalhes
                  </button>

                  {application.status === 'CONCLUIDA' ? (
                    <button
                      type="button"
                      onClick={() =>
                        navigate(
                          `/profissional/avaliacoes/${application.id}/resultados`,
                        )
                      }
                      className="rounded-xl bg-[#385048] px-4 py-2 text-sm font-semibold text-white"
                    >
                      Ver resultados
                    </button>
                  ) : null}

                  <button
                    type="button"
                    disabled={deletingId === application.id}
                    onClick={() => handleDelete(application)}
                    className="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 disabled:opacity-50"
                  >
                    {deletingId === application.id
                      ? 'Excluindo...'
                      : 'Excluir avaliação'}
                  </button>
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
