import { useState } from 'react'
import { changeProfessionalPassword } from '../services/api'
import { clearAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

export default function ProfessionalPassword() {
  const [senhaAtual, setSenhaAtual] = useState('')
  const [novaSenha, setNovaSenha] = useState('')
  const [confirmacao, setConfirmacao] = useState('')
  const [status, setStatus] = useState('idle')
  const [message, setMessage] = useState('')

  async function handleSubmit(event) {
    event.preventDefault()
    setMessage('')

    if (novaSenha.length < 8) {
      setMessage('A nova senha deve ter pelo menos 8 caracteres.')
      return
    }

    if (novaSenha !== confirmacao) {
      setMessage('A confirmação da nova senha não confere.')
      return
    }

    setStatus('saving')

    try {
      const result = await changeProfessionalPassword(senhaAtual, novaSenha)
      clearAuthToken()
      setSenhaAtual('')
      setNovaSenha('')
      setConfirmacao('')
      setStatus('success')
      setMessage(result.message || 'Senha alterada com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('idle')
      setMessage(error.message || 'Não foi possível alterar a senha.')
    }
  }

  if (status === 'success') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] px-6 text-[#385048]">
        <div className="w-full max-w-md rounded-3xl border border-[#A8C8B8]/45 bg-white p-8 text-center shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Senha atualizada
          </p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight">
            Alteração concluída
          </h1>
          <p className="mt-4 text-sm leading-6 text-[#385048]/70">
            {message} Por segurança, sua sessão foi encerrada.
          </p>
          <button
            type="button"
            onClick={() => navigate('/profissional/login')}
            className="mt-7 w-full rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white"
          >
            Entrar novamente
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/profissional/perfil')}
            className="text-left"
          >
            <p className="text-lg font-semibold">Alterar senha</p>
            <p className="text-xs text-[#385048]/65">
              Avaliação de Percepção Relacional
            </p>
          </button>

          <button
            type="button"
            onClick={() => navigate('/profissional/perfil')}
            className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
          >
            Voltar
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-xl px-6 py-10">
        <form
          onSubmit={handleSubmit}
          className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm"
        >
          <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
            Segurança da conta
          </p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight">
            Definir nova senha
          </h1>
          <p className="mt-3 text-sm leading-6 text-[#385048]/70">
            Informe sua senha atual e escolha uma nova senha com pelo menos
            8 caracteres.
          </p>

          <div className="mt-8 space-y-5">
            <PasswordField
              label="Senha atual"
              autoComplete="current-password"
              value={senhaAtual}
              onChange={setSenhaAtual}
            />
            <PasswordField
              label="Nova senha"
              autoComplete="new-password"
              value={novaSenha}
              onChange={setNovaSenha}
            />
            <PasswordField
              label="Confirmar nova senha"
              autoComplete="new-password"
              value={confirmacao}
              onChange={setConfirmacao}
            />
          </div>

          {message ? (
            <div
              role="alert"
              className="mt-6 rounded-xl bg-[#D8B078]/18 px-4 py-3 text-sm"
            >
              {message}
            </div>
          ) : null}

          <button
            type="submit"
            disabled={status === 'saving'}
            className="mt-7 w-full rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-60"
          >
            {status === 'saving' ? 'Alterando...' : 'Alterar senha'}
          </button>
        </form>
      </main>
    </div>
  )
}

function PasswordField({ label, autoComplete, value, onChange }) {
  return (
    <label className="block">
      <span className="text-sm font-medium">{label}</span>
      <input
        type="password"
        required
        autoComplete={autoComplete}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none transition focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
      />
    </label>
  )
}
