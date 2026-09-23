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

type MdmStatus = {
  engine: string
  reachable: boolean
  last_webhook_at: string | null
  error: string | null
  urls: {
    public_url: string
    scep_url: string
    topic: string
    topic_configured: boolean
  }
}

export default function DashboardPage() {
  const { user } = useAuth()
  const [data, setData] = useState<Dash | null>(null)
  const [mdm, setMdm] = useState<MdmStatus | null>(null)

  useEffect(() => {
    api.get('/dashboard').then((res) => setData(res.data.data))
    api.get('/mdm/status').then((res) => setMdm(res.data.data)).catch(() => setMdm(null))
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
    <div className="min-w-0">
      <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
      <p className="mt-1 text-sm text-[var(--text-muted)] sm:text-base">
        Welcome back, {user?.name}. Global engine from .env:{' '}
        <code className="break-all text-[var(--accent)]">{data?.mdm_engine}</code>
      </p>

      {mdm && (
        <div
          className={`mt-4 min-w-0 rounded-xl border px-4 py-3 text-sm ${
            mdm.reachable
              ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'
              : 'border-[var(--danger)]/30 bg-[var(--danger)]/10 text-[var(--danger)]'
          }`}
        >
          <div className="font-medium">
            NanoMDM: {mdm.reachable ? 'Connected' : 'Unreachable'}
            {mdm.error ? ` — ${mdm.error}` : ''}
          </div>
          <div className="mt-1 break-all opacity-90">
            Public URL: {mdm.urls.public_url || '—'}
          </div>
          <div className="mt-1 break-all opacity-90">SCEP: {mdm.urls.scep_url || '—'}</div>
          <div className="mt-1 break-all opacity-90">
            Topic: {mdm.urls.topic_configured ? mdm.urls.topic : 'not set'}
          </div>
          <div className="mt-1 opacity-80">
            Last webhook:{' '}
            {mdm.last_webhook_at ? new Date(mdm.last_webhook_at).toLocaleString() : 'none yet'}
          </div>
        </div>
      )}

      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => (
          <div
            key={card.label}
            className="min-w-0 rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/70 p-5"
          >
            <div className="text-sm text-[var(--text-muted)]">{card.label}</div>
            <div className="mt-2 break-words text-2xl font-semibold capitalize">{card.value}</div>
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
