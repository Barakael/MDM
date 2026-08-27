import { useEffect, useState } from 'react'
import api from '../lib/api'

type Command = {
  id: number
  command_type: string
  status: string
  engine: string
  created_at: string
  device?: { device_name: string | null; udid: string | null }
}

export default function CommandsPage() {
  const [commands, setCommands] = useState<Command[]>([])

  useEffect(() => {
    api.get('/commands').then((res) => setCommands(res.data.data.data ?? res.data.data))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Commands</h1>
      <p className="mt-1 text-[var(--text-muted)]">MDM command lifecycle across the fleet.</p>
      <div className="mt-6 overflow-x-auto rounded-xl border border-[var(--border)]">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-[var(--bg-elevated)] text-[var(--text-muted)]">
            <tr>
              <th className="px-4 py-3">Device</th>
              <th className="px-4 py-3">Type</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Engine</th>
              <th className="px-4 py-3">Created</th>
            </tr>
          </thead>
          <tbody>
            {commands.map((c) => (
              <tr key={c.id} className="border-t border-[var(--border)]">
                <td className="px-4 py-3">{c.device?.device_name || c.device?.udid || '—'}</td>
                <td className="px-4 py-3">{c.command_type}</td>
                <td className="px-4 py-3 capitalize">{c.status}</td>
                <td className="px-4 py-3">{c.engine}</td>
                <td className="px-4 py-3">{new Date(c.created_at).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
