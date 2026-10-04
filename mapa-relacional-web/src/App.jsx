import { useEffect, useState } from 'react'
import ProfessionalProfile from './pages/ProfessionalProfile'
import ProfessionalPassword from './pages/ProfessionalPassword'
import ProfessionalInstruments from './pages/ProfessionalInstruments'
import ProfessionalInstrumentVersions from './pages/ProfessionalInstrumentVersions'
import ProfessionalSections from './pages/ProfessionalSections'
import ProfessionalItems from './pages/ProfessionalItems'
import ProfessionalAlternatives from './pages/ProfessionalAlternatives'
import ProfessionalPeople from './pages/ProfessionalPeople'
import ProfessionalRelationships from './pages/ProfessionalRelationships'
import ProfessionalApplications from './pages/ProfessionalApplications'
import PublicEvaluations from './pages/PublicEvaluations'
import PublicEvaluationStart from './pages/PublicEvaluationStart'
import {
  getApiHealth,
  getAuthenticatedProfessional,
  loginProfessional,
} from './services/api'
import {
  clearAuthToken,
  getAuthToken,
  setAuthToken,
} from './services/auth'

const audiences = [
  'Casais e cônjuges',
  'Pais e filhos',
  'Amigos e familiares',
  'Líderes e liderados',
  'Outros vínculos interpessoais',
]

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function App() {
  const [path, setPath] = useState(window.location.pathname)

  useEffect(() => {
    const handlePopState = () => setPath(window.location.pathname)
    window.addEventListener('popstate', handlePopState)

    return () => window.removeEventListener('popstate', handlePopState)
  }, [])

  if (path === '/avaliacoes') {
    return <PublicEvaluations />
  }

  const publicEvaluationMatch = path.match(
    /^\/avaliacao\/([1-9][0-9]*)\/iniciar$/,
  )

  if (publicEvaluationMatch) {
    return (
      <PublicEvaluationStart
        versionId={Number(publicEvaluationMatch[1])}
      />
    )
  }

  if (path === '/profissional/login') {
    return <ProfessionalLogin />
  }

  if (path === '/profissional/perfil') {
    return <ProfessionalProfile />
  }

  if (path === '/profissional/senha') {
    return <ProfessionalPassword />
  }

  if (path === '/profissional/instrumentos') {
    return <ProfessionalInstruments />
  }

  if (path === '/profissional/pessoas') {
    return <ProfessionalPeople />
  }

  if (path === '/profissional/vinculos') {
    return <ProfessionalRelationships />
  }

  if (path === '/profissional/avaliacoes') {
    return <ProfessionalApplications />
  }

  const versionsMatch = path.match(
    /^\/profissional\/instrumentos\/([1-9][0-9]*)\/versoes$/,
  )

  if (versionsMatch) {
    return (
      <ProfessionalInstrumentVersions
        instrumentId={Number(versionsMatch[1])}
      />
    )
  }

  const sectionsMatch = path.match(
    /^\/profissional\/instrumentos\/([1-9][0-9]*)\/versoes\/([1-9][0-9]*)\/secoes$/,
  )

  if (sectionsMatch) {
    return (
      <ProfessionalSections
        instrumentId={Number(sectionsMatch[1])}
        versionId={Number(sectionsMatch[2])}
      />
    )
  }

  const itemsMatch = path.match(
    /^\/profissional\/instrumentos\/([1-9][0-9]*)\/versoes\/([1-9][0-9]*)\/secoes\/([1-9][0-9]*)\/itens$/,
  )

  if (itemsMatch) {
    return (
      <ProfessionalItems
        instrumentId={Number(itemsMatch[1])}
        versionId={Number(itemsMatch[2])}
        sectionId={Number(itemsMatch[3])}
      />
    )
  }

  const alternativesMatch = path.match(
    /^\/profissional\/instrumentos\/([1-9][0-9]*)\/versoes\/([1-9][0-9]*)\/secoes\/([1-9][0-9]*)\/itens\/([1-9][0-9]*)\/alternativas$/,
  )

  if (alternativesMatch) {
    return (
      <ProfessionalAlternatives
        instrumentId={Number(alternativesMatch[1])}
        versionId={Number(alternativesMatch[2])}
        sectionId={Number(alternativesMatch[3])}
        itemId={Number(alternativesMatch[4])}
      />
    )
  }

  if (path.startsWith('/profissional')) {
    return <ProfessionalArea />
  }

  return <PublicHome />
}

