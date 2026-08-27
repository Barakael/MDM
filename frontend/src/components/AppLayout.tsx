import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const linkClass = ({ isActive }: { isActive: boolean }) =>
  `block rounded-md px-3 py-2 text-sm ${isActive ? 'bg-[var(--bg-muted)] text-white' : 'text-[var(--text-muted)] hover:text-white hover:bg-[var(--bg-muted)]/60'}`

export default function AppLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  return (
    <div className="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
      <aside className="border-b border-[var(--border)] bg-[var(--bg-elevated)]/80 p-4 backdrop-blur lg:border-b-0 lg:border-r">
        <div className="mb-8">
          <div className="text-xs uppercase tracking-[0.2em] text-[var(--text-muted)]">MDM Platform</div>
          <div className="mt-1 text-lg font-semibold">Console</div>
        </div>
        <nav className="flex flex-col gap-1">
          <NavLink to="/" end className={linkClass}>
            Dashboard
          </NavLink>
          <NavLink to="/devices" className={linkClass}>
            Devices
          </NavLink>
          <NavLink to="/enrollment" className={linkClass}>
            Enrollment
          </NavLink>
          <NavLink to="/commands" className={linkClass}>
            Commands
          </NavLink>
          <NavLink to="/audit-logs" className={linkClass}>
            Audit Logs
          </NavLink>
          {user?.is_super_admin && (
            <>
              <NavLink to="/organizations" className={linkClass}>
                Organizations
              </NavLink>
              <NavLink to="/users" className={linkClass}>
                Users
              </NavLink>
            </>
          )}
        </nav>
        <div className="mt-10 border-t border-[var(--border)] pt-4 text-sm">
          <div className="font-medium">{user?.name}</div>
          <div className="text-[var(--text-muted)]">{user?.roles?.join(', ')}</div>
          <button
            type="button"
            className="mt-3 text-[var(--accent)] hover:text-[var(--accent-hover)]"
            onClick={async () => {
              await logout()
              navigate('/login')
            }}
          >
            Sign out
          </button>
        </div>
      </aside>
      <main className="p-6 lg:p-10">
        <Outlet />
      </main>
    </div>
  )
}
