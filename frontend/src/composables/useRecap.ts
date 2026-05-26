import {ref} from 'vue'
import {useToast} from './useToast'
import type {Recap} from '../types/Recap'

export function useRecap() {
  const toast = useToast()
  const recap = ref<Recap | null>(null)
  const loading = ref(false)

  async function fetchRecap(year: number) {
    loading.value = true
    try {
      const response = await fetch(`/api/recap/${year}`, { credentials: 'include' })
      if (!response.ok) {
        toast.error('No se pudo cargar el recap')
        recap.value = null
        return
      }
      recap.value = await response.json()
    } catch {
      toast.error('No se pudo cargar el recap')
      recap.value = null
    } finally {
      loading.value = false
    }
  }

  return { recap, loading, fetchRecap }
}