import { i18n } from '../i18n'
const plural = (key: string, count: number) => i18n.global.t(key, count)

export function formatDuration(seconds: number): string {
  const days = Math.floor(seconds / 86400)
  if (days === 0) return i18n.global.t('time.lessThanDay')

  return `${days} ${plural('time.day', days)}`
}

const EPISODE_MINUTES = 22
export type WatchTimePart = { value: number; unit: string }

export function getWatchTimeParts(episodes: number): WatchTimePart[] {
  const totalMinutes = episodes * EPISODE_MINUTES
  const days = Math.floor(totalMinutes / 1440)
  const hours = Math.floor((totalMinutes % 1440) / 60)
  const minutes = totalMinutes % 60

  if (days > 0) {
    const parts: WatchTimePart[] = [{ value: days, unit: plural('time.day', days) }]
    if (hours > 0) parts.push({ value: hours, unit: plural('time.hour', hours) })
    return parts
  }
  if (hours > 0) {
    const parts: WatchTimePart[] = [{ value: hours, unit: plural('time.hour', hours) }]
    if (minutes > 0) parts.push({ value: minutes, unit: plural('time.minute', minutes) })
    return parts
  }
  return [{ value: minutes, unit: plural('time.minute', minutes) }]
}