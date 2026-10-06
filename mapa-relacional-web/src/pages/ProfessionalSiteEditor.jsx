import { useEffect, useState } from 'react'
import {
  createSiteBlock,
  deleteSiteBlock,
  listSiteBlocks,
  moveSiteBlock,
  updateSiteBlock,
  uploadSiteImage,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'
import { getVideoSource } from '../utils/video'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const typeLabels = {
  TITULO: 'Título',
  TEXTO: 'Texto',
  IMAGEM: 'Imagem',
  TEXTO_IMAGEM: 'Título + texto + imagem',
  VIDEO: 'Vídeo',
  AUDIO: 'Áudio',
  PERFIL: 'Identificação profissional',
  APRESENTACAO: 'Apresentação profissional',
  CTA: 'Chamada para ação',
  LINK: 'Botão / link',
  AVALIACAO: 'Seção de avaliações',
}

const emptyBlock = {
  tipo: 'TEXTO',
  titulo: '',
  descricao: '',
  conteudo: '',
  midia_url: '',
  texto_alternativo: '',
  link_url: '',
  link_texto: '',
  visivel: true,
  status: 'ATIVO',
}

function normalizeBlock(block) {
  return {
    ...emptyBlock,
    ...block,
    titulo: block?.titulo || '',
    descricao: block?.descricao || '',
    conteudo: block?.conteudo || '',
    midia_url: block?.midia_url || '',
    texto_alternativo: block?.texto_alternativo || '',
    link_url: block?.link_url || '',
    link_texto: block?.link_texto || '',
    visivel: block?.visivel !== false,
    status: block?.status || 'ATIVO',
  }
}

export default function ProfessionalSiteEditor() {
  const [blocks, setBlocks] = useState([])
  const [newBlock, setNewBlock] = useState(emptyBlock)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')
  const [workingId, setWorkingId] = useState(null)
  const [uploadingKey, setUploadingKey] = useState(null)

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
      const result = await listSiteBlocks()
      setBlocks((result.blocos || []).map(normalizeBlock))
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar o site institucional.',
      )
    }
  }

  function updateDraft(id, field, value) {
    setBlocks((current) =>
      current.map((block) =>
        block.id === id ? { ...block, [field]: value } : block,
      ),
    )
  }

  function updateNew(field, value) {
    setNewBlock((current) => ({
      ...current,
      [field]: value,
    }))
  }

  async function handleImageUpload(file, target) {
    if (!file) return

    const key = target === 'new' ? 'new' : `block-${target}`
    setUploadingKey(key)
    setMessage('')

    try {
      const result = await uploadSiteImage(file)
      const url = result.imagem?.url

      if (!url) {
        throw new Error('A API não retornou a imagem enviada.')
      }

      if (target === 'new') {
        updateNew('midia_url', url)
      } else {
        updateDraft(target, 'midia_url', url)
      }

      setMessage(
        target === 'new'
          ? 'Imagem enviada. Complete o bloco e clique em Adicionar bloco.'
          : 'Imagem enviada. Clique em Salvar bloco para confirmar a alteração.',
      )
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setMessage(error.message || 'Não foi possível enviar a imagem.')
    } finally {
      setUploadingKey(null)
    }
  }

  async function handleCreate(event) {
    event.preventDefault()
    setStatus('creating')
    setMessage('')

    try {
      await createSiteBlock(newBlock)
      setNewBlock(emptyBlock)
      await load()
      setMessage('Bloco criado com sucesso.')
    } catch (error) {
      setStatus('ready')
      setMessage(error.message || 'Não foi possível criar o bloco.')
    }
  }

  async function handleSave(block) {
    setWorkingId(block.id)
    setMessage('')

    try {
      const result = await updateSiteBlock(block.id, block)
      setBlocks((current) =>
        current.map((item) =>
          item.id === block.id ? normalizeBlock(result.bloco) : item,
        ),
      )
      setMessage('Bloco atualizado com sucesso.')
    } catch (error) {
      setMessage(error.message || 'Não foi possível salvar o bloco.')
    } finally {
      setWorkingId(null)
    }
  }

  async function handleDelete(block) {
    const confirmed = window.confirm(
      `Excluir o bloco "${typeLabels[block.tipo] || block.tipo}"?`,
    )

    if (!confirmed) return

    setWorkingId(block.id)
    setMessage('')

    try {
      await deleteSiteBlock(block.id)
      setBlocks((current) =>
        current.filter((item) => item.id !== block.id),
      )
      setMessage('Bloco excluído.')
    } catch (error) {
      setMessage(error.message || 'Não foi possível excluir o bloco.')
    } finally {
      setWorkingId(null)
    }
  }

  async function handleMove(block, direction) {
    setWorkingId(block.id)
    setMessage('')

    try {
      const result = await moveSiteBlock(block.id, direction)
      setBlocks((result.blocos || []).map(normalizeBlock))
    } catch (error) {
      setMessage(error.message || 'Não foi possível alterar a ordem.')
    } finally {
      setWorkingId(null)
    }
  }

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] p-8 text-[#385048]">
        Carregando editor do site...
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Site institucional</p>
            <p className="text-xs text-[#385048]/65">
              Blocos configuráveis do site público
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <a
              href="/"
              target="_blank"
              rel="noreferrer"
              className="rounded-xl border border-[#88B098]/50 px-4 py-2 text-sm font-semibold"
            >
              Abrir site público
            </a>
            <button
              type="button"
              onClick={() => navigate('/profissional/perfil')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Perfil profissional
            </button>
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
            Editor por blocos
          </p>
          <h1 className="mt-3 text-3xl font-semibold">
            Conteúdo do site institucional
          </h1>
          <p className="mt-4 max-w-3xl text-sm leading-7 text-[#385048]/70">
            Monte o site com textos, imagens, vídeos, áudio, links, chamadas
            para ação e blocos que usam automaticamente os dados do seu perfil.
            A ordem abaixo é a mesma ordem exibida ao visitante.
          </p>

          <div className="mt-5 rounded-2xl bg-[#A8C8D0]/14 p-5 text-sm leading-6">
            Fotografia, logotipo, descrição, atuação e contatos são mantidos em
            <button
              type="button"
              onClick={() => navigate('/profissional/perfil')}
              className="ml-1 font-semibold underline"
            >
              Perfil profissional
            </button>
            . Os blocos “Identificação profissional” e “Apresentação
            profissional” usam esses dados automaticamente.
          </div>

          {message ? (
            <div className="mt-5 rounded-2xl bg-[#D8B078]/14 px-5 py-4 text-sm">
              {message}
            </div>
          ) : null}
        </section>

        <section className="mt-7">
          <div className="space-y-5">
            {blocks.length === 0 ? (
              <div className="rounded-3xl border border-dashed border-[#A8C8B8] bg-white p-8 text-center text-sm text-[#385048]/65">
                Nenhum bloco cadastrado. Crie o primeiro bloco abaixo.
              </div>
            ) : null}

            {blocks.map((block, index) => (
              <article
                key={block.id}
                className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-6 shadow-sm"
              >
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#385048]/50">
                      Bloco {index + 1}
                    </p>
                    <h2 className="mt-2 text-xl font-semibold">
                      {typeLabels[block.tipo] || block.tipo}
                    </h2>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      disabled={index === 0 || workingId !== null}
                      onClick={() => handleMove(block, 'CIMA')}
                      className="rounded-xl border border-[#385048]/20 px-3 py-2 text-xs font-semibold disabled:opacity-40"
                    >
                      ↑ Subir
                    </button>
                    <button
                      type="button"
                      disabled={
                        index === blocks.length - 1 || workingId !== null
                      }
                      onClick={() => handleMove(block, 'BAIXO')}
                      className="rounded-xl border border-[#385048]/20 px-3 py-2 text-xs font-semibold disabled:opacity-40"
                    >
                      ↓ Descer
                    </button>
                    <button
                      type="button"
                      disabled={workingId !== null}
                      onClick={() => handleDelete(block)}
                      className="rounded-xl border border-[#C97C5D]/35 px-3 py-2 text-xs font-semibold disabled:opacity-40"
                    >
                      Excluir
                    </button>
                  </div>
                </div>

                <BlockFields
                  block={block}
                  uploading={uploadingKey === `block-${block.id}`}
                  onImageUpload={(file) =>
                    handleImageUpload(file, block.id)
                  }
                  onChange={(field, value) =>
                    updateDraft(block.id, field, value)
                  }
                />

                <div className="mt-5 flex justify-end">
                  <button
                    type="button"
                    disabled={
                      workingId !== null ||
                      uploadingKey === `block-${block.id}`
                    }
                    onClick={() => handleSave(block)}
                    className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
                  >
                    {workingId === block.id
                      ? 'Salvando...'
                      : 'Salvar bloco'}
                  </button>
                </div>
              </article>
            ))}
          </div>
        </section>

        <section className="mt-8 rounded-3xl border border-[#D8B078]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Novo bloco
          </p>
          <h2 className="mt-2 text-2xl font-semibold">
            Adicionar conteúdo
          </h2>

          <form className="mt-6" onSubmit={handleCreate}>
            <BlockFields
              block={newBlock}
              uploading={uploadingKey === 'new'}
              onImageUpload={(file) =>
                handleImageUpload(file, 'new')
              }
              onChange={updateNew}
            />

            <div className="mt-6 flex justify-end">
              <button
                type="submit"
                disabled={
                  status === 'creating' || uploadingKey === 'new'
                }
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
              >
                {status === 'creating' ? 'Criando...' : 'Adicionar bloco'}
              </button>
            </div>
          </form>
        </section>
      </main>
    </div>
  )
}

