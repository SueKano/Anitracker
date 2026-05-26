export function formatDuration(seconds: number): string {
  const days = Math.floor(seconds / 86400)
  if (days === 0) return 'menos de un día'

  return `${days} ${days === 1 ? 'día' : 'días'}`
}

const EPISODE_MINUTES = 24

export type WatchTimePart = { value: number; unit: string }

export function getWatchTimeParts(episodes: number): WatchTimePart[] {
  const totalMinutes = episodes * EPISODE_MINUTES
  const days = Math.floor(totalMinutes / 1440)
  const hours = Math.floor((totalMinutes % 1440) / 60)
  const minutes = totalMinutes % 60

  if (days > 0) {
    const parts: WatchTimePart[] = [{ value: days, unit: days === 1 ? 'día' : 'días' }]
    if (hours > 0) parts.push({ value: hours, unit: hours === 1 ? 'hora' : 'horas' })
    return parts
  }
  if (hours > 0) {
    const parts: WatchTimePart[] = [{ value: hours, unit: hours === 1 ? 'hora' : 'horas' }]
    if (minutes > 0) parts.push({ value: minutes, unit: minutes === 1 ? 'minuto' : 'minutos' })
    return parts
  }
  return [{ value: minutes, unit: minutes === 1 ? 'minuto' : 'minutos' }]
}

export function formatWatchTime(episodes: number): string {
  const totalMinutes = episodes * EPISODE_MINUTES
  const days = Math.floor(totalMinutes / 1440)
  const hours = Math.floor((totalMinutes % 1440) / 60)
  const minutes = totalMinutes % 60

  if (days > 0) {
    const dayLabel = days === 1 ? 'día' : 'días'
    if (hours === 0) return `${days} ${dayLabel}`
    return `${days} ${dayLabel} y ${hours} ${hours === 1 ? 'hora' : 'horas'}`
  }
  if (hours > 0) {
    const hourLabel = hours === 1 ? 'hora' : 'horas'
    if (minutes === 0) return `${hours} ${hourLabel}`
    return `${hours} ${hourLabel} y ${minutes} ${minutes === 1 ? 'minuto' : 'minutos'}`
  }
  return `${minutes} ${minutes === 1 ? 'minuto' : 'minutos'}`
}