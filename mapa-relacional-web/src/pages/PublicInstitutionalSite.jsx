import { useEffect, useState } from 'react'
import { getPublicSite } from '../services/api'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function openLink(url) {
  if (!url) return

  if (url.startsWith('/')) {
    navigate(url)
    return
  }

  window.open(url, '_blank', 'noopener,noreferrer')
}

function youtubeEmbed(url) {
  if (!url) return null

  try {
    const parsed = new URL(url)

    if (parsed.hostname.includes('youtu.be')) {
      const id = parsed.pathname.replace('/', '')
      return id ? `https://www.youtube.com/embed/${id}` : null
    }

    if (parsed.hostname.includes('youtube.com')) {
      const id = parsed.searchParams.get('v')
      if (id) return `https://www.youtube.com/embed/${id}`

      const match = parsed.pathname.match(/\/embed\/([^/]+)/)
      return match ? `https://www.youtube.com/embed/${match[1]}` : null
    }
  } catch {
    return null
  }

  return null
}

export default function PublicInstitutionalSite() {
  const [site, setSite] = useState(null)
  const [status, setStatus] = useState('loading')

  useEffect(() => {
    let active = true

    getPublicSite()
      .then((result) => {
        if (!active) return
        setSite(result)
        setStatus('ready')
      })
      .catch(() => {
        if (!active) return
        setStatus('error')
      })

    return () => {
      active = false
    }
  }, [])

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] px-6 text-[#385048]">
        Carregando...
      </div>
    )
  }

  if (status === 'error' || !site) {
    return (
      <div className="min-h-screen bg-[#FEFDFB] px-6 py-16 text-[#385048]">
        <div className="mx-auto max-w-2xl rounded-3xl border border-[#D8B078]/40 bg-white p-8 text-center shadow-sm">
          <h1 className="text-2xl font-semibold">
            Site temporariamente indisponível
          </h1>
          <p className="mt-3 text-sm leading-6 text-[#385048]/70">
            Tente novamente em alguns instantes.
          </p>
          <button
            type="button"
            onClick={() => window.location.reload()}
            className="mt-6 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
          >
            Tentar novamente
          </button>
        </div>
      </div>
    )
  }

  const professional = site.profissional
  const blocks = site.blocos || []
  const hasProfileBlock = blocks.some((block) => block.tipo === 'PERFIL')

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="sticky top-0 z-20 border-b border-[#A8C8B8]/45 bg-[#FEFDFB]/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
          <button
            type="button"
            onClick={() => navigate('/')}
            className="flex min-w-0 items-center gap-3 text-left"
          >
            {professional.logo_url ? (
              <img
                src={professional.logo_url}
                alt={`Logotipo de ${professional.nome}`}
                className="h-11 w-auto max-w-[180px] object-contain"
              />
            ) : (
              <div>
                <p className="truncate text-lg font-semibold">
                  {professional.nome}
                </p>
                <p className="text-xs text-[#385048]/60">
                  Avaliação de Percepção Relacional
                </p>
              </div>
            )}
          </button>

          <div className="flex flex-wrap justify-end gap-2">
            <button
              type="button"
              onClick={() => navigate('/avaliacoes')}
              className="rounded-xl bg-[#385048] px-4 py-2 text-sm font-semibold text-white"
            >
              Fazer avaliação
            </button>
            <button
              type="button"
              onClick={() => navigate('/profissional/login')}
              className="rounded-xl border border-[#385048]/20 bg-white px-4 py-2 text-sm font-semibold"
            >
              Área profissional
            </button>
          </div>
        </div>
      </header>

      <main>
        {blocks.length === 0 ? (
          <DefaultInstitutional professional={professional} />
        ) : (
          <>
            {!hasProfileBlock ? (
              <ProfileHero professional={professional} />
            ) : null}

            {blocks.map((block) => (
              <InstitutionalBlock
                key={block.id}
                block={block}
                professional={professional}
              />
            ))}
          </>
        )}
      </main>

      <footer className="border-t border-[#A8C8B8]/35 bg-white">
        <div className="mx-auto grid max-w-6xl gap-4 px-6 py-8 md:grid-cols-2 md:items-end">
          <div>
            <p className="font-semibold">{professional.nome}</p>
            {professional.dados_contato ? (
              <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-[#385048]/65">
                {professional.dados_contato}
              </p>
            ) : null}
          </div>
          <div className="text-sm text-[#385048]/55 md:text-right">
            Avaliação de Percepção Relacional
          </div>
        </div>
      </footer>
    </div>
  )
}

