import { useState } from 'react'
import {
  requestProfessionalPasswordReset,
  resetProfessionalPassword,
} from '../services/api'

function go(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export default function ProfessionalPasswordRecovery({ mode }) {
  const token = new URLSearchParams(window.location.search).get('token') || ''
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [busy, setBusy] = useState(false)
  const [success, setSuccess] = useState(false)
  const [message, setMessage] = useState('')
  const reset = mode === 'reset'

  async function submit(event) {
    event.preventDefault()
    setMessage('')
    if (reset && password !== confirmation) {
      setMessage('As senhas não coincidem.')
      return
    }
    setBusy(true)
    try {
      const result = reset
        ? await resetProfessionalPassword(token, password)
        : await requestProfessionalPasswordReset(email)
      setSuccess(true)
      setMessage(result.message)
      setPassword('')
      setConfirmation('')
    } catch (error) {
      setMessage(error.message || 'Não foi possível concluir a solicitação.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-[#FEFDFB] px-6 py-12 text-[#385048]">
      <div className="w-full max-w-md rounded-3xl border border-[#A8C8B8]/45 bg-white p-8 shadow-sm">
        <p className="text-sm font-semibold uppercase tracking-wide text-[#385048]/60">
          Área profissional
        </p>
        <h1 className="mt-3 text-3xl font-semibold">
          {reset ? 'Definir nova senha' : 'Recuperar senha'}
        </h1>
        <p className="mt-3 text-sm leading-6 text-[#385048]/70">
          {reset
            ? 'Informe uma nova senha de pelo menos 8 caracteres. O link vale por 30 minutos.'
            : 'Digite o e-mail do seu cadastro profissional. Caso exista uma conta ativa, você receberá um link de recuperação.'}
        </p>
        {reset && !token ? (
          <p role="alert" className="mt-5 rounded-xl bg-[#D8B078]/20 p-4 text-sm">
            Link incompleto. Solicite um novo e-mail de recuperação.
          </p>
        ) : null}
        {!success && (!reset || token) && (
          <form onSubmit={submit} className="mt-7 space-y-4">
            {!reset ? (
              <label className="block text-sm font-medium">
                E-mail
                <input type="email" required autoComplete="email" value={email}
                  onChange={event => setEmail(event.target.value)}
                  className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3" />
              </label>
            ) : (
              <>
                <label className="block text-sm font-medium">
                  Nova senha
                  <input type="password" required minLength={8} autoComplete="new-password"
                    value={password} onChange={event => setPassword(event.target.value)}
                    className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3" />
                </label>
                <label className="block text-sm font-medium">
                  Confirmar nova senha
                  <input type="password" required minLength={8} autoComplete="new-password"
                    value={confirmation} onChange={event => setConfirmation(event.target.value)}
                    className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3" />
                </label>
              </>
            )}
            <button type="submit" disabled={busy}
              className="w-full rounded-xl bg-[#385048] px-5 py-3 font-semibold text-white disabled:opacity-60">
              {busy ? 'Aguarde...' : reset ? 'Salvar nova senha' : 'Enviar link'}
            </button>
          </form>
        )}
        {message && (
          <p role="status" className="mt-5 rounded-xl bg-[#A8C8B8]/25 p-4 text-sm">
            {message}
          </p>
        )}
        <button type="button" onClick={() => go('/profissional/login')}
          className="mt-6 text-sm font-semibold underline underline-offset-4">
          Voltar ao login
        </button>
      </div>
    </main>
  )
}
