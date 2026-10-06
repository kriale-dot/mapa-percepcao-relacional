import { useEffect, useState } from 'react'
import { listPublicLibrary } from '../services/api'

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

export default function PublicLibrary() {
  const [documents, setDocuments] = useState([])
  const [professional, setProfessional] = useState(null)
  const [query, setQuery] = useState('')
  const [appliedQuery, setAppliedQuery] = useState('')
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    load('')
  }, [])

  async function load(search) {
    setStatus('loading')
    setMessage('')

    try {
      const result = await listPublicLibrary(search)
      setDocuments(result.documentos || [])
      setProfessional(result.profissional || null)
      setAppliedQuery(result.busca || '')
      setStatus('ready')
    } catch (error) {
      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar a biblioteca.',
      )
    }
  }

  function handleSearch(event) {
    event.preventDefault()
    load(query.trim())
  }

  function clearSearch() {
    setQuery('')
    load('')
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/')}
            className="flex min-w-0 items-center gap-3 text-left"
          >
            {professional?.logo_url ? (
              <img
                src={professional.logo_url}
                alt={`Logotipo de ${professional.nome}`}
                className="h-10 w-auto max-w-[170px] object-contain"
              />
            ) : (
              <div>
                <p className="text-lg font-semibold">
                  {professional?.nome || 'Biblioteca pública'}
                </p>
                <p className="text-xs text-[#385048]/65">
                  Documentos disponíveis livremente
                </p>
              </div>
            )}
          </button>

          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => navigate('/avaliacoes')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Avaliações
            </button>
            <button
              type="button"
              onClick={() => navigate('/')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Voltar ao início
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-12">
        <div className="max-w-3xl">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Biblioteca pública
          </p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">
            Documentos e materiais para consulta
          </h1>
          <p className="mt-4 text-base leading-7 text-[#385048]/70">
            Acesse gratuitamente os documentos publicados. Use a busca para
            localizar materiais pelo título, descrição ou nome do arquivo.
          </p>
        </div>

        <form
          onSubmit={handleSearch}
          className="mt-8 rounded-3xl border border-[#A8C8B8]/45 bg-white p-5 shadow-sm"
        >
          <label className="block">
            <span className="text-sm font-semibold">Buscar na biblioteca</span>
            <div className="mt-2 flex flex-col gap-3 sm:flex-row">
              <input
                type="search"
                maxLength="120"
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Digite uma palavra ou título..."
                className="min-w-0 flex-1 rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
              <button
                type="submit"
                disabled={status === 'loading'}
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
              >
                Buscar
              </button>
              {appliedQuery ? (
                <button
                  type="button"
                  onClick={clearSearch}
                  className="rounded-xl border border-[#385048]/20 px-5 py-3 text-sm font-semibold"
                >
                  Limpar
                </button>
              ) : null}
            </div>
          </label>
        </form>

        {status === 'loading' ? (
          <div className="mt-8 rounded-2xl border border-[#A8C8B8]/40 bg-white p-6">
            Carregando documentos...
          </div>
        ) : null}

        {status === 'error' ? (
          <div className="mt-8 rounded-2xl border border-[#D8B078]/40 bg-white p-6">
            {message}
          </div>
        ) : null}

        {status === 'ready' ? (
          <div className="mt-8">
            <div className="flex flex-wrap items-end justify-between gap-3">
              <div>
                <h2 className="text-2xl font-semibold">
                  {appliedQuery ? 'Resultados da busca' : 'Materiais disponíveis'}
                </h2>
                <p className="mt-1 text-sm text-[#385048]/60">
                  {documents.length}{' '}
                  {documents.length === 1 ? 'documento encontrado' : 'documentos encontrados'}
                </p>
              </div>
            </div>

            {documents.length === 0 ? (
              <div className="mt-5 rounded-2xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center">
                <p className="font-medium">
                  Nenhum documento encontrado.
                </p>
                <p className="mt-2 text-sm text-[#385048]/60">
                  Tente outra palavra ou limpe a busca para ver todos os materiais.
                </p>
              </div>
            ) : (
              <div className="mt-5 grid gap-5 md:grid-cols-2">
                {documents.map((document) => (
                  <article
                    key={document.id}
                    className="overflow-hidden rounded-3xl border border-[#A8C8B8]/45 bg-white shadow-sm"
                  >
                    {document.tipo === 'PNG' ? (
                      <div className="flex h-52 items-center justify-center bg-[#A8C8B8]/8 p-4">
                        <img
                          src={document.arquivo_url}
                          alt={document.titulo}
                          className="max-h-full max-w-full object-contain"
                          loading="lazy"
                        />
                      </div>
                    ) : (
                      <div className="flex h-32 items-center justify-center bg-[#D8B078]/12">
                        <span className="rounded-2xl border border-[#D8B078]/35 bg-white px-5 py-3 text-sm font-bold tracking-wide">
                          PDF
                        </span>
                      </div>
                    )}

                    <div className="p-6">
                      <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-[#385048]/50">
                        <span>{document.tipo}</span>
                        <span>•</span>
                        <span>{formatSize(document.tamanho_bytes)}</span>
                      </div>
                      <h3 className="mt-3 text-xl font-semibold">
                        {document.titulo}
                      </h3>
                      {document.descricao ? (
                        <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-[#385048]/68">
                          {document.descricao}
                        </p>
                      ) : null}
                      <a
                        href={document.arquivo_url}
                        target="_blank"
                        rel="noreferrer"
                        className="mt-5 inline-flex rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
                      >
                        {document.tipo === 'PDF'
                          ? 'Abrir PDF'
                          : 'Abrir imagem'}
                      </a>
                    </div>
                  </article>
                ))}
              </div>
            )}
          </div>
        ) : null}
      </main>
    </div>
  )
}
