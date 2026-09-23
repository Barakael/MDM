import { useEffect, useState } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const linkClass = ({ isActive }: { isActive: boolean }) =>
  `block rounded-md px-3 py-2 text-sm ${isActive ? 'bg-[var(--bg-muted)] text-white' : 'text-[var(--text-muted)] hover:text-white hover:bg-[var(--bg-muted)]/60'}`

function SidebarNav({ onNavigate }: { onNavigate?: () => void }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  return (
    <div className="flex h-full flex-col overflow-hidden">
      <div className="mb-8 shrink-0">
        <div className="text-xs uppercase tracking-[0.2em] text-[var(--text-muted)]">MDM Platform</div>
        <div className="mt-1 text-lg font-semibold">Console</div>
      </div>
      <nav className="flex shrink-0 flex-col gap-1">
        <NavLink to="/" end className={linkClass} onClick={onNavigate}>
          Dashboard
        </NavLink>
        <NavLink to="/devices" className={linkClass} onClick={onNavigate}>
          Devices
        </NavLink>
        <NavLink to="/enrollment" className={linkClass} onClick={onNavigate}>
          Enrollment
        </NavLink>
        <NavLink to="/commands" className={linkClass} onClick={onNavigate}>
          Commands
        </NavLink>
        <NavLink to="/audit-logs" className={linkClass} onClick={onNavigate}>
          Audit Logs
        </NavLink>
        {user?.is_super_admin && (
          <>
            <NavLink to="/organizations" className={linkClass} onClick={onNavigate}>
              Organizations
            </NavLink>
            <NavLink to="/users" className={linkClass} onClick={onNavigate}>
              Users
            </NavLink>
          </>
        )}
      </nav>
      <div className="mt-auto shrink-0 border-t border-[var(--border)] pt-4 text-sm">
        <div className="truncate font-medium">{user?.name}</div>
        <div className="truncate text-[var(--text-muted)]">{user?.roles?.join(', ')}</div>
        <button
          type="button"
          className="mt-3 text-[var(--accent)] hover:text-[var(--accent-hover)]"
          onClick={async () => {
            onNavigate?.()
            await logout()
            navigate('/login')
          }}
        >
          Sign out
        </button>
      </div>
    </div>
  )
}

export default function AppLayout() {
  const [menuOpen, setMenuOpen] = useState(false)
  const location = useLocation()

  useEffect(() => {
    setMenuOpen(false)
  }, [location.pathname])

  useEffect(() => {
    if (!menuOpen) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setMenuOpen(false)
    }
    document.addEventListener('keydown', onKey)
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = prev
    }
  }, [menuOpen])

  return (
    <div className="flex h-dvh flex-col overflow-hidden">
      <header className="flex shrink-0 items-center justify-between gap-3 border-b border-[var(--border)] bg-[var(--bg-elevated)]/90 px-4 py-3 backdrop-blur lg:hidden">
        <div className="min-w-0">
          <div className="text-[10px] uppercase tracking-[0.2em] text-[var(--text-muted)]">MDM Platform</div>
          <div className="truncate text-base font-semibold">Console</div>
        </div>
        <button
          type="button"
          aria-label={menuOpen ? 'Close menu' : 'Open menu'}
          aria-expanded={menuOpen}
          className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[var(--border)] bg-[var(--bg-muted)]/60 text-white"
          onClick={() => setMenuOpen((o) => !o)}
        >
          {menuOpen ? (
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          ) : (
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
              <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          )}
        </button>
      </header>

      {menuOpen && (
        <button
          type="button"
          aria-label="Close menu"
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setMenuOpen(false)}
        />
      )}
      <aside
        className={`fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col border-r border-[var(--border)] bg-[var(--bg-elevated)] p-4 shadow-xl transition-transform duration-200 lg:hidden ${
          menuOpen ? 'translate-x-0' : 'pointer-events-none -translate-x-full'
        }`}
        aria-hidden={!menuOpen}
      >
        <SidebarNav onNavigate={() => setMenuOpen(false)} />
      </aside>

      <div className="flex min-h-0 min-w-0 flex-1 lg:grid lg:grid-cols-[240px_1fr]">
        <aside className="hidden h-full overflow-hidden border-r border-[var(--border)] bg-[var(--bg-elevated)]/80 p-4 backdrop-blur lg:block">
          <SidebarNav />
        </aside>
        <main className="min-h-0 min-w-0 overflow-y-auto p-4 sm:p-6 lg:p-10">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