function BlockFields({
  block,
  onChange,
  onImageUpload,
  uploading = false,
}) {
  const isImage = ['IMAGEM', 'TEXTO_IMAGEM'].includes(block.tipo)
  const externalMediaType = ['VIDEO', 'AUDIO'].includes(block.tipo)
  const hasLink = [
    'IMAGEM',
    'TEXTO_IMAGEM',
    'CTA',
    'LINK',
    'AVALIACAO',
  ].includes(block.tipo)

  return (
    <div className="mt-5 grid gap-4">
      <div className="grid gap-4 md:grid-cols-3">
        <label className="block">
          <span className="text-sm font-medium">Tipo</span>
          <select
            value={block.tipo}
            onChange={(event) => onChange('tipo', event.target.value)}
            className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
          >
            {Object.entries(typeLabels).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </label>

        <label className="block">
          <span className="text-sm font-medium">Status</span>
          <select
            value={block.status}
            onChange={(event) => onChange('status', event.target.value)}
            className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
          >
            <option value="ATIVO">Ativo</option>
            <option value="INATIVO">Inativo</option>
          </select>
        </label>

        <label className="flex items-end gap-3 rounded-xl border border-[#385048]/15 bg-[#FEFDFB] px-4 py-3">
          <input
            type="checkbox"
            checked={Boolean(block.visivel)}
            onChange={(event) => onChange('visivel', event.target.checked)}
          />
          <span className="text-sm font-medium">Visível no site</span>
        </label>
      </div>

      <label className="block">
        <span className="text-sm font-medium">Título</span>
        <input
          type="text"
          maxLength="200"
          value={block.titulo}
          onChange={(event) => onChange('titulo', event.target.value)}
          className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
        />
      </label>

      <label className="block">
        <span className="text-sm font-medium">Descrição / subtítulo</span>
        <textarea
          rows="3"
          maxLength="4000"
          value={block.descricao}
          onChange={(event) => onChange('descricao', event.target.value)}
          className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
        />
      </label>

      <label className="block">
        <span className="text-sm font-medium">Conteúdo de texto</span>
        <textarea
          rows="5"
          maxLength="20000"
          value={block.conteudo}
          onChange={(event) => onChange('conteudo', event.target.value)}
          className="mt-2 w-full resize-y rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
        />
      </label>

      {isImage ? (
        <div className="grid gap-4 md:grid-cols-2">
          <div className="rounded-2xl border border-[#385048]/15 bg-[#FEFDFB] p-4">
            <span className="text-sm font-medium">Imagem</span>
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              required={!block.midia_url}
              disabled={uploading}
              onChange={(event) =>
                onImageUpload?.(event.target.files?.[0] || null)
              }
              className="mt-3 block w-full text-sm"
            />
            <p className="mt-2 text-xs leading-5 text-[#385048]/55">
              JPG, PNG ou WEBP. Limite padrão: 5 MB.
            </p>
            {uploading ? (
              <p className="mt-2 text-sm font-semibold">
                Enviando imagem...
              </p>
            ) : null}
          </div>

          <div>
            <span className="text-sm font-medium">Pré-visualização</span>
            {block.midia_url ? (
              <img
                src={block.midia_url}
                alt={block.texto_alternativo || block.titulo || ''}
                className="mt-2 max-h-52 w-full rounded-2xl border border-[#A8C8B8]/40 object-contain"
              />
            ) : (
              <div className="mt-2 flex h-36 items-center justify-center rounded-2xl border border-dashed border-[#A8C8B8] text-sm text-[#385048]/50">
                Nenhuma imagem enviada
              </div>
            )}
          </div>

          <label className="block md:col-span-2">
            <span className="text-sm font-medium">
              Texto alternativo / descrição da imagem
            </span>
            <input
              type="text"
              maxLength="255"
              value={block.texto_alternativo}
              onChange={(event) =>
                onChange('texto_alternativo', event.target.value)
              }
              className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
            />
          </label>
        </div>
      ) : null}

      {externalMediaType ? (
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block">
            <span className="text-sm font-medium">
              URL do {block.tipo === 'VIDEO' ? 'vídeo' : 'áudio'}
            </span>
            <input
              type="url"
              required
              value={block.midia_url}
              onChange={(event) => onChange('midia_url', event.target.value)}
              className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
              placeholder={
                block.tipo === 'VIDEO'
                  ? 'YouTube, Vimeo ou URL direta do arquivo'
                  : 'https://...'
              }
            />
            {block.tipo === 'VIDEO' ? (
              <p className="mt-2 text-xs leading-5 text-[#385048]/55">
                Compatível com YouTube, YouTube Shorts, Vimeo e arquivos de
                vídeo acessíveis por URL direta.
              </p>
            ) : null}
          </label>

          <label className="block">
            <span className="text-sm font-medium">
              Descrição da mídia
            </span>
            <input
              type="text"
              maxLength="255"
              value={block.texto_alternativo}
              onChange={(event) =>
                onChange('texto_alternativo', event.target.value)
              }
              className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
              placeholder={
                block.tipo === 'VIDEO'
                  ? 'Descrição acessível do vídeo'
                  : ''
              }
            />
          </label>

          {block.tipo === 'VIDEO' ? (
            <div className="md:col-span-2">
              <span className="text-sm font-medium">
                Pré-visualização do vídeo
              </span>
              <VideoPreview
                url={block.midia_url}
                title={
                  block.texto_alternativo ||
                  block.titulo ||
                  'Pré-visualização do vídeo'
                }
              />
            </div>
          ) : null}
        </div>
      ) : null}

      {hasLink ? (
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block">
            <span className="text-sm font-medium">Link</span>
            <input
              type="text"
              value={block.link_url}
              onChange={(event) => onChange('link_url', event.target.value)}
              className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
              placeholder="/avaliacoes ou https://..."
            />
          </label>

          {!['IMAGEM', 'TEXTO_IMAGEM'].includes(block.tipo) ? (
            <label className="block">
              <span className="text-sm font-medium">Texto do botão/link</span>
              <input
                type="text"
                maxLength="120"
                value={block.link_texto}
                onChange={(event) => onChange('link_texto', event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none"
              />
            </label>
          ) : (
            <div className="rounded-xl bg-[#A8C8B8]/12 px-4 py-3 text-xs leading-5 text-[#385048]/60">
              Quando houver link, clicar na imagem abrirá esse endereço.
            </div>
          )}
        </div>
      ) : null}
    </div>
  )
}


function VideoPreview({ url, title }) {
  const source = getVideoSource(url)

  if (!url) {
    return (
      <div className="mt-2 flex aspect-video w-full items-center justify-center rounded-2xl border border-dashed border-[#A8C8B8] bg-[#FEFDFB] text-sm text-[#385048]/50">
        Informe a URL para visualizar o vídeo
      </div>
    )
  }

  if (!source) {
    return (
      <div className="mt-2 rounded-2xl border border-[#D8B078]/45 bg-[#D8B078]/10 px-5 py-4 text-sm text-[#385048]/70">
        Não foi possível reconhecer esta URL de vídeo.
      </div>
    )
  }

  return (
    <div className="mt-2 overflow-hidden rounded-2xl bg-black">
      {source.kind === 'embed' ? (
        <iframe
          src={source.url}
          title={title}
          className="aspect-video w-full"
          loading="lazy"
          referrerPolicy="strict-origin-when-cross-origin"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowFullScreen
        />
      ) : (
        <video
          src={source.url}
          controls
          preload="metadata"
          className="aspect-video w-full bg-black object-contain"
        />
      )}
    </div>
  )
}
