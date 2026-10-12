import { recordSiteVisit } from '../services/api'

// Sessao unica por aba, renovada depois de 30 minutos sem navegacao.
const KEY = 'site_analytics_session_v1'
const IDLE_MS = 30 * 60 * 1000

export function trackInstitutionalPage(page) {
  if (page !== '/' && page !== '/biblioteca') return
  try {
    const now = Date.now()
    let current
    try { current = JSON.parse(sessionStorage.getItem(KEY) || 'null') } catch { current = null }
    if (!current || !/^[a-f0-9]{32}$/.test(current.token || '') ||
      !Number.isFinite(current.lastActivity) || now - current.lastActivity > IDLE_MS) {
      const bytes = new Uint8Array(16)
      crypto.getRandomValues(bytes)
      current = { token: Array.from(bytes, (n) => n.toString(16).padStart(2, '0')).join('') }
    }
    current.lastActivity = now
    sessionStorage.setItem(KEY, JSON.stringify(current))
    const ua = navigator.userAgent
    const device = /tablet|ipad/i.test(ua) ? 'tablet' : /mobi|android|iphone/i.test(ua) ? 'celular' : 'desktop'
    // Apenas o hostname do referenciador sera usado no servidor.
    return recordSiteVisit({ sessao: current.token, pagina: page, origem: document.referrer, dispositivo: device }).catch(() => {})
  } catch {
    // Analise de trafego nao interfere na navegacao publica.
  }
}
