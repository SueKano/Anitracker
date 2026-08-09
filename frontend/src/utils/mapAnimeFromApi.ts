import type { Anime } from '../types/Anime'

export interface AnimeApiPayload {
  anilistId: number
  romajiName: string
  portraitUrl?: string | null
  totalEpisodes: number
  currentAiringEpisode: number
  airingStatus: string
  isAdult: boolean
  airingDay?: string | null
  genres?: string[]
  source?: string
  seasonYear?: number | null
  season?: string | null
  format?: string
}

export function mapAnimeFromApi(payload: AnimeApiPayload): Anime {
  return {
    id: payload.anilistId,
    title: payload.romajiName,
    cover: payload.portraitUrl ?? '',
    progress: 0,
    episodesWatched: 0,
    total: payload.totalEpisodes,
    aired: payload.currentAiringEpisode,
    airing: payload.airingStatus === 'RELEASING',
    airingStatus: payload.airingStatus,
    isAdult: payload.isAdult,
    favorite: false,
    dayOfWeek: payload.airingDay ?? null,
    genres: payload.genres ?? [],
    isCompleted: false,
    isRewatching: false,
    score: 0,
    source: payload.source ?? null,
    seasonYear: payload.seasonYear ?? 0,
    season: payload.season ?? '',
    format: payload.format ?? '',
    isTracked: false,
  }
}