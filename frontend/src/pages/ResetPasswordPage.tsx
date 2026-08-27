import { Link } from 'react-router-dom'

export default function ResetPasswordPage() {
  return (
    <div className="flex min-h-screen items-center justify-center p-6">
      <div className="w-full max-w-md rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)] p-6">
        <h1 className="text-2xl font-semibold">Reset password</h1>
        <p className="mt-2 text-sm text-[var(--text-muted)]">
          Token-based reset form placeholder.
        </p>
        <Link to="/login" className="mt-6 inline-block text-[var(--accent)]">
          Back to sign in
        </Link>
      </div>
    </div>
  )
}
