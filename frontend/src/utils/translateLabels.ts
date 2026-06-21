import { i18n, t } from '../i18n'

function label(category: string, value: string | null | undefined): string {
  if (!value) return ''
  const key = `labels.${category}.${value}`
  return i18n.global.te(key, i18n.global.locale.value) ? t(key) : value
}

export const translateSeason = (value: string | null | undefined) =>
  label('season', value?.toUpperCase())
export const translateFormat = (value: string | null | undefined) =>
  label('format', value?.toUpperCase())
export const translateSource = (value: string | null | undefined) =>
  label('source', value?.toUpperCase())
export const translateDay = (value: string | null | undefined) =>
  label('day', value?.toUpperCase())
export const translateDayAbbr = (value: string | null | undefined) =>
  label('dayAbbr', value?.toUpperCase())

export const translateGenre = (value: string | null | undefined): string =>
  label('genres', value?.trim())

export const translateGenres = (value: string | null | undefined): string => {
  if (!value) return ''
  return value.split(',').map(genre => translateGenre(genre.trim())).join(', ')
}

export type AiringStatus = 'upcoming' | 'airing' | 'finished'
export function resolveAiringStatus(anime: { airingStatus: string; airing: boolean }): AiringStatus {
  if (anime.airingStatus === 'NOT_YET_RELEASED') return 'upcoming'
  return anime.airing ? 'airing' : 'finished'
}
