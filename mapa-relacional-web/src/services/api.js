const API_URL = (
  import.meta.env.VITE_API_URL || 'http://localhost:8383'
).replace(/\/$/, '')

async function request(path, options = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
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

export { API_URL }
