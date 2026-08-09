export type LastUpdate = {
  id: number
  title: string
  cover: string
  isCompleted: boolean
  isRewatching: boolean
  progress: number
}

export type LastUpdateView = LastUpdate & {
  prefix: string
  suffix: string
}

export type LastUpdateResponse = {
  activityWindowDays: number
  lastUpdates?: Array<{
    series: {
      anilistId: number
      romajiName: string
      portraitUrl: string
    }
    isCompleted: boolean
    isRewatching: boolean
    lastEpisodeWatchedCount: number
  }>
}