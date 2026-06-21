import { ref, computed, onMounted } from 'vue'
import { useToast } from './useToast'
import type { LastUpdate, LastUpdateView, LastUpdateResponse } from '../types/LastUpdate'
import { t } from '../i18n'

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
        toast.error(t('toast.activityLoadError'))
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
      toast.error(t('toast.activityLoadError'))
    }
  }

  onMounted(fetchLastUpdates)

  return { lastUpdatesView }
}

function describeUpdate(update: LastUpdate): { prefix: string; suffix: string } {
  if (update.progress === 0) return { prefix: t('activity.addedPrefix'), suffix: t('activity.addedSuffix') }
  if (update.isCompleted) return { prefix: t('activity.finishedPrefix'), suffix: '' }
  return { prefix: t('activity.watchedPrefix', { count: update.progress }), suffix: '' }
}