function PublicHome() {
  const [apiStatus, setApiStatus] = useState('checking')

  useEffect(() => {
    let active = true

    getApiHealth()
      .then(() => {
        if (active) setApiStatus('online')
      })
      .catch(() => {
        if (active) setApiStatus('offline')
      })

    return () => {
      active = false
    }
  }, [])

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-[#FEFDFB]/95">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
          <div>
            <p className="text-lg font-semibold tracking-tight">
              Avaliação de Percepção Relacional
            </p>
            <p className="text-xs text-[#385048]/70">
              Percepção mútua e conhecimento interpessoal
            </p>
          </div>

          <span
            className={[
              'rounded-full px-3 py-1 text-xs font-medium',
              apiStatus === 'online'
                ? 'bg-[#A8C8B8]/35 text-[#385048]'
                : apiStatus === 'offline'
                  ? 'bg-[#D8B078]/25 text-[#385048]'
                  : 'bg-[#A8C8D0]/25 text-[#385048]',
            ].join(' ')}
          >
            {apiStatus === 'online'
              ? 'API conectada'
              : apiStatus === 'offline'
                ? 'API indisponível'
                : 'Verificando API'}
          </span>
        </div>
      </header>

      <main>
        <section className="mx-auto grid max-w-6xl gap-12 px-6 py-20 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
          <div>
            <p className="mb-4 inline-flex rounded-full bg-[#88B098]/15 px-4 py-2 text-sm font-medium">
              Instrumento relacional digital
            </p>

            <h1 className="max-w-3xl text-4xl font-semibold leading-tight tracking-tight md:text-5xl">
              Compreender como eu vejo você — e como você me percebe.
            </h1>

            <p className="mt-6 max-w-2xl text-lg leading-8 text-[#385048]/75">
              Uma experiência estruturada para comparar percepções, identificar
              convergências e divergências e apoiar conversas mais conscientes
              entre pessoas que compartilham um vínculo.
            </p>

            <div className="mt-8 flex flex-wrap gap-3">
              <button
                type="button"
                onClick={() => navigate('/avaliacoes')}
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
              >
                Fazer uma avaliação
              </button>
              <button
                type="button"
                onClick={() => navigate('/profissional/login')}
                className="rounded-xl border border-[#385048]/20 bg-white px-5 py-3 text-sm font-semibold transition hover:bg-[#A8C8B8]/10"
              >
                Área profissional
              </button>
            </div>
          </div>

          <div className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Lógica central
            </p>

            <div className="mt-6 grid grid-cols-2 gap-3 text-center text-sm">
              {['A → A', 'A → B', 'B → B', 'B → A'].map((item) => (
                <div
                  key={item}
                  className="rounded-2xl bg-[#A8C8B8]/18 px-4 py-5 font-semibold"
                >
                  {item}
                </div>
              ))}
            </div>

            <div className="mt-6 rounded-2xl bg-[#D8B078]/15 p-5">
              <p className="font-medium">Comparação de percepção</p>
              <p className="mt-2 text-sm leading-6 text-[#385048]/70">
                O que A acredita sobre B é comparado ao que B informa sobre si,
                e o mesmo acontece no sentido inverso.
              </p>
            </div>
          </div>
        </section>

        <section className="bg-[#A8C8B8]/10">
          <div className="mx-auto max-w-6xl px-6 py-16">
            <div className="max-w-2xl">
              <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
                Para diferentes relações
              </p>
              <h2 className="mt-3 text-3xl font-semibold tracking-tight">
                O vínculo muda. A necessidade de compreender o outro permanece.
              </h2>
            </div>

            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
              {audiences.map((audience) => (
                <div
                  key={audience}
                  className="rounded-2xl border border-[#A8C8B8]/40 bg-[#FEFDFB] p-5 text-sm font-medium"
                >
                  {audience}
                </div>
              ))}
            </div>
          </div>
        </section>
      </main>

      <footer className="mx-auto max-w-6xl px-6 py-8 text-sm text-[#385048]/60">
        Avaliação de Percepção Relacional
      </footer>
    </div>
  )
}

