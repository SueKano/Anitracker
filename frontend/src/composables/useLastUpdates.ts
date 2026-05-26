import { ref, computed, onMounted } from 'vue'
import { useToast } from './useToast'
import type { LastUpdate, LastUpdateView, LastUpdateResponse } from '../types/LastUpdate'

export function useLastUpdates() {
  const toast = useToast()
  const lastUpdates = ref<LastUpdate[]>([])

  const lastUpdatesView = computed<LastUpdateView[]>(() =>
    lastUpdates.value.map(update => ({ ...update, ...describeUpdate(update) }))
  )

  async function fetchLastUpdates() {
    try {
      const response = await fetch('/api/user/getLastUpdates', { credentials: 'include' })
      if (!response.ok) {
        toast.error('No se pudo cargar la actividad reciente')
        return
      }
      const data: LastUpdateResponse = await response.json()
      lastUpdates.value = (data.lastUpdates ?? []).map(update => ({
        id: update.series.anilistId,
        title: update.series.romajiName,
        cover: update.series.portraitUrl,
        isCompleted: update.isCompleted,
        progress: update.lastEpisodeWatchedCount,
      }))
    } catch {
      toast.error('No se pudo cargar la actividad reciente')
    }
  }

  onMounted(fetchLastUpdates)

  return { lastUpdatesView }
}

function describeUpdate(update: LastUpdate): { prefix: string; suffix: string } {
  if (update.progress === 0) return { prefix: 'Has añadido ', suffix: ' a tu lista' }
  if (update.isCompleted) return { prefix: 'Has terminado ', suffix: '' }
  return { prefix: `Has visto el capítulo ${update.progress} de `, suffix: '' }
}