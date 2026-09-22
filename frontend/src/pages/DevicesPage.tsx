import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { commandTypeLabel } from '../lib/commandLabels'

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
  push?: { topic: string | null; has_token: boolean }
}

export default function DevicesPage() {
  const [devices, setDevices] = useState<Device[]>([])
  const [q, setQ] = useState('')
  const [busyId, setBusyId] = useState<number | null>(null)
  const [flash, setFlash] = useState<string | null>(null)

  async function load() {
    const params = q ? { q } : {}
    const res = await api.get('/devices', { params })
    setDevices(res.data.data.data ?? res.data.data)
  }

  useEffect(() => {
    void load()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q])

  async function quickLock(device: Device) {
    const canControl = device.enrollment_status === 'enrolled' && Boolean(device.push?.has_token)
    if (!canControl) {
      setFlash('Cannot lock: device must be enrolled with an APNs token. Open the device for details.')
      return
    }
    if (!window.confirm(`Lock screen on “${device.device_name || 'this device'}”?`)) {
      return
    }
    setBusyId(device.id)
    setFlash(null)
    try {
      const { data } = await api.post(`/devices/${device.id}/lock`, {})
      setFlash(
        `Queued ${commandTypeLabel(data.data.command_type)} — view status on the device Commands tab.`,
      )
      await load()
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string } } }
      setFlash(ax.response?.data?.message || 'Lock command failed to queue')
    } finally {
      setBusyId(null)
    }
  }

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Devices</h1>
          <p className="mt-1 text-[var(--text-muted)]">
            Open a device for full remote control (Lock, Lost Mode, Release, Erase), or use quick Lock here.
          </p>
        </div>
        <input
          placeholder="Search name, serial, UDID"
          className="w-full max-w-xs rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm outline-none focus:border-[var(--accent)]"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
      </div>

      {flash && (
        <p className="mt-4 rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] px-3 py-2 text-sm text-[var(--accent)]">
          {flash}
        </p>
      )}

      <div className="mt-6 overflow-x-auto rounded-xl border border-[var(--border)]">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-[var(--bg-elevated)] text-[var(--text-muted)]">
            <tr>
              <th className="px-4 py-3 font-medium">Device</th>
              <th className="px-4 py-3 font-medium">OS</th>
              <th className="px-4 py-3 font-medium">Enrollment</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Engine</th>
              <th className="px-4 py-3 font-medium">Last contact</th>
              <th className="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {devices.map((device) => {
              const canLock = device.enrollment_status === 'enrolled' && Boolean(device.push?.has_token)
              return (
                <tr key={device.id} className="border-t border-[var(--border)] hover:bg-[var(--bg-elevated)]/40">
                  <td className="px-4 py-3">
                    <Link to={`/devices/${device.id}`} className="font-medium text-[var(--accent)] hover:underline">
                      {device.device_name || 'Unnamed device'}
                    </Link>
                    <div className="text-xs text-[var(--text-muted)]">{device.serial_number}</div>
                  </td>
                  <td className="px-4 py-3">{device.os_version || '—'}</td>
                  <td className="px-4 py-3 capitalize">{device.enrollment_status || '—'}</td>
                  <td className="px-4 py-3 capitalize">
                    {device.is_online ? 'Online' : 'Offline'} · {device.management_status}
                    {device.management_status === 'lost_mode' && (
                      <span className="ml-2 rounded bg-amber-500/15 px-1.5 py-0.5 text-xs text-amber-200">
                        Lost Mode
                      </span>
                    )}
                    {!device.push?.has_token && (
                      <span className="ml-1 text-xs text-[var(--text-muted)]">(no APNs)</span>
                    )}
                  </td>
                  <td className="px-4 py-3">{device.mdm_engine || 'default'}</td>
                  <td className="px-4 py-3 text-[var(--text-muted)]">
                    {device.last_contact_at ? new Date(device.last_contact_at).toLocaleString() : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <Link
                        to={`/devices/${device.id}`}
                        className="rounded-md bg-[var(--bg-muted)] px-2.5 py-1 text-xs font-medium text-white hover:bg-[var(--border)]"
                      >
                        Open
                      </Link>
                      <button
                        type="button"
                        disabled={!canLock || busyId === device.id}
                        onClick={() => void quickLock(device)}
                        title={canLock ? 'Lock screen' : 'Requires enrolled device with APNs token'}
                        className="rounded-md bg-[var(--accent)]/15 px-2.5 py-1 text-xs font-medium text-[var(--accent)] hover:bg-[var(--accent)]/25 disabled:cursor-not-allowed disabled:opacity-40"
                      >
                        {busyId === device.id ? '…' : 'Lock'}
                      </button>
                    </div>
                  </td>
                </tr>
              )
            })}
            {devices.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-[var(--text-muted)]">
                  No devices found. Enroll a device to see it appear automatically.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
