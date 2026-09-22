/** Human-readable labels for MDM command_type values. */
export function commandTypeLabel(type: string): string {
  switch (type) {
    case 'DeviceInformation':
      return 'Refresh inventory'
    case 'DeviceLock':
      return 'Lock screen'
    case 'EnableLostMode':
      return 'Lost Mode'
    case 'DisableLostMode':
      return 'Release Lost Mode'
    case 'EraseDevice':
      return 'Erase device'
    case 'InstallProfile':
      return 'Install profile'
    case 'RemoveProfile':
      return 'Remove profile'
    default:
      return type
  }
}
