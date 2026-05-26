import { useToast } from './useToast'

export function useAccount() {
  const toast = useToast()

  async function deleteAccount(): Promise<boolean> {
    try {
      const response = await fetch('/api/deleteAccount', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
      })
      if (!response.ok) {
        toast.error('No se pudo borrar la cuenta')
        return false
      }
      return true
    } catch {
      toast.error('Error de conexión con el servidor')
      return false
    }
  }

  async function updateProfile(username: string, avatar: File | null): Promise<boolean> {
    try {
      const formData = new FormData()
      formData.append('username', username)
      if (avatar) formData.append('newProfileImage', avatar)

      const response = await fetch('/api/user/updateUser', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      })
      const data = await response.json()
      if (!response.ok) {
        toast.error(data.error)
        return false
      }
      toast.success('Perfil actualizado')
      return true
    } catch {
      toast.error('Error de conexión con el servidor')
      return false
    }
  }

  async function changePassword(currentPassword: string, newPassword: string): Promise<boolean> {
    try {
      const response = await fetch('/api/changePassword', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ currentPassword, newPassword }),
        credentials: 'include'
      })
      const data = await response.json()
      if (!response.ok) {
        toast.error(data.error)
        return false
      }
      toast.success('Contraseña actualizada')
      return true
    } catch {
      toast.error('Error de conexión con el servidor')
      return false
    }
  }

  return { deleteAccount, updateProfile, changePassword }
}