import { ref } from 'vue'
import { useToast } from './useToast'
import { t } from '../i18n'

export function useSession() {
  const toast = useToast()
  const isLoggedIn = ref(false)
  const currentUsername = ref('')
  const currentProfileImage = ref<string | null>(null)
  const isAdmin = ref(false)

  async function checkSession() {
    try {
      const response = await fetch('/api/me', { credentials: 'include' })
      if (!response.ok) return
      const data = await response.json()
      currentUsername.value = data.username
      currentProfileImage.value = data.profileImage || null
      isAdmin.value = data.isAdmin
      isLoggedIn.value = true
    } catch {
      toast.error(t('toast.sessionError'))
    }
  }

  function setUser(username: string) {
    currentUsername.value = username
    isLoggedIn.value = true
  }

  function clearSession() {
    isLoggedIn.value = false
    currentUsername.value = ''
    currentProfileImage.value = null
    isAdmin.value = false
  }

  return { isLoggedIn, currentUsername, currentProfileImage, isAdmin, checkSession, setUser, clearSession }
}