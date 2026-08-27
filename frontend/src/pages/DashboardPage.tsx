import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../context/AuthContext'

type Dash = {
  organizations: number
  devices: number
  online_devices: number
  pending_commands: number
  mdm_engine: string
}

export default function DashboardPage() {
  const { user } = useAuth()
  const [data, setData] = useState<Dash | null>(null)

  useEffect(() => {
    api.get('/dashboard').then((res) => setData(res.data.data))
  }, [])

  const cards = [
    { label: 'Devices', value: data?.devices ?? '—' },
    { label: 'Online', value: data?.online_devices ?? '—' },
    { label: 'Pending commands', value: data?.pending_commands ?? '—' },
    { label: 'MDM engine', value: data?.mdm_engine ?? '—' },
  ]

  if (user?.is_super_admin) {
    cards.unshift({ label: 'Organizations', value: data?.organizations ?? '—' })
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
      <p className="mt-1 text-[var(--text-muted)]">
        Welcome back, {user?.name}. Global engine from .env: <code className="text-[var(--accent)]">{data?.mdm_engine}</code>
      </p>
      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => (
          <div
            key={card.label}
            className="rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/70 p-5"
          >
            <div className="text-sm text-[var(--text-muted)]">{card.label}</div>
            <div className="mt-2 text-2xl font-semibold capitalize">{card.value}</div>
          </div>
        ))}
      </div>
      <div className="mt-8">
        <Link to="/devices" className="text-[var(--accent)] hover:text-[var(--accent-hover)]">
          View devices →
        </Link>
      </div>
    </div>
  )
}