function ProfileHero({ professional }) {
  return (
    <section className="mx-auto grid max-w-6xl gap-10 px-6 py-16 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
      <div className="flex justify-center lg:justify-start">
        {professional.foto_url ? (
          <img
            src={professional.foto_url}
            alt={professional.nome}
            className="aspect-square w-full max-w-sm rounded-3xl object-cover shadow-sm"
          />
        ) : professional.logo_url ? (
          <div className="flex aspect-square w-full max-w-sm items-center justify-center rounded-3xl border border-[#A8C8B8]/45 bg-white p-10 shadow-sm">
            <img
              src={professional.logo_url}
              alt={`Logotipo de ${professional.nome}`}
              className="max-h-full max-w-full object-contain"
            />
          </div>
        ) : (
          <div className="flex aspect-square w-full max-w-sm items-center justify-center rounded-3xl bg-[#A8C8B8]/20 text-6xl font-semibold">
            {professional.nome?.trim()?.charAt(0) || 'P'}
          </div>
        )}
      </div>

      <div>
        <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
          Profissional
        </p>
        <h1 className="mt-3 text-4xl font-semibold tracking-tight md:text-5xl">
          {professional.nome}
        </h1>

        {professional.descricao ? (
          <p className="mt-5 whitespace-pre-wrap text-lg leading-8 text-[#385048]/72">
            {professional.descricao}
          </p>
        ) : null}

        {professional.atuacao ? (
          <p className="mt-5 whitespace-pre-wrap text-sm leading-7 text-[#385048]/68">
            {professional.atuacao}
          </p>
        ) : null}
      </div>
    </section>
  )
}

