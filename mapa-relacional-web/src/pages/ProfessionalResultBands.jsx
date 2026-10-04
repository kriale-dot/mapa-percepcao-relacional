import { useEffect, useState } from 'react'
import {
  getResultBands,
  updateResultBands,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function formatValue(value) {
  if (value === '' || value == null) return ''
  return String(Number(value).toFixed(2)).replace('.', ',')
}

export default function ProfessionalResultBands({
  instrumentId,
  versionId,
}) {
  const [version, setVersion] = useState(null)
  const [bands, setBands] = useState([])
  const [editable, setEditable] = useState(false)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }

    load()
  }, [instrumentId, versionId])

  async function load() {
    setStatus('loading')
    setMessage('')

    try {
      const result = await getResultBands(instrumentId, versionId)
      setVersion(result.versao)
      setEditable(Boolean(result.editavel))
      setBands(result.faixas || [])
      setStatus('ready')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('error')
      setMessage(
        error.message || 'Não foi possível carregar as faixas de resultado.',
      )
    }
  }

  function updateBand(index, field, value) {
    setBands((current) =>
      current.map((band, currentIndex) =>
        currentIndex === index
          ? {
              ...band,
              [field]: value,
            }
          : band,
      ),
    )
  }

  function addBand() {
    if (!editable || bands.length >= 10) return

    setBands((current) => [
      ...current,
      {
        id: null,
        codigo: '',
        rotulo: '',
        minimo: '',
        maximo: '',
        ordem: current.length + 1,
      },
    ])
  }

  function removeBand(index) {
    if (!editable || bands.length <= 1) return

    setBands((current) =>
      current
        .filter((_, currentIndex) => currentIndex !== index)
        .map((band, currentIndex) => ({
          ...band,
          ordem: currentIndex + 1,
        })),
    )
  }

  async function handleSave(event) {
    event.preventDefault()

    if (!editable) return

    setStatus('saving')
    setMessage('')

    try {
      const payload = bands.map((band) => ({
        codigo: band.codigo || '',
        rotulo: String(band.rotulo || '').trim(),
        minimo: Number(String(band.minimo).replace(',', '.')),
        maximo: Number(String(band.maximo).replace(',', '.')),
      }))

      const result = await updateResultBands(
        instrumentId,
        versionId,
        payload,
      )

      setVersion(result.versao)
      setEditable(Boolean(result.editavel))
      setBands(result.faixas || [])
      setStatus('ready')
      setMessage('Faixas de resultado atualizadas com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(
        error.message || 'Não foi possível atualizar as faixas.',
      )
    }
  }

  if (status === 'loading') {
    return (
      <div className="min-h-screen bg-[#FEFDFB] p-8 text-[#385048]">
        Carregando faixas de resultado...
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-5">
          <div>
            <p className="text-lg font-semibold">
              Faixas de resultado
            </p>
            <p className="text-xs text-[#385048]/65">
              {version?.instrumento_nome || 'Instrumento'} · versão{' '}
              {version?.numero_versao || ''}
            </p>
          </div>

          <button
            type="button"
            onClick={() =>
              navigate(
                `/profissional/instrumentos/${instrumentId}/versoes`,
              )
            }
            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
          >
            Voltar
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-5xl px-6 py-10">
        <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm">
          <div className="flex flex-wrap items-center gap-3">
            <span className="rounded-full bg-[#A8C8B8]/20 px-3 py-1 text-xs font-semibold">
              {version?.status}
            </span>
            <span className="text-sm text-[#385048]/60">
              Interpretação percentual da versão
            </span>
          </div>

          <h1 className="mt-4 text-3xl font-semibold">
            Configurar faixas
          </h1>

          <p className="mt-4 max-w-3xl text-sm leading-7 text-[#385048]/70">
            As faixas transformam o percentual técnico em uma classificação
            interpretativa. Elas precisam cobrir todo o intervalo de 0 a 100,
            sem lacunas nem sobreposições.
          </p>

          {!editable ? (
            <div className="mt-6 rounded-2xl bg-[#D8B078]/15 p-5 text-sm leading-6">
              Esta versão já foi publicada ou arquivada. As faixas ficam
              somente para leitura para preservar os resultados históricos.
              Para alterar a interpretação, crie uma nova versão do
              instrumento.
            </div>
          ) : (
            <div className="mt-6 rounded-2xl bg-[#A8C8D0]/14 p-5 text-sm leading-6">
              Enquanto a versão estiver em rascunho, você pode alterar nomes,
              limites e quantidade de faixas. Depois de publicar, essa
              configuração ficará congelada.
            </div>
          )}

          {message ? (
            <div className="mt-5 rounded-2xl bg-[#D8B078]/15 px-5 py-4 text-sm">
              {message}
            </div>
          ) : null}
        </section>

        {status === 'error' ? (
          <section className="mt-7 rounded-3xl border border-[#D8B078]/45 bg-white p-7">
            <p>{message}</p>
            <button
              type="button"
              onClick={load}
              className="mt-5 rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
            >
              Tentar novamente
            </button>
          </section>
        ) : (
          <form className="mt-7" onSubmit={handleSave}>
            <div className="space-y-4">
              {bands.map((band, index) => (
                <article
                  key={band.id || `new-${index}`}
                  className="rounded-3xl border border-[#A8C8B8]/40 bg-white p-6"
                >
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#385048]/50">
                        Faixa {index + 1}
                      </p>
                      {!editable ? (
                        <h2 className="mt-2 text-xl font-semibold">
                          {band.rotulo}
                        </h2>
                      ) : null}
                    </div>

                    {editable && bands.length > 1 ? (
                      <button
                        type="button"
                        onClick={() => removeBand(index)}
                        className="rounded-xl border border-[#C97C5D]/35 px-3 py-2 text-xs font-semibold"
                      >
                        Remover
                      </button>
                    ) : null}
                  </div>

                  <div className="mt-5 grid gap-4 md:grid-cols-[1.3fr_1fr_1fr]">
                    <label className="block">
                      <span className="text-sm font-medium">Nome da faixa</span>
                      <input
                        type="text"
                        required
                        maxLength="60"
                        disabled={!editable}
                        value={band.rotulo || ''}
                        onChange={(event) =>
                          updateBand(index, 'rotulo', event.target.value)
                        }
                        className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none disabled:opacity-70"
                      />
                    </label>

                    <label className="block">
                      <span className="text-sm font-medium">De (%)</span>
                      <input
                        type="number"
                        required
                        min="0"
                        max="100"
                        step="0.01"
                        disabled={!editable}
                        value={band.minimo}
                        onChange={(event) =>
                          updateBand(index, 'minimo', event.target.value)
                        }
                        className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none disabled:opacity-70"
                      />
                    </label>

                    <label className="block">
                      <span className="text-sm font-medium">Até (%)</span>
                      <input
                        type="number"
                        required
                        min="0"
                        max="100"
                        step="0.01"
                        disabled={!editable}
                        value={band.maximo}
                        onChange={(event) =>
                          updateBand(index, 'maximo', event.target.value)
                        }
                        className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none disabled:opacity-70"
                      />
                    </label>
                  </div>

                  {!editable ? (
                    <p className="mt-4 text-sm text-[#385048]/60">
                      Intervalo: {formatValue(band.minimo)}% a{' '}
                      {formatValue(band.maximo)}%
                    </p>
                  ) : null}
                </article>
              ))}
            </div>

            {editable ? (
              <div className="mt-7 flex flex-wrap gap-3">
                <button
                  type="button"
                  disabled={bands.length >= 10}
                  onClick={addBand}
                  className="rounded-xl border border-[#A8C8D0] px-5 py-3 text-sm font-semibold disabled:opacity-45"
                >
                  Adicionar faixa
                </button>

                <button
                  type="submit"
                  disabled={status === 'saving'}
                  className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
                >
                  {status === 'saving'
                    ? 'Salvando...'
                    : 'Salvar faixas'}
                </button>
              </div>
            ) : null}

            <div className="mt-7 rounded-2xl border border-[#A8C8B8]/40 bg-white p-5 text-sm leading-6">
              <strong>Regra:</strong> a primeira faixa deve começar em 0,00%,
              a última deve terminar em 100,00% e cada faixa seguinte deve
              começar exatamente 0,01 ponto percentual após a anterior.
            </div>
          </form>
        )}
      </main>
    </div>
  )
}
