import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { commandTypeLabel } from '../lib/commandLabels'

type Command = {
  id: number
  command_type: string
  status: string
  engine: string
  created_at: string
  sent_at?: string | null
  completed_at?: string | null
  device?: { id?: number; device_name: string | null; udid: string | null }
}

const inflight = new Set(['created', 'queued', 'sent', 'device_connected'])

function DeviceCell({ command }: { command: Command }) {
  const d = command.device
  if (d?.id) {
    return (
      <Link className="text-[var(--accent)] hover:underline" to={`/devices/${d.id}`}>
        {d.device_name || d.udid || 'Device'}
      </Link>
    )
  }
  return <>{d?.device_name || d?.udid || '—'}</>
}

export default function CommandsPage() {
  const [commands, setCommands] = useState<Command[]>([])

  async function load() {
    const res = await api.get('/commands')
    setCommands(res.data.data.data ?? res.data.data)
  }

  useEffect(() => {
    void load()
  }, [])

  useEffect(() => {
    const hasInflight = commands.some((c) => inflight.has(c.status))
    if (!hasInflight) return
    const timer = window.setInterval(() => {
      void load()
    }, 4000)
    return () => window.clearInterval(timer)
  }, [commands])

  return (
    <div className="min-w-0">
      <h1 className="text-2xl font-semibold tracking-tight">Commands</h1>
      <p className="mt-1 text-sm text-[var(--text-muted)] sm:text-base">
        MDM command lifecycle across the fleet. Run new actions from a device&apos;s Remote control panel.
      </p>

      {/* Mobile cards */}
      <div className="mt-6 space-y-3 md:hidden">
        {commands.map((c) => (
          <article
            key={c.id}
            className="rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/70 p-4 text-sm"
          >
            <div className="font-medium">
              <DeviceCell command={c} />
            </div>
            <dl className="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div>
                <dt className="text-[var(--text-muted)]">Type</dt>
                <dd className="mt-0.5">{commandTypeLabel(c.command_type)}</dd>
              </div>
              <div>
                <dt className="text-[var(--text-muted)]">Status</dt>
                <dd className="mt-0.5 capitalize">{c.status}</dd>
              </div>
              <div>
                <dt className="text-[var(--text-muted)]">Engine</dt>
                <dd className="mt-0.5">{c.engine}</dd>
              </div>
              <div>
                <dt className="text-[var(--text-muted)]">Created</dt>
                <dd className="mt-0.5 text-[var(--text-muted)]">{new Date(c.created_at).toLocaleString()}</dd>
              </div>
              <div>
                <dt className="text-[var(--text-muted)]">Sent</dt>
                <dd className="mt-0.5 text-[var(--text-muted)]">
                  {c.sent_at ? new Date(c.sent_at).toLocaleString() : '—'}
                </dd>
              </div>
              <div>
                <dt className="text-[var(--text-muted)]">Completed</dt>
                <dd className="mt-0.5 text-[var(--text-muted)]">
                  {c.completed_at ? new Date(c.completed_at).toLocaleString() : '—'}
                </dd>
              </div>
            </dl>
          </article>
        ))}
        {commands.length === 0 && (
          <p className="rounded-xl border border-[var(--border)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">
            No commands yet. Open a device and use Remote control.
          </p>
        )}
      </div>

      {/* Desktop table */}
      <div className="mt-6 hidden overflow-x-auto rounded-xl border border-[var(--border)] md:block">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-[var(--bg-elevated)] text-[var(--text-muted)]">
            <tr>
              <th className="px-4 py-3">Device</th>
              <th className="px-4 py-3">Type</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Engine</th>
              <th className="px-4 py-3">Created</th>
              <th className="px-4 py-3">Sent</th>
              <th className="px-4 py-3">Completed</th>
            </tr>
          </thead>
          <tbody>
            {commands.map((c) => (
              <tr key={c.id} className="border-t border-[var(--border)]">
                <td className="px-4 py-3">
                  <DeviceCell command={c} />
                </td>
                <td className="px-4 py-3">{commandTypeLabel(c.command_type)}</td>
                <td className="px-4 py-3 capitalize">{c.status}</td>
                <td className="px-4 py-3">{c.engine}</td>
                <td className="px-4 py-3">{new Date(c.created_at).toLocaleString()}</td>
                <td className="px-4 py-3">{c.sent_at ? new Date(c.sent_at).toLocaleString() : '—'}</td>
                <td className="px-4 py-3">{c.completed_at ? new Date(c.completed_at).toLocaleString() : '—'}</td>
              </tr>
            ))}
            {commands.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-[var(--text-muted)]">
                  No commands yet. Open a device and use Remote control.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
