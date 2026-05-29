import { ref } from 'vue'
import { useToast } from './useToast'

export function useSession() {
  const toast = useToast()
  const isLoggedIn = ref(false)
  const currentUsername = ref('')
  const currentProfileImage = ref<string | null>(null)

  async function checkSession() {
    try {
      const response = await fetch('/api/me', { credentials: 'include' })
      if (!response.ok) return
      const data = await response.json()
      currentUsername.value = data.username
      currentProfileImage.value = data.profileImage || null
      isLoggedIn.value = true
    } catch {
      toast.error('Error al verificar tu sesión')
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
  }

  return {isLoggedIn, currentUsername, currentProfileImage, checkSession, setUser, clearSession}
}