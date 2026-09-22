import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import DeviceActionsPanel from '../components/DeviceActionsPanel'
import api from '../lib/api'
import { commandTypeLabel } from '../lib/commandLabels'

type Tab =
  | 'information'
  | 'commands'
  | 'applications'
  | 'profiles'
  | 'certificates'
  | 'restrictions'
  | 'events'
  | 'audit'

type PushSummary = {
  topic: string | null
  has_token: boolean
  has_push_magic: boolean
  updated_at: string | null
}

type Device = {
  id: number
  device_name: string | null
  model: string | null
  os_version: string | null
  serial_number: string | null
  udid: string | null
  supervised: boolean
  is_online: boolean
  management_status: string
  enrollment_status: string
  mdm_engine: string | null
  last_contact_at: string | null
  push?: PushSummary
  commands?: Command[]
  applications?: { id: number; name: string; bundle_identifier: string; version: string }[]
  profiles?: { id: number; display_name: string; profile_identifier: string }[]
  certificates?: { id: number; common_name: string; expires_at: string }[]
  events?: { id: number; event_type: string; created_at: string }[]
}

type Command = {
  id: number
  command_type: string
  status: string
  engine: string
  error: string | null
  created_at: string
  sent_at: string | null
  completed_at: string | null
}

const tabs: { id: Tab; label: string }[] = [
  { id: 'information', label: 'Device Information' },
  { id: 'commands', label: 'Commands' },
  { id: 'applications', label: 'Applications' },
  { id: 'profiles', label: 'Profiles' },
  { id: 'certificates', label: 'Certificates' },
  { id: 'restrictions', label: 'Restrictions' },
  { id: 'events', label: 'Events' },
  { id: 'audit', label: 'Audit Logs' },
]

const inflight = new Set(['created', 'queued', 'sent', 'device_connected'])

