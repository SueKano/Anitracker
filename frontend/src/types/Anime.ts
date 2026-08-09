export type Anime = {
  id: number
  title: string
  cover: string
  progress: number
  episodesWatched: number
  total: number
  aired: number
  airing: boolean
  airingStatus: string
  isAdult: boolean
  favorite: boolean
  dayOfWeek: string | null
  genres: string[]
  isCompleted: boolean
  isRewatching: boolean
  score: number
  source: string | null
  seasonYear: number
  season: string
  format: string
  isTracked: boolean
}