import { ref } from 'vue'
import { useToast } from './useToast'
import { t, errorMessage } from '../i18n'
import type { ApiError } from '../types/Api'
import type { ImportResult } from '../types/Import'

export function useAnilistImport() {
  const toast = useToast()
  const importing = ref(false)
  const result = ref<ImportResult | null>(null)

  async function importFromAnilist(userName: string): Promise<boolean> {
    importing.value = true
    result.value = null
    try {
      const response = await fetch('/api/user/import/anilist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userName }),
        credentials: 'include'
      })
      const data = await response.json() as ImportResult & ApiError
      if (!response.ok) {
        toast.error(errorMessage(data.errorCode))
        return false
      }
      result.value = data
      return true
    } catch {
      toast.error(t('toast.connectionError'))
      return false
    } finally {
      importing.value = false
    }
  }

  return { importing, result, importFromAnilist }
}