function ProfessionalLogin() {
  const [email, setEmail] = useState('')
  const [senha, setSenha] = useState('')
  const [status, setStatus] = useState('idle')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) return

    let active = true

    getAuthenticatedProfessional()
      .then(() => {
        if (active) navigate('/profissional')
      })
      .catch(() => {
        clearAuthToken()
      })

    return () => {
      active = false
    }
  }, [])

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('loading')
    setMessage('')

    try {
      const result = await loginProfessional(email, senha)
      setAuthToken(result.access_token)
      setSenha('')
      navigate('/profissional')
    } catch (error) {
      setStatus('error')
      setMessage(
        error.status === 401
          ? 'E-mail ou senha inválidos.'
          : error.message || 'Não foi possível entrar.',
      )
    }
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/')}
            className="text-left"
          >
            <p className="text-lg font-semibold tracking-tight">
              Avaliação de Percepção Relacional
            </p>
            <p className="text-xs text-[#385048]/70">Área profissional</p>
          </button>
        </div>
      </header>

      <main className="mx-auto flex min-h-[calc(100vh-90px)] max-w-6xl items-center justify-center px-6 py-12">
        <div className="w-full max-w-md rounded-3xl border border-[#A8C8B8]/45 bg-white p-8 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Acesso profissional
          </p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight">
            Entrar na plataforma
          </h1>
          <p className="mt-3 text-sm leading-6 text-[#385048]/70">
            Use o e-mail e a senha cadastrados para acessar sua área de trabalho.
          </p>

          <form className="mt-8 space-y-5" onSubmit={handleSubmit}>
            <label className="block">
              <span className="text-sm font-medium">E-mail</span>
              <input
                type="email"
                autoComplete="username"
                required
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none transition focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium">Senha</span>
              <input
                type="password"
                autoComplete="current-password"
                required
                value={senha}
                onChange={(event) => setSenha(event.target.value)}
                className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none transition focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
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

            <button
              type="submit"
              disabled={status === 'loading'}
              className="w-full rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {status === 'loading' ? 'Entrando...' : 'Entrar'}
            </button>
          </form>
        </div>
      </main>
    </div>
  )
}

function ProfessionalArea() {
  const [status, setStatus] = useState('loading')
  const [professional, setProfessional] = useState(null)

  useEffect(() => {
    let active = true

    if (!getAuthToken()) {
      navigate('/profissional/login')
      return undefined
    }

    getAuthenticatedProfessional()
      .then((result) => {
        if (!active) return
        setProfessional(result.profissional)
        setStatus('ready')
      })
      .catch((error) => {
        if (!active) return

        if (error.status === 401) {
          clearAuthToken()
          navigate('/profissional/login')
          return
        }

        setStatus('error')
      })

    return () => {
      active = false
    }
  }, [])

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] text-[#385048]">
        Validando sessão...
      </div>
    )
  }

  if (status === 'error') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] px-6 text-[#385048]">
        <div className="max-w-md rounded-2xl border border-[#D8B078]/40 bg-white p-6 text-center">
          Não foi possível validar a sessão. Verifique se a API está disponível e
          tente novamente.
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">Área profissional</p>
            <p className="text-xs text-[#385048]/65">
              Avaliação de Percepção Relacional
            </p>
          </div>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => navigate('/profissional/perfil')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold transition hover:bg-[#A8C8B8]/10"
            >
              Meu perfil
            </button>
            <button
              type="button"
              onClick={handleLogout}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold transition hover:bg-[#A8C8B8]/10"
            >
              Sair
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Sessão autenticada
          </p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight">
            Olá, {professional?.nome}.
          </h1>
          <p className="mt-3 text-[#385048]/70">
            Seu acesso profissional está ativo e foi validado pela API.
          </p>

          <dl className="mt-7 grid gap-4 sm:grid-cols-2">
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <dt className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                E-mail
              </dt>
              <dd className="mt-2 font-medium">{professional?.email}</dd>
            </div>
            <div className="rounded-2xl bg-[#A8C8B8]/12 p-5">
              <dt className="text-xs font-semibold uppercase tracking-wide text-[#385048]/55">
                Status
              </dt>
              <dd className="mt-2 font-medium">{professional?.status}</dd>
            </div>
          </dl>
        </section>

        <section className="mt-7 grid gap-4 md:grid-cols-3">
          {[
            {
              title: 'Instrumentos',
              description: 'Gerencie instrumentos e suas versões.',
              path: '/profissional/instrumentos',
            },
            {
              title: 'Pessoas',
              description: 'Cadastre participantes e mantenha seus dados administrativos.',
              path: '/profissional/pessoas',
            },
            {
              title: 'Vínculos',
              description: 'Relacione duas pessoas e preserve os lados A e B.',
              path: '/profissional/vinculos',
            },
            {
              title: 'Avaliações',
              description: 'Crie e acompanhe aplicações relacionais.',
              path: '/profissional/avaliacoes',
            },
            {
              title: 'Resultados',
              description: 'Consulte comparações e devolutivas.',
              path: null,
            },
          ].map(({ title, description, path: targetPath }) => (
            <button
              key={title}
              type="button"
              disabled={!targetPath}
              onClick={() => {
                if (targetPath) navigate(targetPath)
              }}
              className={[
                'rounded-2xl border border-[#A8C8B8]/40 bg-white p-6 text-left',
                targetPath
                  ? 'transition hover:-translate-y-0.5 hover:shadow-sm'
                  : 'cursor-default',
              ].join(' ')}
            >
              <p className="font-semibold">{title}</p>
              <p className="mt-2 text-sm leading-6 text-[#385048]/65">
                {description}
              </p>
            </button>
          ))}
        </section>
      </main>
    </div>
  )
}

export default App
