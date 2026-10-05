import { getAuthToken } from './auth'

const API_URL = (
  import.meta.env.VITE_API_URL || 'http://localhost:8383'
).replace(/\/$/, '')

async function request(path, options = {}) {
  const token = options.auth ? getAuthToken() : null

  const isFormData =
    typeof FormData !== 'undefined' && options.body instanceof FormData

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(!isFormData ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  })

  const raw = await response.text()
  let data = null

  if (raw.trim() !== '') {
    try {
      data = JSON.parse(raw)
    } catch {
      const detail = import.meta.env.DEV
        ? ` Conteúdo recebido: ${raw.slice(0, 500)}`
        : ''
      const error = new Error(
        `Resposta inválida da API (HTTP ${response.status}).${detail}`,
      )
      error.status = response.status
      error.raw = raw
      throw error
    }
  }

  if (!response.ok) {
    const error = new Error(
      data?.message || `Erro HTTP ${response.status}`,
    )
    error.status = response.status
    error.data = data
    throw error
  }

  if (data === null) {
    const error = new Error(
      `A API respondeu sem conteúdo (HTTP ${response.status}).`,
    )
    error.status = response.status
    throw error
  }

  return data
}

export function getApiHealth() {
  return request('/api/health')
}

export function getPublicSite() {
  return request('/api/public/site', {
    method: 'GET',
  })
}

export function listPublicEvaluations() {
  return request('/api/public/avaliacoes', {
    method: 'GET',
  })
}

export function getPublicEvaluation(versionId) {
  return request(`/api/public/avaliacoes/${versionId}`, {
    method: 'GET',
  })
}

export function startPublicEvaluation(versionId, application) {
  return request(`/api/public/avaliacoes/${versionId}/iniciar`, {
    method: 'POST',
    body: JSON.stringify(application),
  })
}

export function getParticipantAccess(token) {
  return request(`/api/public/acessos/${token}`, {
    method: 'GET',
  })
}

export function identifyParticipant(token, identification) {
  return request(`/api/public/acessos/${token}/identificacao`, {
    method: 'POST',
    body: JSON.stringify(identification),
  })
}

export function getParticipantQuestionnaire(token) {
  return request(`/api/public/acessos/${token}/questionario`, {
    method: 'GET',
  })
}

export function saveParticipantResponse(token, itemId, answer) {
  return request(
    `/api/public/acessos/${token}/respostas/${itemId}`,
    {
      method: 'PUT',
      body: JSON.stringify(answer),
    },
  )
}

export function markParticipantItemNotApplicable(token, itemId, reason = null) {
  return request(
    `/api/public/acessos/${token}/itens/${itemId}/nao-se-aplica`,
    {
      method: 'POST',
      body: JSON.stringify({ motivo: reason }),
    },
  )
}

export function completeParticipantEvaluation(token) {
  return request(`/api/public/acessos/${token}/concluir`, {
    method: 'POST',
    body: JSON.stringify({}),
  })
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

export function deleteProfessionalProfileImage(type) {
  return request(`/api/profissional/perfil/imagem/${type}`, {
    method: 'DELETE',
    auth: true,
  })
}

export function uploadSiteImage(file) {
  const form = new FormData()
  form.append('imagem', file)

  return request('/api/profissional/site/upload-imagem', {
    method: 'POST',
    auth: true,
    body: form,
  })
}

export function listSiteBlocks() {
  return request('/api/profissional/site/blocos', {
    method: 'GET',
    auth: true,
  })
}

export function createSiteBlock(block) {
  return request('/api/profissional/site/blocos', {
    method: 'POST',
    auth: true,
    body: JSON.stringify(block),
  })
}

export function updateSiteBlock(id, block) {
  return request(`/api/profissional/site/blocos/${id}`, {
    method: 'PUT',
    auth: true,
    body: JSON.stringify(block),
  })
}

export function deleteSiteBlock(id) {
  return request(`/api/profissional/site/blocos/${id}`, {
    method: 'DELETE',
    auth: true,
  })
}

export function moveSiteBlock(id, direction) {
  return request(`/api/profissional/site/blocos/${id}/mover`, {
    method: 'POST',
    auth: true,
    body: JSON.stringify({ direcao: direction }),
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

export function listPeople() {
  return request('/api/profissional/pessoas', {
    method: 'GET',
    auth: true,
  })
}

export function createPerson(person) {
  return request('/api/profissional/pessoas', {
    method: 'POST',
    auth: true,
    body: JSON.stringify(person),
  })
}

export function updatePerson(id, person) {
  return request(`/api/profissional/pessoas/${id}`, {
    method: 'PUT',
    auth: true,
    body: JSON.stringify(person),
  })
}

export function deletePerson(id) {
  return request(`/api/profissional/pessoas/${id}`, {
    method: 'DELETE',
    auth: true,
  })
}

export function listRelationships() {
  return request('/api/profissional/vinculos', {
    method: 'GET',
    auth: true,
  })
}

export function createRelationship(relationship) {
  return request('/api/profissional/vinculos', {
    method: 'POST',
    auth: true,
    body: JSON.stringify(relationship),
  })
}

export function updateRelationship(id, relationship) {
  return request(`/api/profissional/vinculos/${id}`, {
    method: 'PUT',
    auth: true,
    body: JSON.stringify(relationship),
  })
}

export function deleteRelationship(id) {
  return request(`/api/profissional/vinculos/${id}`, {
    method: 'DELETE',
    auth: true,
  })
}

export function getProfessionalDashboard() {
  return request('/api/profissional/dashboard', {
    method: 'GET',
    auth: true,
  })
}

export function listApplications(filters = {}) {
  const params = new URLSearchParams()

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.set(key, String(value))
    }
  })

  const query = params.toString()

  return request(
    `/api/profissional/aplicacoes${query ? `?${query}` : ''}`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function getApplication(id) {
  return request(`/api/profissional/aplicacoes/${id}`, {
    method: 'GET',
    auth: true,
  })
}

export function getApplicationOptions() {
  return request('/api/profissional/aplicacoes/opcoes', {
    method: 'GET',
    auth: true,
  })
}

export function createApplication(application) {
  return request('/api/profissional/aplicacoes', {
    method: 'POST',
    auth: true,
    body: JSON.stringify(application),
  })
}

export function getApplicationResults(id) {
  return request(`/api/profissional/aplicacoes/${id}/resultados`, {
    method: 'GET',
    auth: true,
  })
}

export function calculateApplicationResults(id) {
  return request(
    `/api/profissional/aplicacoes/${id}/resultados/calcular`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify({}),
    },
  )
}

export function getApplicationFeedback(id) {
  return request(`/api/profissional/aplicacoes/${id}/devolutiva`, {
    method: 'GET',
    auth: true,
  })
}

export function saveApplicationFeedback(id, feedback) {
  return request(`/api/profissional/aplicacoes/${id}/devolutiva`, {
    method: 'PUT',
    auth: true,
    body: JSON.stringify(feedback),
  })
}

export function releaseApplicationFeedback(id) {
  return request(
    `/api/profissional/aplicacoes/${id}/devolutiva/liberar`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify({}),
    },
  )
}

export function resendParticipantAccess(id, side) {
  return request(
    `/api/profissional/aplicacoes/${id}/acessos/${side}/reenviar`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify({}),
    },
  )
}

