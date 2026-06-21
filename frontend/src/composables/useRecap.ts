import {ref} from 'vue'
import {useToast} from './useToast'
import type {Recap} from '../types/Recap'
import { t } from '../i18n'

export function useRecap() {
  const toast = useToast()
  const recap = ref<Recap | null>(null)
  const loading = ref(false)

  async function fetchRecap(year: number) {
    loading.value = true
    try {
      const response = await fetch(`/api/recap/${year}`, { credentials: 'include' })
      if (!response.ok) {
        toast.error(t('toast.recapError'))
        recap.value = null
        return
      }
      recap.value = await response.json()
    } catch {
      toast.error(t('toast.recapError'))
      recap.value = null
    } finally {
      loading.value = false
    }
  }

  return { recap, loading, fetchRecap }
}