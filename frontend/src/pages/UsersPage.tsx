import { useEffect, useState, type FormEvent } from 'react'
import api from '../lib/api'
import { useAuth } from '../context/AuthContext'

type UserRow = {
  id: number
  name: string
  email: string
  organization?: { name: string } | null
  roles: { name: string }[]
}

type Org = { id: number; name: string }

export default function UsersPage() {
  const { user } = useAuth()
  const [users, setUsers] = useState<UserRow[]>([])
  const [orgs, setOrgs] = useState<Org[]>([])
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    organization_id: '',
    role: 'Admin',
  })

  async function load() {
    const [u, o] = await Promise.all([api.get('/users'), api.get('/organizations')])
    setUsers(u.data.data)
    setOrgs(o.data.data)
  }

  useEffect(() => {
    void load()
  }, [])

  async function onCreate(e: FormEvent) {
    e.preventDefault()
    await api.post('/users', {
      ...form,
      organization_id: form.organization_id ? Number(form.organization_id) : null,
    })
    setForm({ name: '', email: '', password: '', organization_id: '', role: 'Admin' })
    await load()
  }

  if (!user?.is_super_admin) {
    return <p className="text-[var(--text-muted)]">Super Admin only.</p>
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold tracking-tight">Users</h1>
      <form onSubmit={onCreate} className="mt-6 grid max-w-2xl gap-2 sm:grid-cols-2">
        <input
          className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm"
          placeholder="Name"
          value={form.name}
          onChange={(e) => setForm({ ...form, name: e.target.value })}
          required
        />
        <input
          className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm"
          placeholder="Email"
          type="email"
          value={form.email}
          onChange={(e) => setForm({ ...form, email: e.target.value })}
          required
        />
        <input
          className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm"
          placeholder="Password"
          type="password"
          value={form.password}
          onChange={(e) => setForm({ ...form, password: e.target.value })}
          required
        />
        <select
          className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm"
          value={form.role}
          onChange={(e) => setForm({ ...form, role: e.target.value })}
        >
          <option value="Admin">Admin</option>
          <option value="Super_Admin">Super_Admin</option>
        </select>
        <select
          className="rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm sm:col-span-2"
          value={form.organization_id}
          onChange={(e) => setForm({ ...form, organization_id: e.target.value })}
        >
          <option value="">No organization</option>
          {orgs.map((o) => (
            <option key={o.id} value={o.id}>
              {o.name}
            </option>
          ))}
        </select>
        <button type="submit" className="rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-medium text-white sm:col-span-2">
          Create user
        </button>
      </form>
      <ul className="mt-6 space-y-2">
        {users.map((u) => (
          <li key={u.id} className="rounded-lg border border-[var(--border)] px-4 py-3 text-sm">
            <div className="font-medium">
              {u.name} · {u.email}
            </div>
            <div className="text-[var(--text-muted)]">
              {u.roles.map((r) => r.name).join(', ')} · {u.organization?.name || 'Global'}
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}