export function resendApplicationFeedback(id) {
  return request(
    `/api/profissional/aplicacoes/${id}/devolutiva/reenviar`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify({}),
    },
  )
}

export function listAuditEvents(filters = {}) {
  const params = new URLSearchParams()

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.set(key, String(value))
    }
  })

  const query = params.toString()

  return request(
    `/api/profissional/auditoria${query ? `?${query}` : ''}`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function getPublicResult(token) {
  return request(`/api/public/resultados/${token}`, {
    method: 'GET',
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

export function getResultBands(instrumentId, versionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/faixas-resultados`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function updateResultBands(instrumentId, versionId, bands) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/faixas-resultados`,
    {
      method: 'PUT',
      auth: true,
      body: JSON.stringify({ faixas: bands }),
    },
  )
}

export function listSections(instrumentId, versionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function createSection(instrumentId, versionId, section) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify(section),
    },
  )
}

export function updateSection(
  instrumentId,
  versionId,
  sectionId,
  section,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}`,
    {
      method: 'PUT',
      auth: true,
      body: JSON.stringify(section),
    },
  )
}

export function deleteSection(instrumentId, versionId, sectionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}`,
    {
      method: 'DELETE',
      auth: true,
    },
  )
}

export function listItems(instrumentId, versionId, sectionId) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function createItem(instrumentId, versionId, sectionId, item) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify(item),
    },
  )
}

export function updateItem(
  instrumentId,
  versionId,
  sectionId,
  itemId,
  item,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${itemId}`,
    {
      method: 'PUT',
      auth: true,
      body: JSON.stringify(item),
    },
  )
}

export function deleteItem(
  instrumentId,
  versionId,
  sectionId,
  itemId,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${itemId}`,
    {
      method: 'DELETE',
      auth: true,
    },
  )
}

export function listAlternatives(
  instrumentId,
  versionId,
  sectionId,
  itemId,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${itemId}/alternativas`,
    {
      method: 'GET',
      auth: true,
    },
  )
}

export function createAlternative(
  instrumentId,
  versionId,
  sectionId,
  itemId,
  alternative,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${itemId}/alternativas`,
    {
      method: 'POST',
      auth: true,
      body: JSON.stringify(alternative),
    },
  )
}

export function updateAlternative(
  instrumentId,
  versionId,
  sectionId,
  itemId,
  alternativeId,
  alternative,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${itemId}/alternativas/${alternativeId}`,
    {
      method: 'PUT',
      auth: true,
      body: JSON.stringify(alternative),
    },
  )
}

export function deleteAlternative(
  instrumentId,
  versionId,
  sectionId,
  itemId,
  alternativeId,
) {
  return request(
    `/api/profissional/instrumentos/${instrumentId}/versoes/${versionId}/secoes/${sectionId}/itens/${itemId}/alternativas/${alternativeId}`,
    {
      method: 'DELETE',
      auth: true,
    },
  )
}

export { API_URL }
