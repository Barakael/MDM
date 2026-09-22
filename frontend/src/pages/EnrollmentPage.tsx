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

type MdmUrls = {
  public_url: string
  scep_url: string
  topic: string
  topic_configured: boolean
}

type ProfileEndpoints = {
  server_url: string
  scep_url: string
  topic: string
}

export default function EnrollmentPage() {
  const [items, setItems] = useState<Enrollment[]>([])
  const [orgs, setOrgs] = useState<Org[]>([])
  const [organizationId, setOrganizationId] = useState('')
  const [createdUrl, setCreatedUrl] = useState<string | null>(null)
  const [profileEndpoints, setProfileEndpoints] = useState<ProfileEndpoints | null>(null)
  const [mdmUrls, setMdmUrls] = useState<MdmUrls | null>(null)
  const [error, setError] = useState<string | null>(null)

  async function load() {
    const [enrollments, organizations, status] = await Promise.all([
      api.get('/enrollments'),
      api.get('/organizations'),
      api.get('/mdm/status').catch(() => null),
    ])
    setItems(enrollments.data.data.data ?? enrollments.data.data)
    setOrgs(organizations.data.data)
    if (status?.data?.data?.urls) {
      setMdmUrls(status.data.data.urls)
    }
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
    setError(null)
    try {
      const { data } = await api.post('/enrollments', {
        organization_id: Number(organizationId),
        method: 'configurator',
      })
      setCreatedUrl(data.enrollment_url)
      setProfileEndpoints(data.profile_endpoints ?? null)
      await load()
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      setError(ax.response?.data?.message || 'Failed to create enrollment. Check NANO_MDM_TOPIC / public URLs in .env.')
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Enrollment</h1>
      <p className="mt-1 text-[var(--text-muted)]">
        Creates a real NanoMDM + SCEP profile from .env. After deploy, flip public URLs and create new enrollments.
      </p>

      {mdmUrls && (
        <div className="mt-4 rounded-xl border border-[var(--border)] bg-[var(--bg-elevated)]/60 p-4 text-sm">
          <div className="font-medium">Configured endpoints (.env)</div>
          <dl className="mt-2 grid gap-1 text-[var(--text-muted)]">
            <div>
              <span className="text-white">ServerURL base:</span> {mdmUrls.public_url || '—'}
            </div>
            <div>
              <span className="text-white">SCEP:</span> {mdmUrls.scep_url || '—'}
            </div>
            <div>
              <span className="text-white">Topic:</span>{' '}
              {mdmUrls.topic_configured ? mdmUrls.topic : 'NANO_MDM_TOPIC not set'}
            </div>
          </dl>
        </div>
      )}

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

      {error && <p className="mt-4 text-sm text-[var(--danger)]">{error}</p>}

      {createdUrl && (
        <div className="mt-4 space-y-2 break-all rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] p-3 text-sm">
          <p>
            Enrollment URL: <span className="text-[var(--accent)]">{createdUrl}</span>
          </p>
          {profileEndpoints && (
            <>
              <p className="text-[var(--text-muted)]">Profile ServerURL: {profileEndpoints.server_url}</p>
              <p className="text-[var(--text-muted)]">Profile SCEP: {profileEndpoints.scep_url}</p>
              <p className="text-[var(--text-muted)]">Topic: {profileEndpoints.topic}</p>
            </>
          )}
        </div>
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
        {items.length === 0 && (
          <li className="rounded-lg border border-dashed border-[var(--border)] px-4 py-8 text-center text-sm text-[var(--text-muted)]">
            No enrollments yet.
          </li>
        )}
      </ul>
    </div>
  )
}
