type SeriesSerialized = {
  romajiName: string
  portraitUrl: string
  season: string
  seasonYear: number
}

type UserSeriesSerialized = {
  series: SeriesSerialized
  completedAt: string
  score: number
}

export type Recap = {
  year: number
  worksCompleted: { total: number; formats: Record<string, number> }
  episodesWatched: number
  topGenres: { name: string; seriesCount: number; episodes: number; portraits: string[] }[]
  topSeason: string | null
  topSeriesSeason: UserSeriesSerialized[]
  totalSeriesSeason: number
  firstWatched: UserSeriesSerialized
  lastWatched: UserSeriesSerialized
  slowestSeries: { userSeries: UserSeriesSerialized | null; duration: number }
  fastestSeries: { userSeries: UserSeriesSerialized | null; duration: number }
  scoreSeries: { top: UserSeriesSerialized[]; average: number; disappointment: UserSeriesSerialized } | null
}