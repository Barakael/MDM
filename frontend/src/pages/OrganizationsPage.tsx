import { useEffect, useState, type FormEvent } from 'react'
import api from '../lib/api'
import { useAuth } from '../context/AuthContext'

type Org = { id: number; name: string; slug: string; is_active: boolean; devices_count?: number }

export default function OrganizationsPage() {
  const { user } = useAuth()
  const [orgs, setOrgs] = useState<Org[]>([])
  const [name, setName] = useState('')

  async function load() {
    const { data } = await api.get('/organizations')
    setOrgs(data.data)
  }

  useEffect(() => {
    void load()
  }, [])

  async function onCreate(e: FormEvent) {
    e.preventDefault()
    await api.post('/organizations', { name })
    setName('')
    await load()
  }

  if (!user?.is_super_admin) {
    return <p className="text-[var(--text-muted)]">Super Admin only.</p>
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Organizations</h1>
      <form onSubmit={onCreate} className="mt-6 flex max-w-lg gap-2">
        <input
          className="flex-1 rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm"
          placeholder="Organization name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
        />
        <button type="submit" className="rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-medium text-white">
          Create
        </button>
      </form>
      <ul className="mt-6 space-y-2">
        {orgs.map((org) => (
          <li key={org.id} className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)]/60 px-4 py-3">
            <div className="font-medium">{org.name}</div>
            <div className="text-sm text-[var(--text-muted)]">
              {org.slug} · {org.devices_count ?? 0} devices · {org.is_active ? 'active' : 'inactive'}
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}
