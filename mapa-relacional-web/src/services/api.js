import { getAuthToken } from './auth'

const API_URL = (
  import.meta.env.VITE_API_URL || 'http://localhost:8383'
).replace(/\/$/, '')

async function request(path, options = {}) {
  const token = options.auth ? getAuthToken() : null

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  })

  const data = await response.json().catch(() => null)

  if (!response.ok) {
    const error = new Error(
      data?.message || `Erro HTTP ${response.status}`,
    )
    error.status = response.status
    error.data = data
    throw error
  }

  return data
}

export function getApiHealth() {
  return request('/api/health')
}

export function loginProfessional(email, senha) {
  return request('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, senha }),
  })
}

export function getAuthenticatedProfessional() {
  return request('/api/profissional/me', {
    method: 'GET',
    auth: true,
  })
}

export { API_URL }
