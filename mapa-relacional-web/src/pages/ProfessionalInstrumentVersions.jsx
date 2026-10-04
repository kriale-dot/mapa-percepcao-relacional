import { useEffect, useState } from 'react'
import {
  archiveInstrumentVersion,
  createInstrumentVersion,
  deleteInstrumentVersion,
  getInstrument,
  listInstrumentVersions,
  publishInstrumentVersion,
  updateInstrumentVersion,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export default function ProfessionalInstrumentVersions({ instrumentId }) {
  const [instrument, setInstrument] = useState(null)
  const [versions, setVersions] = useState([])
  const [numeroVersao, setNumeroVersao] = useState('')
  const [editingId, setEditingId] = useState(null)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load()
  }, [instrumentId])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const [instrumentResult, versionsResult] = await Promise.all([
        getInstrument(instrumentId),
        listInstrumentVersions(instrumentId),
      ])

      setInstrument(instrumentResult.instrumento)
      setVersions(versionsResult.versoes || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(error.message || 'Não foi possível carregar as versões.')
    }
  }

  function beginEdit(version) {
    if (version.status !== 'RASCUNHO') return

    setEditingId(version.id)
    setNumeroVersao(version.numero_versao)
    setMessage('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  function cancelEdit() {
    setEditingId(null)
    setNumeroVersao('')
    setMessage('')
  }

  async function refreshVersions() {
    const result = await listInstrumentVersions(instrumentId)
    setVersions(result.versoes || [])
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    try {
      if (editingId) {
        await updateInstrumentVersion(instrumentId, editingId, numeroVersao)
        setMessage('Versão atualizada com sucesso.')
      } else {
        await createInstrumentVersion(instrumentId, numeroVersao)
        setMessage('Versão criada com sucesso.')
      }

      setEditingId(null)
      setNumeroVersao('')
      await refreshVersions()
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar a versão.')
    }
  }

  async function handlePublish(version) {
    const confirmed = window.confirm(
      `Publicar a versão "${version.numero_versao}"? Após a publicação, ela ficará imutável. Alterações futuras deverão ser feitas em uma nova versão.`,
    )

    if (!confirmed) return

    await runAction(
      () => publishInstrumentVersion(instrumentId, version.id),
      'Versão publicada com sucesso.',
    )
  }

  async function handleArchive(version) {
    const confirmed = window.confirm(
      `Arquivar a versão "${version.numero_versao}"?`,
    )

    if (!confirmed) return

    await runAction(
      () => archiveInstrumentVersion(instrumentId, version.id),
      'Versão arquivada com sucesso.',
    )
  }

  async function handleDelete(version) {
    const confirmed = window.confirm(
      `Excluir a versão em rascunho "${version.numero_versao}"?`,
    )

    if (!confirmed) return

    await runAction(
      () => deleteInstrumentVersion(instrumentId, version.id),
      'Versão excluída com sucesso.',
    )

    if (editingId === version.id) {
      setEditingId(null)
      setNumeroVersao('')
    }
  }

  async function runAction(action, successMessage) {
    setMessage('')

    try {
      await action()
      await refreshVersions()
      setMessage(successMessage)
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível concluir a operação.')
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
            onClick={() => navigate('/profissional/instrumentos')}
            className="text-left"
          >
            <p className="text-lg font-semibold">Versões do instrumento</p>
            <p className="text-xs text-[#385048]/65">
              {instrument?.nome || 'Carregando instrumento...'}
            </p>
          </button>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => navigate('/profissional/instrumentos')}
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
            {editingId ? 'Editar versão' : 'Nova versão'}
          </p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight">
            {editingId ? 'Atualizar número' : 'Criar versão'}
          </h1>

          <p className="mt-3 text-sm leading-6 text-[#385048]/70">
            Novas versões começam como rascunho. Ao publicar, a versão fica
            imutável e alterações futuras exigem uma nova versão.
          </p>

          <form className="mt-7 space-y-5" onSubmit={handleSubmit}>
            <label className="block">
              <span className="text-sm font-medium">Número da versão</span>
              <input
                type="text"
                required
                maxLength="30"
                placeholder="Ex.: 1.0"
                value={numeroVersao}
                onChange={(event) => setNumeroVersao(event.target.value)}
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

            <div className="flex flex-wrap gap-3">
              <button
                type="submit"
                disabled={status === 'saving'}
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
              >
                {status === 'saving'
                  ? 'Salvando...'
                  : editingId
                    ? 'Salvar alteração'
                    : 'Criar versão'}
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
              Histórico
            </p>
            <h2 className="mt-2 text-2xl font-semibold">
              Versões cadastradas
            </h2>
          </div>

          {status === 'loading' ? (
            <div className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando versões...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' && status !== 'error' && versions.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
              Nenhuma versão cadastrada ainda.
            </div>
          ) : null}

          <div className="space-y-4">
            {versions.map((version) => (
              <article
                key={version.id}
                className="rounded-2xl border border-[#A8C8B8]/40 bg-white p-6"
              >
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="text-lg font-semibold">
                        Versão {version.numero_versao}
                      </h3>
                      <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
                        {version.status}
                      </span>
                    </div>

                    <p className="mt-3 text-xs text-[#385048]/55">
                      {version.total_secoes} seção(ões) ·{' '}
                      {version.total_aplicacoes} aplicação(ões)
                    </p>

                    {version.publicado_em ? (
                      <p className="mt-2 text-xs text-[#385048]/50">
                        Publicada em {version.publicado_em}
                      </p>
                    ) : null}
                  </div>

                  <div className="flex shrink-0 flex-wrap gap-2">
                    {version.status === 'RASCUNHO' ? (
                      <>
                        <button
                          type="button"
                          onClick={() => beginEdit(version)}
                          className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
                        >
                          Editar
                        </button>
                        <button
                          type="button"
                          onClick={() => handlePublish(version)}
                          className="rounded-xl border border-[#88B098] px-4 py-2 text-sm font-semibold"
                        >
                          Publicar
                        </button>
                        <button
                          type="button"
                          onClick={() => handleDelete(version)}
                          className="rounded-xl border border-[#D8B078]/60 px-4 py-2 text-sm font-semibold"
                        >
                          Excluir
                        </button>
                      </>
                    ) : null}

                    {version.status === 'PUBLICADA' ? (
                      <button
                        type="button"
                        onClick={() => handleArchive(version)}
                        className="rounded-xl border border-[#D8B078]/60 px-4 py-2 text-sm font-semibold"
                      >
                        Arquivar
                      </button>
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
