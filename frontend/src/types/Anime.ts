export type Anime = {
  id: number
  title: string
  cover: string
  progress: number
  total: number
  aired: number
  airing: boolean
  airingStatus: string
  favorite: boolean
  dayOfWeek: string | null
  genre: string
  isCompleted: boolean
  source: string | null
  seasonYear: number
  season: string
  format: string
  isTracked: boolean
}