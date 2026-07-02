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

  async function submitUserUpdate(formData: FormData): Promise<boolean> {
    try {
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

  async function renameUser(username: string): Promise<boolean> {
    const formData = new FormData()
    formData.append('username', username)
    return submitUserUpdate(formData)
  }

  async function updateAvatar(username: string, avatar: File): Promise<boolean> {
    const formData = new FormData()
    formData.append('username', username)
    formData.append('newProfileImage', avatar)
    return submitUserUpdate(formData)
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

  return { deleteAccount, renameUser, updateAvatar, changePassword }
}