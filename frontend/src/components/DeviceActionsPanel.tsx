import { useState, type FormEvent } from 'react'

export type DeviceActionsDevice = {
  enrollment_status: string
  management_status: string
  push?: { has_token: boolean } | null
}

type Props = {
  device: DeviceActionsDevice
  busy: string | null
  message: string | null
  onAction: (action: string, body?: Record<string, unknown>) => Promise<void>
}

type Panel = 'none' | 'lock' | 'lost' | 'erase'

export default function DeviceActionsPanel({ device, busy, message, onAction }: Props) {
  const [panel, setPanel] = useState<Panel>('none')
  const [lockPin, setLockPin] = useState('')
  const [lockMessage, setLockMessage] = useState('')
  const [lockPhone, setLockPhone] = useState('')
  const [lostMessage, setLostMessage] = useState('This device has been reported lost. Please call the number below.')
  const [lostPhone, setLostPhone] = useState('')
  const [lostFootnote, setLostFootnote] = useState('')
  const [eraseConfirm, setEraseConfirm] = useState('')

  const enrolled = device.enrollment_status === 'enrolled'
  const hasToken = Boolean(device.push?.has_token)
  const canControl = enrolled && hasToken
  const blockedReason = !enrolled
    ? 'Device is not enrolled — remote commands are unavailable.'
    : !hasToken
      ? 'No APNs token — device has not completed MDM check-in yet.'
      : null

  function openPanel(next: Panel) {
    if (!canControl || busy) return
    setPanel((current) => (current === next ? 'none' : next))
  }

  async function submitLock(e: FormEvent) {
    e.preventDefault()
    const body: Record<string, unknown> = {}
    if (lockPin.trim()) body.pin = lockPin.trim()
    if (lockMessage.trim()) body.message = lockMessage.trim()
    if (lockPhone.trim()) body.phone_number = lockPhone.trim()
    await onAction('lock', body)
    setPanel('none')
  }

  async function submitLost(e: FormEvent) {
    e.preventDefault()
    if (!window.confirm('Enable Lost Mode on this device? The user cannot dismiss it until you Release.')) {
      return
    }
    await onAction('lost-mode', {
      message: lostMessage.trim() || 'This device has been reported lost.',
      phone_number: lostPhone.trim(),
      footnote: lostFootnote.trim() || undefined,
    })
    setPanel('none')
  }

  async function submitRelease() {
    if (!canControl || busy) return
    if (!window.confirm('Release Lost Mode? The device will exit the lock screen overlay.')) {
      return
    }
    await onAction('release')
  }

  async function submitErase(e: FormEvent) {
    e.preventDefault()
    if (eraseConfirm !== 'ERASE') return
    await onAction('erase', { confirm: true })
    setEraseConfirm('')
    setPanel('none')
  }

  return (
    <section className="mt-6 rounded-xl border border-[var(--border)] bg-[var(--bg)]/40 p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold tracking-tight">Remote control</h2>
          <p className="mt-1 text-sm text-[var(--text-muted)]">
            Lock locks the screen (user unlocks with their passcode). Lost Mode holds the device until you Release.
            Erase wipes the device.
          </p>
        </div>
        {device.management_status === 'lost_mode' && (
          <span className="rounded-md bg-amber-500/15 px-2.5 py-1 text-xs font-medium text-amber-200">
            Lost Mode active
          </span>
        )}
      </div>

      {blockedReason && (
        <p className="mt-3 rounded-lg border border-[var(--danger)]/25 bg-[var(--danger)]/10 px-3 py-2 text-sm text-[var(--danger)]">
          {blockedReason}
        </p>
      )}

      <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <ActionCard
          title="Refresh inventory"
          description="Pull DeviceInformation (name, OS, model)."
          disabled={!canControl || !!busy}
          busy={busy === 'refresh'}
          onClick={() => void onAction('refresh')}
        />
        <ActionCard
          title="Lock screen"
          description="DeviceLock — user unlocks with passcode."
          disabled={!canControl || !!busy}
          busy={busy === 'lock'}
          active={panel === 'lock'}
          onClick={() => openPanel('lock')}
        />
        <ActionCard
          title="Enable Lost Mode"
          description="Remote lock until an admin Releases."
          disabled={!canControl || !!busy}
          busy={busy === 'lost-mode'}
          active={panel === 'lost'}
          onClick={() => openPanel('lost')}
        />
        <ActionCard
          title="Release Lost Mode"
          description="Disable Lost Mode on the device."
          disabled={!canControl || !!busy}
          busy={busy === 'release'}
          onClick={() => void submitRelease()}
        />
        <ActionCard
          title="Erase device"
          description="Wipe all data. Cannot be undone."
          danger
          disabled={!canControl || !!busy}
          busy={busy === 'erase'}
          active={panel === 'erase'}
          onClick={() => openPanel('erase')}
        />
      </div>

      {panel === 'lock' && (
        <form onSubmit={(e) => void submitLock(e)} className="mt-4 space-y-3 rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] p-4">
          <h3 className="text-sm font-medium">Lock screen options</h3>
          <Field label="PIN (optional)" value={lockPin} onChange={setLockPin} placeholder="6-digit PIN if required" />
          <Field label="Lock message (optional)" value={lockMessage} onChange={setLockMessage} />
          <Field label="Phone number (optional)" value={lockPhone} onChange={setLockPhone} placeholder="+255…" />
          <FormActions
            busy={busy === 'lock'}
            submitLabel="Send Lock"
            onCancel={() => setPanel('none')}
          />
        </form>
      )}

      {panel === 'lost' && (
        <form onSubmit={(e) => void submitLost(e)} className="mt-4 space-y-3 rounded-lg border border-[var(--border)] bg-[var(--bg-elevated)] p-4">
          <h3 className="text-sm font-medium">Lost Mode message</h3>
          <Field label="Message" value={lostMessage} onChange={setLostMessage} required />
          <Field label="Phone number" value={lostPhone} onChange={setLostPhone} placeholder="+255…" />
          <Field label="Footnote (optional)" value={lostFootnote} onChange={setLostFootnote} />
          <FormActions
            busy={busy === 'lost-mode'}
            submitLabel="Enable Lost Mode"
            onCancel={() => setPanel('none')}
          />
        </form>
      )}

      {panel === 'erase' && (
        <form onSubmit={(e) => void submitErase(e)} className="mt-4 space-y-3 rounded-lg border border-[var(--danger)]/30 bg-[var(--danger)]/5 p-4">
          <h3 className="text-sm font-medium text-[var(--danger)]">Erase device</h3>
          <p className="text-sm text-[var(--text-muted)]">
            This permanently wipes the device. Type <span className="font-mono text-white">ERASE</span> to confirm.
          </p>
          <Field label="Confirmation" value={eraseConfirm} onChange={setEraseConfirm} placeholder="ERASE" />
          <FormActions
            busy={busy === 'erase'}
            submitLabel="Erase now"
            danger
            disabled={eraseConfirm !== 'ERASE'}
            onCancel={() => {
              setEraseConfirm('')
              setPanel('none')
            }}
          />
        </form>
      )}

      {message && <p className="mt-4 text-sm text-[var(--accent)]">{message}</p>}
    </section>
  )
}

