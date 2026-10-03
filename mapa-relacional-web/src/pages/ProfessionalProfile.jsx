import { useEffect, useState } from 'react'
import {
  getProfessionalProfile,
  updateProfessionalProfile,
} from '../services/api'
import { clearAuthToken, getAuthToken } from '../services/auth'

function navigate(path) {
  window.history.pushState({}, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const emptyProfile = {
  nome: '',
  email: '',
  telefone: '',
  descricao: '',
  atuacao: '',
  foto_url: '',
  logo_url: '',
  dados_contato: '',
  status: '',
}

export default function ProfessionalProfile() {
  const [profile, setProfile] = useState(emptyProfile)
  const [status, setStatus] = useState('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    let active = true

    if (!getAuthToken()) {
      navigate('/profissional/login')
      return undefined
    }

    getProfessionalProfile()
      .then((result) => {
        if (!active) return

        setProfile({
          ...emptyProfile,
          ...result.profissional,
        })
        setStatus('ready')
      })
      .catch((error) => {
        if (!active) return

        if (error.status === 401) {
          clearAuthToken()
          navigate('/profissional/login')
          return
        }

        setMessage(error.message || 'Não foi possível carregar o perfil.')
        setStatus('error')
      })

    return () => {
      active = false
    }
  }, [])

  function updateField(field, value) {
    setProfile((current) => ({
      ...current,
      [field]: value,
    }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setStatus('saving')
    setMessage('')

    try {
      const result = await updateProfessionalProfile({
        nome: profile.nome,
        email: profile.email,
        telefone: profile.telefone,
        descricao: profile.descricao,
        atuacao: profile.atuacao,
        foto_url: profile.foto_url,
        logo_url: profile.logo_url,
        dados_contato: profile.dados_contato,
      })

      setProfile({
        ...emptyProfile,
        ...result.profissional,
      })
      setStatus('ready')
      setMessage('Perfil atualizado com sucesso.')
    } catch (error) {
      if (error.status === 401) {
        clearAuthToken()
        navigate('/profissional/login')
        return
      }

      setStatus('ready')
      setMessage(error.message || 'Não foi possível salvar o perfil.')
    }
  }

  function handleLogout() {
    clearAuthToken()
    navigate('/profissional/login')
  }

  if (status === 'loading') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] text-[#385048]">
        Carregando perfil...
      </div>
    )
  }

  if (status === 'error') {
    return (
      <div className="flex min-h-screen items-center justify-center bg-[#FEFDFB] px-6 text-[#385048]">
        <div className="max-w-md rounded-2xl border border-[#D8B078]/40 bg-white p-6 text-center">
          {message}
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-[#FEFDFB] text-[#385048]">
      <header className="border-b border-[#A8C8B8]/45 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
          <button
            type="button"
            onClick={() => navigate('/profissional')}
            className="text-left"
          >
            <p className="text-lg font-semibold">Perfil profissional</p>
            <p className="text-xs text-[#385048]/65">
              Avaliação de Percepção Relacional
            </p>
          </button>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => navigate('/profissional')}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Voltar
            </button>
            <button
              type="button"
              onClick={handleLogout}
              className="rounded-xl border border-[#385048]/20 px-4 py-2 text-sm font-semibold"
            >
              Sair
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-4xl px-6 py-10">
        <form
          onSubmit={handleSubmit}
          className="rounded-3xl border border-[#A8C8B8]/45 bg-white p-7 shadow-sm"
        >
          <div className="mb-8">
            <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#385048]/55">
              Dados do perfil
            </p>
            <h1 className="mt-3 text-3xl font-semibold tracking-tight">
              Informações profissionais
            </h1>
            <p className="mt-3 text-sm leading-6 text-[#385048]/70">
              Estes dados poderão ser usados na área profissional e,
              futuramente, no site institucional.
            </p>
          </div>

          <div className="grid gap-5 md:grid-cols-2">
            <Field
              label="Nome"
              type="text"
              required
              value={profile.nome}
              onChange={(value) => updateField('nome', value)}
            />
            <Field
              label="E-mail"
              type="email"
              required
              value={profile.email}
              onChange={(value) => updateField('email', value)}
            />
            <Field
              label="Telefone"
              type="text"
              value={profile.telefone || ''}
              onChange={(value) => updateField('telefone', value)}
            />
            <Field
              label="Status"
              type="text"
              value={profile.status || ''}
              disabled
              onChange={() => {}}
            />
          </div>

          <div className="mt-5 grid gap-5">
            <TextArea
              label="Descrição / apresentação profissional"
              value={profile.descricao || ''}
              onChange={(value) => updateField('descricao', value)}
            />
            <TextArea
              label="Informações de atuação"
              value={profile.atuacao || ''}
              onChange={(value) => updateField('atuacao', value)}
            />
            <TextArea
              label="Dados de contato"
              value={profile.dados_contato || ''}
              onChange={(value) => updateField('dados_contato', value)}
            />
          </div>

          <div className="mt-5 grid gap-5 md:grid-cols-2">
            <Field
              label="URL da fotografia"
              type="url"
              value={profile.foto_url || ''}
              onChange={(value) => updateField('foto_url', value)}
            />
            <Field
              label="URL do logotipo"
              type="url"
              value={profile.logo_url || ''}
              onChange={(value) => updateField('logo_url', value)}
            />
          </div>

          {message ? (
            <div className="mt-6 rounded-xl bg-[#A8C8B8]/18 px-4 py-3 text-sm">
              {message}
            </div>
          ) : null}

          <div className="mt-7 flex justify-end">
            <button
              type="submit"
              disabled={status === 'saving'}
              className="rounded-xl bg-[#385048] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-60"
            >
              {status === 'saving' ? 'Salvando...' : 'Salvar perfil'}
            </button>
          </div>
        </form>
      </main>
    </div>
  )
}

function Field({
  label,
  type,
  value,
  onChange,
  required = false,
  disabled = false,
}) {
  return (
    <label className="block">
      <span className="text-sm font-medium">{label}</span>
      <input
        type={type}
        required={required}
        disabled={disabled}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none transition focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20 disabled:cursor-not-allowed disabled:bg-[#A8C8B8]/10"
      />
    </label>
  )
}

function TextArea({ label, value, onChange }) {
  return (
    <label className="block">
      <span className="text-sm font-medium">{label}</span>
      <textarea
        rows="4"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="mt-2 w-full rounded-xl border border-[#385048]/20 bg-[#FEFDFB] px-4 py-3 outline-none transition focus:border-[#88B098] focus:ring-2 focus:ring-[#88B098]/20"
      />
    </label>
  )
}
