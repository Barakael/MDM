import { useEffect, useState } from 'react'
import api from '../lib/api'

type Log = {
  id: number
  action: string
  created_at: string
  user?: { name: string; email: string } | null
  metadata?: Record<string, unknown> | null
}

export default function AuditLogsPage() {
  const [logs, setLogs] = useState<Log[]>([])

  useEffect(() => {
    api.get('/audit-logs').then((res) => setLogs(res.data.data.data ?? res.data.data))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Audit Logs</h1>
      <ul className="mt-6 space-y-2">
        {logs.map((log) => (
          <li key={log.id} className="rounded-lg border border-[var(--border)] px-4 py-3 text-sm">
            <div className="font-medium">{log.action}</div>
            <div className="text-[var(--text-muted)]">
              {log.user?.name || 'System'} · {new Date(log.created_at).toLocaleString()}
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}
