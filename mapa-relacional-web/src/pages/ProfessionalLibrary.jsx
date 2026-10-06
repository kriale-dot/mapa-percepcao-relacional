import { useEffect, useState } from 'react'
import {
  createLibraryDocument,
  deleteLibraryDocument,
  listLibraryDocuments,
  updateLibraryDocument,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function formatSize(bytes) {
  const value = Number(bytes || 0)

  if (value < 1024) return `${value} B`
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`

  return `${(value / (1024 * 1024)).toFixed(1)} MB`
}

export default function ProfessionalLibrary() {
  const [documents, setDocuments] = useState([])
  const [query, setQuery] = useState('')
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [workingId, setWorkingId] = useState(null)
  const [form, setForm] = useState({
    titulo: '',
    descricao: '',
    status: 'ATIVO',
    arquivo: null,
  })
  const [fileInputKey, setFileInputKey] = useState(0)

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load('')
  }, [])

  async function load(search = query.trim()) {
    setStatus('loading')
    setMessage('')

    try {
      const result = await listLibraryDocuments(search)
      setDocuments(result.documentos || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar a biblioteca.',
      )
    }
  }

  function updateDraft(id, field, value) {
    setDocuments((current) =>
      current.map((document) =>
        document.id === id
          ? { ...document, [field]: value }
          : document,
      ),
    )
  }

  async function handleCreate(event) {
    event.preventDefault()

    if (!form.arquivo) {
      setMessage('Selecione um arquivo PDF ou PNG.')
      return
    }

    setStatus('creating')
    setMessage('')

    try {
      await createLibraryDocument(form)
      setForm({
        titulo: '',
        descricao: '',
        status: 'ATIVO',
        arquivo: null,
      })
      setFileInputKey((value) => value + 1)
      await load(query.trim())
      setMessage('Documento publicado com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível publicar o documento.')
    }
  }

  async function handleSave(document) {
    setWorkingId(document.id)
    setMessage('')

    try {
      const result = await updateLibraryDocument(document.id, {
        titulo: document.titulo,
        descricao: document.descricao || '',
        status: document.status,
      })

      setDocuments((current) =>
        current.map((item) =>
          item.id === document.id ? result.documento : item,
        ),
      )
      setMessage('Documento atualizado.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível atualizar o documento.')
    } finally {
      setWorkingId(null)
    }
  }

  async function handleDelete(document) {
    const confirmed = window.confirm(
      `Excluir permanentemente "${document.titulo}" da biblioteca?`,
    )

    if (!confirmed) return

    setWorkingId(document.id)
    setMessage('')

    try {
      await deleteLibraryDocument(document.id)
      setDocuments((current) =>
        current.filter((item) => item.id !== document.id),
      )
      setMessage('Documento excluído.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível excluir o documento.')
    } finally {
      setWorkingId(null)
    }
  }

  function handleSearch(event) {
    event.preventDefault()
    load(query.trim())
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Biblioteca pública</p>
            <p className="text-xs text-[#385048]/65">
              Documentos PDF e PNG disponíveis aos visitantes
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <a
              href="/biblioteca"
              target="_blank"
              rel="noreferrer"
              className="rounded-xl border border-[#88B098]/50 px-4 py-2 text-sm font-semibold"
            >
              Abrir biblioteca pública
            </a>
            <button
              type="button"
              onClick={() => navigate('/profissional')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Voltar
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Novo documento
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            Publicar na biblioteca
          </h1>
          <p className="mt-3 max-w-3xl text-sm leading-7 text-[#385048]/70">
            Os documentos ativos ficam disponíveis livremente em
            <strong> /biblioteca</strong>, sem login ou cadastro.
          </p>

          {message ? (
            <div className="mt-5 rounded-2xl bg-[#D8B078]/14 px-5 py-4 text-sm">
              {message}
            </div>
          ) : null}

          <form
            className="mt-7 grid gap-4"
            onSubmit={handleCreate}
          >
            <div className="grid gap-4 md:grid-cols-[1fr_220px]">
              <label className="block">
                <span className="text-sm font-medium">Título</span>
                <input
                  type="text"
                  required
                  maxLength="200"
                  value={form.titulo}
                  onChange={(event) =>
                    setForm((current) => ({
                      ...current,
                      titulo: event.target.value,
                    }))
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium">Status</span>
                <select
                  value={form.status}
                  onChange={(event) =>
                    setForm((current) => ({
                      ...current,
                      status: event.target.value,
                    }))
                  }
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
                >
                  <option value="ATIVO">Ativo / público</option>
                  <option value="INATIVO">Inativo / oculto</option>
                </select>
              </label>
            </div>

            <label className="block">
              <span className="text-sm font-medium">Descrição</span>
              <textarea
                rows="4"
                maxLength="4000"
                value={form.descricao}
                onChange={(event) =>
                  setForm((current) => ({
                    ...current,
                    descricao: event.target.value,
                  }))
                }
                className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
              />
            </label>

            <label className="block rounded-2xl border border-[#385048]/15 bg-[#FEFDFB] p-5">
              <span className="text-sm font-medium">Arquivo</span>
              <input
                key={fileInputKey}
                type="file"
                required
                accept="application/pdf,image/png"
                onChange={(event) =>
                  setForm((current) => ({
                    ...current,
                    arquivo: event.target.files?.[0] || null,
                  }))
                }
                className="mt-3 block w-full text-sm"
              />
              <p className="mt-2 text-xs leading-5 text-[#385048]/55">
                Formatos aceitos: PDF e PNG. Limite padrão: 20 MB.
              </p>
            </label>

            <div className="flex justify-end">
              <button
                type="submit"
                disabled={status === 'creating'}
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
              >
                {status === 'creating'
                  ? 'Publicando...'
                  : 'Publicar documento'}
              </button>
            </div>
          </form>
        </section>

        <section className="mt-8">
          <form
            onSubmit={handleSearch}
            className="flex flex-col gap-3 rounded-2xl border border-[#A8C8B8]/40 bg-white p-4 sm:flex-row"
          >
            <input
              type="search"
              maxLength="120"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Buscar por título, descrição ou arquivo..."
              className="min-w-0 flex-1 rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
            />
            <button
              type="submit"
              className="rounded-xl border border-[#385048]/20 px-5 py-3 text-sm font-semibold"
            >
              Buscar
            </button>
            {query ? (
              <button
                type="button"
                onClick={() => {
                  setQuery('')
                  load('')
                }}
                className="rounded-xl border border-[#385048]/20 px-5 py-3 text-sm font-semibold"
              >
                Limpar
              </button>
            ) : null}
          </form>

          {status === 'loading' ? (
            <div className="mt-5 rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
              Carregando documentos...
            </div>
          ) : null}

          {status === 'error' ? (
            <div className="mt-5 rounded-2xl border border-[#D8B078]/40 bg-white p-6">
              {message}
            </div>
          ) : null}

          {status !== 'loading' && documents.length === 0 ? (
            <div className="mt-5 rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center">
              Nenhum documento cadastrado.
            </div>
          ) : null}

          <div className="mt-5 space-y-5">
            {documents.map((document) => (
              <article
                key={document.id}
                className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-6 shadow-sm"
              >
                <div className="grid gap-5 lg:grid-cols-[160px_1fr]">
                  <div>
                    {document.tipo === 'PNG' ? (
                      <img
                        src={document.arquivo_url}
                        alt={document.titulo}
                        className="h-36 w-full rounded-2xl border border-[#A8C8B8]/35 object-contain"
                      />
                    ) : (
                      <div className="flex h-36 items-center justify-center rounded-2xl bg-[#D8B078]/12">
                        <span className="rounded-xl bg-white px-4 py-2 text-sm font-bold">
                          PDF
                        </span>
                      </div>
                    )}
                    <p className="mt-2 text-xs text-[#385048]/55">
                      {document.tipo} · {formatSize(document.tamanho_bytes)}
                    </p>
                    <a
                      href={document.arquivo_url}
                      target="_blank"
                      rel="noreferrer"
                      className="mt-3 inline-flex text-sm font-semibold underline"
                    >
                      Abrir arquivo
                    </a>
                  </div>

                  <div className="grid gap-4">
                    <div className="grid gap-4 md:grid-cols-[1fr_220px]">
                      <label className="block">
                        <span className="text-sm font-medium">Título</span>
                        <input
                          type="text"
                          maxLength="200"
                          value={document.titulo || ''}
                          onChange={(event) =>
                            updateDraft(
                              document.id,
                              'titulo',
                              event.target.value,
                            )
                          }
                          className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
                        />
                      </label>

                      <label className="block">
                        <span className="text-sm font-medium">Status</span>
                        <select
                          value={document.status}
                          onChange={(event) =>
                            updateDraft(
                              document.id,
                              'status',
                              event.target.value,
                            )
                          }
                          className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
                        >
                          <option value="ATIVO">Ativo / público</option>
                          <option value="INATIVO">Inativo / oculto</option>
                        </select>
                      </label>
                    </div>

                    <label className="block">
                      <span className="text-sm font-medium">Descrição</span>
                      <textarea
                        rows="4"
                        maxLength="4000"
                        value={document.descricao || ''}
                        onChange={(event) =>
                          updateDraft(
                            document.id,
                            'descricao',
                            event.target.value,
                          )
                        }
                        className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
                      />
                    </label>

                    <div className="flex flex-wrap justify-end gap-2">
                      <button
                        type="button"
                        disabled={workingId !== null}
                        onClick={() => handleDelete(document)}
                        className="rounded-xl border border-[#C97C5D]/35 px-4 py-2 text-sm font-semibold disabled:opacity-40"
                      >
                        Excluir
                      </button>
                      <button
                        type="button"
                        disabled={workingId !== null}
                        onClick={() => handleSave(document)}
                        className="rounded-xl bg-[#385048] px-5 py-2 text-sm font-semibold text-white disabled:opacity-40"
                      >
                        {workingId === document.id
                          ? 'Salvando...'
                          : 'Salvar alterações'}
                      </button>
                    </div>
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
