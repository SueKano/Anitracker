import { ref } from 'vue'
import type { Anime } from '../types/Anime'
import type { ApiError, UserSeriesPayload } from '../types/Api'
import { useToast } from './useToast'
import { progressPercent, availableEpisodes } from '../utils/animeStats'
import { t, errorMessage } from '../i18n'

export function useUserSeries() {
  const toast = useToast()
  const animeList = ref<Anime[]>([])
  const pendingEpisodeIds = ref<Set<number>>(new Set())
  let listAbortController: AbortController | null = null

  async function fetchUserSeries() {
    listAbortController?.abort()
    listAbortController = new AbortController()
    try {
      const response = await fetch('/api/series/getUserSeries', { credentials: 'include', signal: listAbortController.signal })
      if (!response.ok) {
        toast.error(t('toast.seriesLoadError'))
        return
      }

      const data = await response.json() as { userSeries: UserSeriesPayload[] }
      animeList.value = data.userSeries.map((userSeries): Anime => ({
        id: userSeries.series.anilistId,
        title: userSeries.series.romajiName,
        cover: userSeries.series.portraitUrl ?? '',
        progress: userSeries.lastEpisodeWatchedCount,
        total: userSeries.series.totalEpisodes,
        aired: userSeries.series.currentAiringEpisode,
        airing: userSeries.series.airingStatus === 'RELEASING',
        airingStatus: userSeries.series.airingStatus,
        isAdult: userSeries.series.isAdult,
        favorite: userSeries.isFavourite,
        dayOfWeek: userSeries.series.airingDay,
        genre: userSeries.series.genres.join(', '),
        isCompleted: userSeries.isCompleted,
        season: userSeries.series.season ?? '',
        format: userSeries.series.format,
        source: userSeries.series.source || null,
        seasonYear: userSeries.series.seasonYear ?? 0,
        isTracked: true,
      }))
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') return
      toast.error(t('toast.seriesLoadError'))
    }
  }

  async function addEpisode(anime: Anime) {
    pendingEpisodeIds.value.add(anime.id)
    pendingEpisodeIds.value = new Set(pendingEpisodeIds.value)
    try {
      const response = await fetch('/api/series/addEpisodeToSeries', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anilistId: anime.id }),
        credentials: 'include'
      })
      if (!response.ok) {
        const data = await response.json() as ApiError
        toast.error(errorMessage(data.errorCode))
        return
      }
      const data = await response.json() as { lastEpisodeWatched: number; isCompleted: boolean }
      listAbortController?.abort()
      const target = animeList.value.find(item => item.id === anime.id)
      if (target) {
        target.progress = data.lastEpisodeWatched
        target.isCompleted = data.isCompleted
      }
      if (data.isCompleted) {
        toast.success(anime.format === 'MOVIE' ? t('toast.movieFinished') : t('toast.seriesFinished'))
      } else {
        toast.success(t('toast.episodeAdded'))
      }
    } catch {
      toast.error(t('toast.connectionErrorShort'))
    } finally {
      pendingEpisodeIds.value.delete(anime.id)
      pendingEpisodeIds.value = new Set(pendingEpisodeIds.value)
    }
  }

  async function deleteAnime(anilistId: number) {
    try {
      const response = await fetch('/api/series/deleteSeries', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anilistId })
      })
      if (response.ok) await fetchUserSeries()
      else toast.error(t('toast.seriesDeleteError'))
    } catch {
      toast.error(t('toast.seriesDeleteError'))
    }
  }

  function setFavorite(id: number, isFavourite: boolean) {
    const anime = animeList.value.find(a => a.id === id)
    if (anime) anime.favorite = isFavourite
  }

  function setAiredEpisode(id: number, aired: number, airingStatus: string) {
    const anime = animeList.value.find(a => a.id === id)
    if (anime) {
      anime.aired = aired
      anime.total = aired
      anime.airingStatus = airingStatus
      anime.airing = airingStatus === 'RELEASING'
    }
  }

  function clearList() {
    animeList.value = []
  }

  return {animeList, pendingEpisodeIds, fetchUserSeries, addEpisode, deleteAnime, setFavorite, setAiredEpisode, clearList, progressPercent, availableEpisodes}
}