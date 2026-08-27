import { Link } from 'react-router-dom'

export default function ForgotPasswordPage() {
  return (
    <div className="flex min-h-screen items-center justify-center p-6">
      <div className="w-full max-w-md rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)] p-6">
        <h1 className="text-2xl font-semibold">Forgot password</h1>
        <p className="mt-2 text-sm text-[var(--text-muted)]">
          Password reset email flow will be wired to Laravel notifications next.
        </p>
        <Link to="/login" className="mt-6 inline-block text-[var(--accent)]">
          Back to sign in
        </Link>
      </div>
    </div>
  )
}
