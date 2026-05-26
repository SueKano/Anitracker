type SeriesSerialized = {
  romajiName: string
  portraitUrl: string
  season: string
  seasonYear: number
}

type UserSeriesSerialized = {
  series: SeriesSerialized
  completedAt: string
}

export type Recap = {
  year: number
  worksCompleted: { total: number; formats: Record<string, number> }
  worksAdded: number
  episodesWatched: number
  topGenres: Record<string, number>
  topSeason: string | null
  topSeriesSeason: UserSeriesSerialized[]
  totalSeriesSeason: number
  firstWatched: UserSeriesSerialized
  lastWatched: UserSeriesSerialized
  slowestSeries: { userSeries: UserSeriesSerialized | null; duration: number }
  fastestSeries: { userSeries: UserSeriesSerialized | null; duration: number }
}