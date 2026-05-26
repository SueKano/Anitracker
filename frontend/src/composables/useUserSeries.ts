import { ref } from 'vue'
import type { Anime } from '../types/Anime'
import { useToast } from './useToast'
import { progressPercent, availableEpisodes } from '../utils/animeStats'

export function useUserSeries() {
  const toast = useToast()
  const animeList = ref<Anime[]>([])
  const pendingEpisodeIds = ref<Set<number>>(new Set())

  async function fetchUserSeries() {
    try {
      const response = await fetch('/api/series/getUserSeries', { credentials: 'include' })
      if (!response.ok) {
        toast.error('No se pudieron cargar tus series')
        return
      }
      const data = await response.json()
      animeList.value = (data.userSeries ?? []).map((userSeries: any) => ({
        id: userSeries.series.anilistId,
        title: userSeries.series.romajiName,
        cover: userSeries.series.portraitUrl,
        progress: userSeries.lastEpisodeWatchedCount,
        total: userSeries.series.totalEpisodes,
        aired: userSeries.series.currentAiringEpisode,
        airing: userSeries.series.airingStatus === 'RELEASING',
        airingStatus: userSeries.series.airingStatus,
        favorite: userSeries.isFavourite,
        dayOfWeek: userSeries.series.airingDay,
        genre: (userSeries.series.genres ?? []).join(', '),
        isCompleted: userSeries.isCompleted,
        season: userSeries.series.season,
        format: userSeries.series.format,
        source: userSeries.series.source || null,
        seasonYear: userSeries.series.seasonYear,
        isTracked: true,
      }))
    } catch {
      toast.error('No se pudieron cargar tus series')
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
      const data = await response.json()
      if (response.ok) {
        anime.progress = data.lastEpisodeWatched
        anime.isCompleted = data.isCompleted
        if (data.isCompleted) {
          toast.success(anime.format === 'MOVIE' ? 'Película finalizada' : 'Serie finalizada')
        } else {
          toast.success('Capítulo añadido')
        }
      } else {
        toast.error(data.error)
      }
    } catch {
      toast.error('Error de conexión')
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
      else toast.error('No se pudo eliminar la serie')
    } catch {
      toast.error('No se pudo eliminar la serie')
    }
  }

  function setFavorite(id: number, isFavourite: boolean) {
    const anime = animeList.value.find(a => a.id === id)
    if (anime) anime.favorite = isFavourite
  }

  function clearList() {
    animeList.value = []
  }

  return {animeList, pendingEpisodeIds, fetchUserSeries, addEpisode, deleteAnime, setFavorite, clearList, progressPercent, availableEpisodes}
}