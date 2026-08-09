import { ref, computed, onMounted } from 'vue'
import { useToast } from './useToast'
import type { LastUpdate, LastUpdateView, LastUpdateResponse } from '../types/LastUpdate'
import { t } from '../i18n'

export function useLastUpdates() {
  const toast = useToast()
  const lastUpdates = ref<LastUpdate[]>([])
  const activityWindowDays = ref(90)

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
      activityWindowDays.value = data.activityWindowDays
      lastUpdates.value = (data.lastUpdates ?? []).map(update => ({
        id: update.series.anilistId,
        title: update.series.romajiName,
        cover: update.series.portraitUrl,
        isCompleted: update.isCompleted,
        isRewatching: update.isRewatching,
        progress: update.lastEpisodeWatchedCount,
      }))
    } catch {
      toast.error(t('toast.activityLoadError'))
    }
  }

  onMounted(fetchLastUpdates)

  return { lastUpdatesView, activityWindowDays }
}

function describeUpdate(update: LastUpdate): { prefix: string; suffix: string } {
  if (update.isRewatching && update.progress === 0) return { prefix: t('activity.rewatchPrefix'), suffix: '' }
  if (update.progress === 0) return { prefix: t('activity.addedPrefix'), suffix: t('activity.addedSuffix') }
  if (update.isCompleted && !update.isRewatching) return { prefix: t('activity.finishedPrefix'), suffix: '' }
  return { prefix: t('activity.watchedPrefix', { count: update.progress }), suffix: '' }
}