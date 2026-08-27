import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function LoginPage() {
  const { user, loading, login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('superadmin@mdm.local')
  const [password, setPassword] = useState('password')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  if (!loading && user) return <Navigate to="/" replace />

  async function onSubmit(e: FormEvent) {
    e.preventDefault()
    setSubmitting(true)
    setError(null)
    try {
      await login(email, password)
      navigate('/')
    } catch {
      setError('Invalid credentials')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center p-6">
      <div className="w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="text-xs uppercase tracking-[0.25em] text-[var(--text-muted)]">MDM Platform</div>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight">Sign in</h1>
          <p className="mt-2 text-sm text-[var(--text-muted)]">Manage supervised devices from one console.</p>
        </div>
        <form
          onSubmit={onSubmit}
          className="rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/80 p-6 shadow-xl backdrop-blur"
        >
          <label className="mb-4 block text-sm">
            <span className="mb-1.5 block text-[var(--text-muted)]">Email</span>
            <input
              className="w-full rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 py-2 outline-none focus:border-[var(--accent)]"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              type="email"
              required
            />
          </label>
          <label className="mb-4 block text-sm">
            <span className="mb-1.5 block text-[var(--text-muted)]">Password</span>
            <input
              className="w-full rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 py-2 outline-none focus:border-[var(--accent)]"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              type="password"
              required
            />
          </label>
          {error && <p className="mb-3 text-sm text-[var(--danger)]">{error}</p>}
          <button
            type="submit"
            disabled={submitting}
            className="w-full rounded-lg bg-[var(--accent)] px-4 py-2.5 font-medium text-white hover:bg-[var(--accent-hover)] disabled:opacity-60"
          >
            {submitting ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
          <p className="mt-4 text-center text-xs text-[var(--text-muted)]">
            Demo: superadmin@mdm.local / password ·{' '}
            <a className="text-[var(--accent)]" href="/forgot-password">
              Forgot password
            </a>
          </p>
      </div>
    </div>
  )
}
