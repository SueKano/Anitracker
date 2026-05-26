import type { Anime } from '../types/Anime'

export interface AnimeApiPayload {
  anilistId?: number
  id?: number
  romajiName?: string
  title?: string
  titleJp?: string
  cover?: string
  portraitUrl?: string
  totalEpisodes?: number
  total?: number
  currentAiringEpisode?: number
  aired?: number
  airingStatus?: string
  airingDay?: string | null
  genres?: string[]
  source?: string
  seasonYear?: number
  season?: string
  format?: string
}

export function mapAnimeFromApi(payload: AnimeApiPayload): Anime {
  return {
    id: payload.anilistId ?? payload.id ?? 0,
    title: payload.romajiName || payload.title || '',
    cover: payload.cover ?? payload.portraitUrl ?? '',
    progress: 0,
    total: payload.totalEpisodes ?? payload.total ?? 0,
    aired: payload.currentAiringEpisode ?? payload.aired ?? 0,
    airing: payload.airingStatus === 'RELEASING',
    airingStatus: payload.airingStatus ?? '',
    favorite: false,
    dayOfWeek: payload.airingDay ?? null,
    genre: (payload.genres ?? []).join(', '),
    isCompleted: false,
    source: payload.source ?? null,
    seasonYear: payload.seasonYear ?? 0,
    season: payload.season ?? '',
    format: payload.format ?? '',
    isTracked: false,
  }
}