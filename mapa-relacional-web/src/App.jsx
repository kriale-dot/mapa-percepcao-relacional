import { useEffect, useState } from 'react'
import { getApiHealth } from './services/api'

const audiences = [
  'Casais e cônjuges',
  'Pais e filhos',
  'Amigos e familiares',
  'Líderes e liderados',
  'Outros vínculos interpessoais',
]

function App() {
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
              Mapa de Percepção Relacional
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
                className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
              >
                Conhecer o instrumento
              </button>
              <button
                type="button"
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
        Mapa de Percepção Relacional
      </footer>
    </div>
  )
}

export default App