export default function DeviceDetailPage() {
  const { id } = useParams()
  const [device, setDevice] = useState<Device | null>(null)
  const [engine, setEngine] = useState('nano')
  const [tab, setTab] = useState<Tab>('information')
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState<string | null>(null)
  const [highlightCommandId, setHighlightCommandId] = useState<number | null>(null)

  const load = useCallback(async () => {
    const { data } = await api.get(`/devices/${id}`)
    setDevice(data.data)
    setEngine(data.resolved_engine)
  }, [id])

  useEffect(() => {
    void load()
  }, [load])

  useEffect(() => {
    const hasInflight = (device?.commands || []).some((c) => inflight.has(c.status))
    if (!hasInflight) return
    const timer = window.setInterval(() => {
      void load()
    }, 4000)
    return () => window.clearInterval(timer)
  }, [device?.commands, load])

  async function runAction(action: string, body: Record<string, unknown> = {}) {
    setBusy(action)
    setMessage(null)
    try {
      const { data } = await api.post(`/devices/${id}/${action}`, body)
      const command = data.data
      setMessage(`Queued: ${commandTypeLabel(command.command_type)} (${command.status})`)
      setHighlightCommandId(command.id)
      await load()
      setTab('commands')
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string; error?: string } } }
      setMessage(ax.response?.data?.message || 'Command failed to queue')
    } finally {
      setBusy(null)
    }
  }

  if (!device) {
    return <div className="text-[var(--text-muted)]">Loading device…</div>
  }

  return (
    <div>
      <Link to="/devices" className="text-sm text-[var(--text-muted)] hover:text-white">
        ← Devices
      </Link>

      <section className="mt-4 rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/80 p-6">
        <h1 className="text-3xl font-semibold tracking-tight">{device.device_name || 'Device'}</h1>
        <dl className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 text-sm">
          <div>
            <dt className="text-[var(--text-muted)]">Status</dt>
            <dd className="mt-1 font-medium">{device.is_online ? 'Online' : 'Offline'}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">Enrollment</dt>
            <dd className="mt-1 font-medium capitalize">{device.enrollment_status}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">Management</dt>
            <dd className="mt-1 font-medium capitalize">{device.management_status}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">Supervised</dt>
            <dd className="mt-1 font-medium">{device.supervised ? 'Yes' : 'No'}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">iOS</dt>
            <dd className="mt-1 font-medium">{device.os_version || '—'}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">Engine</dt>
            <dd className="mt-1 font-medium capitalize">{engine === 'nano' ? 'NanoMDM' : engine}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">Last Contact</dt>
            <dd className="mt-1 font-medium">
              {device.last_contact_at ? new Date(device.last_contact_at).toLocaleString() : '—'}
            </dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">UDID</dt>
            <dd className="mt-1 font-mono text-xs">{device.udid || '—'}</dd>
          </div>
          <div>
            <dt className="text-[var(--text-muted)]">APNs</dt>
            <dd className="mt-1 font-medium">
              {device.push?.has_token ? 'Token present' : 'No token'}
              {device.push?.topic ? (
                <div className="mt-1 font-mono text-xs text-[var(--text-muted)] break-all">{device.push.topic}</div>
              ) : null}
            </dd>
          </div>
        </dl>

        <DeviceActionsPanel device={device} busy={busy} message={message} onAction={runAction} />
      </section>

      <div className="mt-6 flex flex-wrap gap-2 border-b border-[var(--border)] pb-2">
        {tabs.map((t) => (
          <button
            key={t.id}
            type="button"
            onClick={() => setTab(t.id)}
            className={`rounded-md px-3 py-1.5 text-sm ${
              tab === t.id ? 'bg-[var(--bg-muted)] text-white' : 'text-[var(--text-muted)] hover:text-white'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      <div className="mt-4 rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/50 p-4">
        {tab === 'information' && (
          <InfoGrid
            rows={[
              ['Model', device.model],
              ['Serial', device.serial_number],
              ['Management', device.management_status],
              ['Enrollment', device.enrollment_status],
              ['Override engine', device.mdm_engine || 'inherit (.env)'],
              ['Push topic', device.push?.topic || '—'],
              ['Push magic', device.push?.has_push_magic ? 'Present' : 'Missing'],
            ]}
          />
        )}
        {tab === 'commands' && (
          <CommandsTable commands={device.commands || []} highlightId={highlightCommandId} />
        )}
        {tab === 'applications' && (
          <SimpleList
            empty="No applications reported."
            items={(device.applications || []).map((a) => `${a.name} (${a.bundle_identifier}) ${a.version || ''}`)}
          />
        )}
        {tab === 'profiles' && (
          <SimpleList
            empty="No profiles installed."
            items={(device.profiles || []).map((p) => p.display_name || p.profile_identifier)}
          />
        )}
        {tab === 'certificates' && (
          <SimpleList
            empty="No certificates."
            items={(device.certificates || []).map(
              (c) => `${c.common_name || 'Certificate'} · expires ${c.expires_at || '—'}`,
            )}
          />
        )}
        {tab === 'restrictions' && (
          <p className="text-sm text-[var(--text-muted)]">Restrictions will appear after inventory sync.</p>
        )}
        {tab === 'events' && (
          <SimpleList
            empty="No events."
            items={(device.events || []).map((e) => `${e.event_type} · ${new Date(e.created_at).toLocaleString()}`)}
          />
        )}
        {tab === 'audit' && (
          <p className="text-sm text-[var(--text-muted)]">
            See organization-wide entries in{' '}
            <Link className="text-[var(--accent)]" to="/audit-logs">
              Audit Logs
            </Link>
            .
          </p>
        )}
      </div>
    </div>
  )
}

function InfoGrid({ rows }: { rows: [string, string | null | undefined][] }) {
  return (
    <dl className="grid gap-3 sm:grid-cols-2 text-sm">
      {rows.map(([k, v]) => (
        <div key={k}>
          <dt className="text-[var(--text-muted)]">{k}</dt>
          <dd className="mt-1 break-all">{v || '—'}</dd>
        </div>
      ))}
    </dl>
  )
}

function CommandsTable({ commands, highlightId }: { commands: Command[]; highlightId: number | null }) {
  if (!commands.length) return <p className="text-sm text-[var(--text-muted)]">No commands yet.</p>
  return (
    <div className="overflow-x-auto">
      <table className="min-w-full text-left text-sm">
        <thead className="text-[var(--text-muted)]">
          <tr>
            <th className="py-2 pr-4">Type</th>
            <th className="py-2 pr-4">Status</th>
            <th className="py-2 pr-4">Engine</th>
            <th className="py-2 pr-4">Created</th>
            <th className="py-2 pr-4">Sent</th>
            <th className="py-2 pr-4">Completed</th>
            <th className="py-2">Error</th>
          </tr>
        </thead>
        <tbody>
          {commands.map((c) => (
            <tr
              key={c.id}
              className={`border-t border-[var(--border)] ${
                highlightId === c.id ? 'bg-[var(--accent)]/10' : ''
              }`}
            >
              <td className="py-2 pr-4">{commandTypeLabel(c.command_type)}</td>
              <td className="py-2 pr-4 capitalize">{c.status}</td>
              <td className="py-2 pr-4">{c.engine}</td>
              <td className="py-2 pr-4">{new Date(c.created_at).toLocaleString()}</td>
              <td className="py-2 pr-4">{c.sent_at ? new Date(c.sent_at).toLocaleString() : '—'}</td>
              <td className="py-2 pr-4">{c.completed_at ? new Date(c.completed_at).toLocaleString() : '—'}</td>
              <td className="py-2 text-[var(--danger)]">{c.error || '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

function SimpleList({ items, empty }: { items: string[]; empty: string }) {
  if (!items.length) return <p className="text-sm text-[var(--text-muted)]">{empty}</p>
  return (
    <ul className="space-y-2 text-sm">
      {items.map((item) => (
        <li key={item} className="border-b border-[var(--border)] pb-2 last:border-0">
          {item}
        </li>
      ))}
    </ul>
  )
}
