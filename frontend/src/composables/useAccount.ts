import { useToast } from './useToast'
import { t, errorMessage } from '../i18n'
import type { ApiError } from '../types/Api'

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
        toast.error(t('toast.deleteAccountError'))
        return false
      }
      return true
    } catch {
      toast.error(t('toast.connectionError'))
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
      const data = await response.json() as ApiError
      if (!response.ok) {
        toast.error(errorMessage(data.errorCode))
        return false
      }
      toast.success(t('toast.profileUpdated'))
      return true
    } catch {
      toast.error(t('toast.connectionError'))
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
      const data = await response.json() as ApiError
      if (!response.ok) {
        toast.error(errorMessage(data.errorCode))
        return false
      }
      toast.success(t('toast.passwordUpdated'))
      return true
    } catch {
      toast.error(t('toast.connectionError'))
      return false
    }
  }

  return { deleteAccount, updateProfile, changePassword }
}