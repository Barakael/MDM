import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'

type Device = {
  id: number
  device_name: string | null
  model: string | null
  os_version: string | null
  serial_number: string | null
  supervised: boolean
  is_online: boolean
  management_status: string
  enrollment_status: string
  mdm_engine: string | null
  last_contact_at: string | null
  organization?: { name: string }
}

export default function DevicesPage() {
  const [devices, setDevices] = useState<Device[]>([])
  const [q, setQ] = useState('')

  useEffect(() => {
    const params = q ? { q } : {}
    api.get('/devices', { params }).then((res) => setDevices(res.data.data.data ?? res.data.data))
  }, [q])

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Devices</h1>
          <p className="mt-1 text-[var(--text-muted)]">Fleet inventory and management status.</p>
        </div>
        <input
          placeholder="Search name, serial, UDID"
          className="w-full max-w-xs rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm outline-none focus:border-[var(--accent)]"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
      </div>
      <div className="mt-6 overflow-x-auto rounded-xl border border-[var(--border)]">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-[var(--bg-elevated)] text-[var(--text-muted)]">
            <tr>
              <th className="px-4 py-3 font-medium">Device</th>
              <th className="px-4 py-3 font-medium">OS</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Engine</th>
              <th className="px-4 py-3 font-medium">Last contact</th>
            </tr>
          </thead>
          <tbody>
            {devices.map((device) => (
              <tr key={device.id} className="border-t border-[var(--border)] hover:bg-[var(--bg-elevated)]/40">
                <td className="px-4 py-3">
                  <Link to={`/devices/${device.id}`} className="font-medium text-[var(--accent)] hover:underline">
                    {device.device_name || 'Unnamed device'}
                  </Link>
                  <div className="text-xs text-[var(--text-muted)]">{device.serial_number}</div>
                </td>
                <td className="px-4 py-3">{device.os_version || '—'}</td>
                <td className="px-4 py-3 capitalize">
                  {device.is_online ? 'Online' : 'Offline'} · {device.management_status}
                </td>
                <td className="px-4 py-3">{device.mdm_engine || 'default'}</td>
                <td className="px-4 py-3 text-[var(--text-muted)]">
                  {device.last_contact_at ? new Date(device.last_contact_at).toLocaleString() : '—'}
                </td>
              </tr>
            ))}
            {devices.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-[var(--text-muted)]">
                  No devices found.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
