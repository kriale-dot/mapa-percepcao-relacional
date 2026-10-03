const TOKEN_KEY = 'mapa_relacional_professional_token'

export function getAuthToken() {
  return window.sessionStorage.getItem(TOKEN_KEY)
}

export function setAuthToken(token) {
  window.sessionStorage.setItem(TOKEN_KEY, token)
}

export function clearAuthToken() {
  window.sessionStorage.removeItem(TOKEN_KEY)
}

export function hasAuthToken() {
  return Boolean(getAuthToken())
}
