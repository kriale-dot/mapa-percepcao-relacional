import { useEffect, useState } from 'react'
import { getSiteAnalytics } from '../services/api'
import { getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export default function ProfessionalSiteStatistics() {
  const [days, setDays] = useState(30)
  const [data, setData] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!getAuthToken()) {
      navigate('/profissional/login')
      return
    }
    let active = true
    setLoading(true)
    getSiteAnalytics(days)
      .then((result) => { if (active) { setData(result); setError('') } })
      .catch((err) => { if (active) setError(err.message || 'Falha ao consultar estatísticas.') })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [days])

  const number = (value) => Number(value || 0).toLocaleString('pt-BR')

  return (
    <div className="min-h-screen bg-[#FEFDFB] px-6 py-10 text-[#385048]">
      <main className="mx-auto max-w-6xl">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <p className="text-sm text-[#385048]/65">Área profissional</p>
            <h1 className="text-3xl font-semibold">Estatísticas do site institucional</h1>
            <p className="mt-2 text-sm text-[#385048]/65">
              Somente visitas às páginas institucionais. Não inclui estatísticas de avaliações.
            </p>
          </div>
          <button onClick={() => navigate('/profissional')} className="rounded-xl border border-[#385048]/25 px-4 py-2">Voltar</button>
        </div>
        <label className="mb-6 block text-sm font-medium">
          Período
          <select className="ml-3 rounded-xl border border-[#385048]/25 bg-white px-4 py-2" value={days} onChange={(event) => setDays(Number(event.target.value))}>
            <option value={7}>7 dias</option><option value={30}>30 dias</option>
            <option value={90}>90 dias</option><option value={365}>365 dias</option>
          </select>
        </label>
        {loading ? <p>Carregando estatísticas...</p> : null}
        {error ? <p role="alert" className="rounded-xl bg-red-50 p-4">{error}</p> : null}
        {!loading && data ? (
          <>
            <div className="grid gap-4 md:grid-cols-2">
              <Stat label="Visitas acumuladas" value={number(data.total)} />
              <Stat label={`Visitas nos últimos ${days} dias`} value={number(data.periodo)} />
            </div>
            <section className="mt-6 rounded-3xl border border-[#A8C8B8]/45 bg-white p-6">
              <h2 className="text-xl font-semibold">Visitas por dia</h2>
              <div className="mt-4 space-y-2">
                {(data.evolucao || []).length === 0 ? <p className="text-sm">Ainda não há visitas no período.</p> : null}
                {(data.evolucao || []).map((item) => (
                  <div key={item.dia} className="flex items-center gap-3 text-sm">
                    <span className="w-28 shrink-0">{item.dia}</span>
                    <div className="h-3 flex-1 rounded bg-[#A8C8B8]/25">
                      <div className="h-3 rounded bg-[#88B098]" style={{ width: `${Math.max(1, Number(item.total) / Math.max(1, ...(data.evolucao || []).map((v) => Number(v.total))) * 100)}%` }} />
                    </div>
                    <span className="w-14 text-right">{number(item.total)}</span>
                  </div>
                ))}
              </div>
            </section>
            <div className="mt-6 grid gap-6 md:grid-cols-3">
              <Breakdown title="Páginas acessadas" rows={data.paginas || []} labelKey="pagina" number={number} />
              <Breakdown title="Origem dos acessos" rows={data.origens || []} labelKey="origem" number={number} />
              <Breakdown title="Dispositivos" rows={data.dispositivos || []} labelKey="dispositivo" number={number} />
            </div>
          </>
        ) : null}
      </main>
    </div>
  )
}

function Stat({ label, value }) {
  return <div className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-6">
    <p className="text-sm text-[#385048]/65">{label}</p>
    <p className="mt-3 text-3xl font-semibold">{value}</p>
  </div>
}

function Breakdown({ title, rows, labelKey, number }) {
  return <section className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-6">
    <h2 className="mb-4 text-lg font-semibold">{title}</h2>
    {rows.length === 0 ? <p className="text-sm">Sem dados no período.</p> : null}
    {rows.map((row, index) => <div className="flex justify-between gap-3 border-b border-[#A8C8B8]/25 py-2 text-sm" key={index}>
      <span className="min-w-0 break-words">{row[labelKey]}</span><strong>{number(row.total)}</strong>
    </div>)}
  </section>
}
