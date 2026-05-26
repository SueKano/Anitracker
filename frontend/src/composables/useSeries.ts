import { useToast } from './useToast'
import { mapAnimeFromApi } from '../utils/mapAnimeFromApi'
import type { Anime } from '../types/Anime'

export function useSeries() {
  const toast = useToast()

  async function fetchAnilistDetails(anilistId: number): Promise<Anime | null> {
    try {
      const response = await fetch(`/api/series/anilist/${anilistId}`, { credentials: 'include' })
      if (!response.ok) {
        toast.error('No se pudo cargar la información del anime')
        return null
      }
      const data = await response.json()
      return {
        ...mapAnimeFromApi(data.series),
        progress: data.tracking?.lastEpisodeWatchedCount ?? 0,
        favorite: data.tracking?.isFavourite ?? false,
        isCompleted: data.tracking?.isCompleted ?? false,
        isTracked: data.tracking !== null,
      }
    } catch {
      toast.error('Error de conexión con el servidor')
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
      const data = await response.json()
      if (response.ok) return true
      toast.error(data.error)
      return false
    } catch {
      toast.error('Error de conexión con el servidor')
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
      const data = await response.json()
      if (response.ok) {
        toast.success(data.isFavourite ? 'Serie añadida a favoritos' : 'Serie eliminada de favoritos')
        return data.isFavourite as boolean
      }
      toast.error(data.error)
      return null
    } catch {
      toast.error('Error de conexión con el servidor')
      return null
    }
  }

  return { fetchAnilistDetails, createUserSeries, toggleFavourite }
}