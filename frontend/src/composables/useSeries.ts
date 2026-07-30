import { useToast } from './useToast'
import { mapAnimeFromApi } from '../utils/mapAnimeFromApi'
import type { Anime } from '../types/Anime'
import type { ApiError } from '../types/Api'
import {errorMessage, t} from '../i18n'

export function useSeries() {
  const toast = useToast()

  async function fetchAnilistDetails(anilistId: number): Promise<Anime | null> {
    try {
      const response = await fetch(`/api/series/anilist/${anilistId}`, { credentials: 'include' })
      if (!response.ok) {
        toast.error(t('toast.animeLoadError'))
        return null
      }
      const data = await response.json()
      return {
        ...mapAnimeFromApi(data.series),
        progress: data.tracking?.lastEpisodeWatchedCount ?? 0,
        episodesWatched: data.tracking?.countEpisodesCompleted ?? 0,
        favorite: data.tracking?.isFavourite ?? false,
        isCompleted: data.tracking?.isCompleted ?? false,
        isRewatching: data.tracking?.isRewatching ?? false,
        isTracked: data.tracking !== null,
      }
    } catch {
      toast.error(t('toast.connectionError'))
      return null
    }
  }

  async function createUserSeries(anilistId: number): Promise<boolean> {
    try {
      const response = await fetch('/api/series/createUserSeries', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anilistId }),
        credentials: 'include'
      })
      const data = await response.json() as ApiError
      if (response.ok) return true
      toast.error(errorMessage(data.errorCode))
      return false
    } catch {
      toast.error(t('toast.connectionError'))
      return false
    }
  }

  async function toggleFavourite(anilistId: number): Promise<boolean | null> {
    try {
      const response = await fetch('/api/series/addSeriesToFavourite', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anilistId }),
        credentials: 'include'
      })
      if (!response.ok) {
        const data = await response.json() as ApiError
        toast.error(errorMessage(data.errorCode))
        return null
      }
      const data = await response.json() as { isFavourite: boolean }
      toast.success(data.isFavourite ? t('toast.favAdded') : t('toast.favRemoved'))
      return data.isFavourite

    } catch {
      toast.error(t('toast.connectionError'))
      return null
    }
  }

  async function rewatch(anilistId: number): Promise<boolean> {
    try {
      const response = await fetch('/api/series/rewatch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anilistId }),
        credentials: 'include'
      })
      if (!response.ok) {
        const data = await response.json() as ApiError
        toast.error(errorMessage(data.errorCode))
        return false
      }
      toast.success(t('toast.rewatchStarted'))
      return true
    } catch {
      toast.error(t('toast.connectionError'))
      return false
    }
  }

  async function updateAdultEpisode(anilistId: number, currentAiringEpisode: number, isFinished: boolean): Promise<boolean> {
    try {
      const response = await fetch('/api/series/updateEpisodeToAdultSeries', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anilistId, currentAiringEpisode, isFinished }),
        credentials: 'include'
      })
      if (response.ok) {
        toast.success(t('toast.adultEpisodeUpdated'))
        return true
      }
      const data = await response.json() as ApiError
      toast.error(errorMessage(data.errorCode))
      return false

    } catch {
      toast.error(t('toast.connectionError'))
      return false
    }
  }

  return { fetchAnilistDetails, createUserSeries, toggleFavourite, rewatch, updateAdultEpisode }
}