function InstitutionalBlock({ block, professional }) {
  if (block.tipo === 'PERFIL') {
    return (
      <ProfileHero
        professional={{
          ...professional,
          nome: block.titulo || professional.nome,
          descricao: block.descricao || professional.descricao,
          atuacao: block.conteudo || professional.atuacao,
        }}
      />
    )
  }

  if (block.tipo === 'APRESENTACAO') {
    return (
      <section className="bg-[#A8C8B8]/10">
        <div className="mx-auto max-w-6xl px-6 py-16">
          <div className="mx-auto max-w-3xl">
            <SectionHeading block={block} fallback="Apresentação" />
            {professional.descricao ? (
              <p className="mt-5 whitespace-pre-wrap text-base leading-8 text-[#385048]/72">
                {professional.descricao}
              </p>
            ) : null}
            {professional.atuacao ? (
              <p className="mt-5 whitespace-pre-wrap text-base leading-8 text-[#385048]/72">
                {professional.atuacao}
              </p>
            ) : null}
            {block.conteudo ? (
              <p className="mt-5 whitespace-pre-wrap text-base leading-8 text-[#385048]/72">
                {block.conteudo}
              </p>
            ) : null}
          </div>
        </div>
      </section>
    )
  }

  if (block.tipo === 'TITULO') {
    return (
      <section className="mx-auto max-w-6xl px-6 py-16">
        <div className="mx-auto max-w-4xl">
          <h1 className="text-4xl font-semibold leading-tight tracking-tight md:text-5xl">
            {block.titulo || block.conteudo}
          </h1>
          {block.descricao ? (
            <p className="mt-5 text-lg leading-8 text-[#385048]/72">
              {block.descricao}
            </p>
          ) : null}
        </div>
      </section>
    )
  }

  if (block.tipo === 'TEXTO') {
    return (
      <section className="mx-auto max-w-6xl px-6 py-14">
        <div className="mx-auto max-w-4xl rounded-3xl border border-[#A8C8B8]/40 bg-white p-7 shadow-sm md:p-9">
          <SectionHeading block={block} />
          {block.conteudo ? (
            <p className="mt-5 whitespace-pre-wrap text-base leading-8 text-[#385048]/72">
              {block.conteudo}
            </p>
          ) : null}
        </div>
      </section>
    )
  }

  if (block.tipo === 'IMAGEM') {
    return (
      <section className="mx-auto max-w-6xl px-6 py-12">
        <div className="overflow-hidden rounded-3xl border border-[#A8C8B8]/40 bg-white shadow-sm">
          <div className="flex justify-center p-6 md:p-8">
            {block.link_url ? (
              <button
                type="button"
                onClick={() => openLink(block.link_url)}
                className="cursor-pointer"
                aria-label={
                  block.texto_alternativo ||
                  block.titulo ||
                  'Abrir link da imagem'
                }
              >
                <img
                  src={block.midia_url}
                  alt={block.texto_alternativo || block.titulo || ''}
                  className="h-auto w-auto max-w-full"
                />
              </button>
            ) : (
              <img
                src={block.midia_url}
                alt={block.texto_alternativo || block.titulo || ''}
                className="h-auto w-auto max-w-full"
              />
            )}
          </div>
          {block.titulo || block.descricao || block.conteudo ? (
            <div className="p-6 md:p-8">
              <SectionHeading block={block} />
              {block.conteudo ? (
                <p className="mt-4 whitespace-pre-wrap text-sm leading-7 text-[#385048]/70">
                  {block.conteudo}
                </p>
              ) : null}
            </div>
          ) : null}
        </div>
      </section>
    )
  }

  if (block.tipo === 'VIDEO') {
    const embedUrl = youtubeEmbed(block.midia_url)

    return (
      <section className="mx-auto max-w-6xl px-6 py-12">
        <div className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-6 shadow-sm md:p-8">
          <SectionHeading block={block} />
          <div className="mt-6 overflow-hidden rounded-2xl bg-black">
            {embedUrl ? (
              <iframe
                src={embedUrl}
                title={block.texto_alternativo || block.titulo || 'Vídeo'}
                className="aspect-video w-full"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
              />
            ) : (
              <video
                src={block.midia_url}
                controls
                className="aspect-video w-full bg-black object-contain"
              />
            )}
          </div>
          {block.conteudo ? (
            <p className="mt-5 whitespace-pre-wrap text-sm leading-7 text-[#385048]/70">
              {block.conteudo}
            </p>
          ) : null}
        </div>
      </section>
    )
  }

  if (block.tipo === 'AUDIO') {
    return (
      <section className="mx-auto max-w-6xl px-6 py-12">
        <div className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-6 shadow-sm md:p-8">
          <SectionHeading block={block} />
          <audio
            controls
            src={block.midia_url}
            className="mt-6 w-full"
          />
          {block.conteudo ? (
            <p className="mt-5 whitespace-pre-wrap text-sm leading-7 text-[#385048]/70">
              {block.conteudo}
            </p>
          ) : null}
        </div>
      </section>
    )
  }

  if (block.tipo === 'CTA' || block.tipo === 'LINK') {
    const emphasized = block.tipo === 'CTA'

    return (
      <section className={emphasized ? 'bg-[#385048] text-white' : ''}>
        <div className="mx-auto max-w-6xl px-6 py-14">
          <div
            className={
              emphasized
                ? 'mx-auto max-w-3xl'
                : 'mx-auto max-w-4xl rounded-3xl border border-[#A8C8B8]/40 bg-white p-7 shadow-sm'
            }
          >
            <SectionHeading block={block} light={emphasized} />
            {block.conteudo ? (
              <p
                className={[
                  'mt-5 whitespace-pre-wrap text-base leading-8',
                  emphasized ? 'text-white/80' : 'text-[#385048]/72',
                ].join(' ')}
              >
                {block.conteudo}
              </p>
            ) : null}
            {block.link_url ? (
              <button
                type="button"
                onClick={() => openLink(block.link_url)}
                className={[
                  'mt-6 rounded-xl px-5 py-3 text-sm font-semibold shadow-sm',
                  emphasized
                    ? 'bg-white text-[#385048]'
                    : 'bg-[#385048] text-white',
                ].join(' ')}
              >
                {block.link_texto || 'Saiba mais'}
              </button>
            ) : null}
          </div>
        </div>
      </section>
    )
  }

  if (block.tipo === 'AVALIACAO') {
    return (
      <section className="bg-[#A8C8B8]/12">
        <div className="mx-auto max-w-6xl px-6 py-16">
          <div className="mx-auto max-w-3xl">
            <SectionHeading
              block={block}
              fallback="Avaliações disponíveis"
            />
            {block.conteudo ? (
              <p className="mt-5 whitespace-pre-wrap text-base leading-8 text-[#385048]/72">
                {block.conteudo}
              </p>
            ) : null}
            <button
              type="button"
              onClick={() =>
                openLink(block.link_url || '/avaliacoes')
              }
              className="mt-6 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm"
            >
              {block.link_texto || 'Ver avaliações'}
            </button>
          </div>
        </div>
      </section>
    )
  }

  return null
}