function ActionCard({
  title,
  description,
  onClick,
  disabled,
  busy,
  danger,
  active,
}: {
  title: string
  description: string
  onClick: () => void
  disabled?: boolean
  busy?: boolean
  danger?: boolean
  active?: boolean
}) {
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      className={`rounded-xl border p-4 text-left transition disabled:cursor-not-allowed disabled:opacity-45 ${
        active
          ? 'border-[var(--accent)] bg-[var(--accent)]/10'
          : danger
            ? 'border-[var(--danger)]/35 bg-[var(--danger)]/5 hover:bg-[var(--danger)]/10'
            : 'border-[var(--border)] bg-[var(--bg-elevated)]/80 hover:border-[var(--accent)]/40'
      }`}
    >
      <div className={`text-sm font-semibold ${danger ? 'text-[var(--danger)]' : 'text-white'}`}>
        {busy ? 'Sending…' : title}
      </div>
      <p className="mt-1 text-xs text-[var(--text-muted)]">{description}</p>
    </button>
  )
}

function Field({
  label,
  value,
  onChange,
  placeholder,
  required,
}: {
  label: string
  value: string
  onChange: (v: string) => void
  placeholder?: string
  required?: boolean
}) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block text-[var(--text-muted)]">{label}</span>
      <input
        className="w-full rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 py-2 text-sm outline-none focus:border-[var(--accent)]"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        required={required}
      />
    </label>
  )
}

function FormActions({
  onCancel,
  submitLabel,
  busy,
  danger,
  disabled,
}: {
  onCancel: () => void
  submitLabel: string
  busy?: boolean
  danger?: boolean
  disabled?: boolean
}) {
  return (
    <div className="flex flex-wrap gap-2 pt-1">
      <button
        type="submit"
        disabled={busy || disabled}
        className={`rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-50 ${
          danger
            ? 'bg-[var(--danger)] text-white hover:opacity-90'
            : 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]'
        }`}
      >
        {busy ? 'Sending…' : submitLabel}
      </button>
      <button
        type="button"
        onClick={onCancel}
        className="rounded-lg bg-[var(--bg-muted)] px-4 py-2 text-sm text-white hover:bg-[var(--border)]"
      >
        Cancel
      </button>
    </div>
  )
}
