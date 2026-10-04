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

export function getProfessionalProfile() {
  return request('/api/profissional/perfil', {
    method: 'GET',
    auth: true,
  })
}

export function updateProfessionalProfile(profile) {
  return request('/api/profissional/perfil', {
    method: 'PUT',
    auth: true,
    body: JSON.stringify(profile),
  })
}

export function changeProfessionalPassword(senhaAtual, novaSenha) {
  return request('/api/profissional/senha', {
    method: 'PUT',
    auth: true,
    body: JSON.stringify({
      senha_atual: senhaAtual,
      nova_senha: novaSenha,
    }),
  })
}

export function listInstruments() {
  return request('/api/profissional/instrumentos', {
    method: 'GET',
    auth: true,
  })
}

export function createInstrument(instrument) {
  return request('/api/profissional/instrumentos', {
    method: 'POST',
    auth: true,
    body: JSON.stringify(instrument),
  })
}

export function updateInstrument(id, instrument) {
  return request(`/api/profissional/instrumentos/${id}`, {
    method: 'PUT',
    auth: true,
    body: JSON.stringify(instrument),
  })
}

export function getInstrument(id) {
  return request(`/api/profissional/instrumentos/${id}`, {
    method: 'GET',
    auth: true,
  })
}

export function deleteInstrument(id) {
  return request(`/api/profissional/instrumentos/${id}`, {
    method: 'DELETE',
    auth: true,
  })
}

export function listInstrumentVersions(instrumentId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function createInstrumentVersion(instrumentId, numeroVersao) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify({ numero_versao: numeroVersao }),
    },
  )
}

export function updateInstrumentVersion(
  instrumentId,
  versionId,
  numeroVersao,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}`,
    {
      method: 'PUT',
      auth: true,
      body: JSON.stringify({ numero_versao: numeroVersao }),
    },
  )
}

export function publishInstrumentVersion(instrumentId, versionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/publicar`,
    {
      method: 'POST',
      auth: true,
    },
  )
}

export function archiveInstrumentVersion(instrumentId, versionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/arquivar`,
    {
      method: 'POST',
      auth: true,
    },
  )
}

export function deleteInstrumentVersion(instrumentId, versionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}`,
    {
      method: 'DELETE',
      auth: true,
    },
  )
}

export { API_URL }
