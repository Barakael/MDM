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
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Commands</h1>
      <p className="mt-1 text-[var(--text-muted)]">
        MDM command lifecycle across the fleet. Run new actions from a device&apos;s Remote control panel.
      </p>
      <div className="mt-6 overflow-x-auto rounded-xl border border-[var(--border)]">
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
                  {c.device?.id ? (
                    <Link className="text-[var(--accent)] hover:underline" to={`/devices/${c.device.id}`}>
                      {c.device.device_name || c.device.udid || 'Device'}
                    </Link>
                  ) : (
                    c.device?.device_name || c.device?.udid || '—'
                  )}
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
