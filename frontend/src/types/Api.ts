export type ApiError = { errorCode: string }

export type SeriesPayload = {
  anilistId: number
  romajiName: string
  portraitUrl: string | null
  totalEpisodes: number
  currentAiringEpisode: number
  airingStatus: string
  airingDay: string | null
  genres: string[]
  format: string
  source: string
  season: string | null
  seasonYear: number | null
  isAdult: boolean
}

export type UserSeriesPayload = {
  series: SeriesPayload
  lastEpisodeWatchedCount: number
  countEpisodesCompleted: number
  isFavourite: boolean
  isCompleted: boolean
  isRewatching: boolean
  score: number
}