function SectionHeading({ block, fallback = '', light = false }) {
  return (
    <>
      {block.titulo || fallback ? (
        <h2 className="text-2xl font-semibold tracking-tight md:text-3xl">
          {block.titulo || fallback}
        </h2>
      ) : null}
      {block.descricao ? (
        <p
          className={[
            'mt-3 text-sm leading-7',
            light ? 'text-white/70' : 'text-[#385048]/65',
          ].join(' ')}
        >
          {block.descricao}
        </p>
      ) : null}
    </>
  )
}

function DefaultInstitutional({ professional }) {
  return (
    <section className="mx-auto grid max-w-6xl gap-10 px-6 py-20 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
      <div className="flex justify-center lg:justify-start">
        {professional.foto_url ? (
          <img
            src={professional.foto_url}
            alt={professional.nome}
            className="aspect-square w-full max-w-sm rounded-3xl object-cover shadow-sm"
          />
        ) : professional.logo_url ? (
          <div className="flex aspect-square w-full max-w-sm items-center justify-center rounded-3xl border border-[#A8C8B8]/45 bg-white p-10 shadow-sm">
            <img
              src={professional.logo_url}
              alt={`Logotipo de ${professional.nome}`}
              className="max-h-full max-w-full object-contain"
            />
          </div>
        ) : (
          <div className="flex aspect-square w-full max-w-sm items-center justify-center rounded-3xl bg-[#A8C8B8]/20 text-6xl font-semibold">
            {professional.nome?.trim()?.charAt(0) || 'P'}
          </div>
        )}
      </div>

      <div className="max-w-4xl">
        <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
          {professional.nome}
        </p>
        <h1 className="mt-4 text-4xl font-semibold leading-tight tracking-tight md:text-5xl">
          Avaliação de Percepção Relacional
        </h1>
        <p className="mt-5 text-lg leading-8 text-[#385048]/72">
          Instrumento de percepção mútua e conhecimento interpessoal.
        </p>
        {professional.descricao ? (
          <p className="mt-6 whitespace-pre-wrap text-base leading-8 text-[#385048]/70">
            {professional.descricao}
          </p>
        ) : null}
        {professional.atuacao ? (
          <p className="mt-4 whitespace-pre-wrap text-sm leading-7 text-[#385048]/65">
            {professional.atuacao}
          </p>
        ) : null}
        <button
          type="button"
          onClick={() => navigate('/avaliacoes')}
          className="mt-7 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
        >
          Fazer uma avaliação
        </button>
      </div>
    </section>
  )
}
