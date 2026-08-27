import { useEffect, useState, type FormEvent } from 'react'
import api from '../lib/api'

type Enrollment = {
  id: number
  token: string
  method: string
  status: string
  expires_at: string | null
  organization?: { name: string }
}

type Org = { id: number; name: string }

export default function EnrollmentPage() {
  const [items, setItems] = useState<Enrollment[]>([])
  const [orgs, setOrgs] = useState<Org[]>([])
  const [organizationId, setOrganizationId] = useState('')
  const [createdUrl, setCreatedUrl] = useState<string | null>(null)

  async function load() {
    const [enrollments, organizations] = await Promise.all([
      api.get('/enrollments'),
      api.get('/organizations'),
    ])
    setItems(enrollments.data.data.data ?? enrollments.data.data)
    setOrgs(organizations.data.data)
    if (!organizationId && organizations.data.data[0]) {
      setOrganizationId(String(organizations.data.data[0].id))
    }
  }

  useEffect(() => {
    void load()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function onCreate(e: FormEvent) {
    e.preventDefault()
    const { data } = await api.post('/enrollments', {
      organization_id: Number(organizationId),
      method: 'configurator',
    })
    setCreatedUrl(data.enrollment_url)
    await load()
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Enrollment</h1>
      <p className="mt-1 text-[var(--text-muted)]">
        Apple Configurator enrollment → URL → configuration → status. ABM comes later.
      </p>

      <form onSubmit={onCreate} className="mt-6 flex max-w-xl flex-wrap gap-2">
        <select
          className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm"
          value={organizationId}
          onChange={(e) => setOrganizationId(e.target.value)}
        >
          {orgs.map((o) => (
            <option key={o.id} value={o.id}>
              {o.name}
            </option>
          ))}
        </select>
        <button type="submit" className="rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-medium text-white">
          Create Configurator enrollment
        </button>
      </form>

      {createdUrl && (
        <p className="mt-4 break-all rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] p-3 text-sm">
          Enrollment URL: <span className="text-[var(--accent)]">{createdUrl}</span>
        </p>
      )}

      <ul className="mt-6 space-y-2">
        {items.map((item) => (
          <li key={item.id} className="rounded-lg border border-[var(--border)] px-4 py-3 text-sm">
            <div className="font-medium capitalize">
              {item.method} · {item.status}
            </div>
            <div className="text-[var(--text-muted)]">
              {item.organization?.name} · expires{' '}
              {item.expires_at ? new Date(item.expires_at).toLocaleString() : '—'}
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}
