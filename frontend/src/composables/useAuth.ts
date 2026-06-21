import { useToast } from './useToast'
import { t, errorMessage } from '../i18n'
import type { ApiError } from '../types/Api'

export function useAuth() {
  const toast = useToast()
  async function login(username: string, password: string): Promise<boolean> {
    try {
      const response = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password }),
        credentials: 'include'
      })
      if (!response.ok) {
        toast.error(t('toast.wrongCredentials'))
        return false
      }

      return true
    } catch {
      toast.error(t('toast.connectionError'))
      return false
    }
  }

  async function register(username: string, password: string, email: string): Promise<boolean> {
    try {
      const response = await fetch('/api/createUser', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, email }),
        credentials: 'include'
      })
      if (!response.ok) {
        const data = await response.json() as ApiError
        toast.error(errorMessage(data.errorCode))
        return false
      }

      toast.success(t('toast.accountCreated'))
      return true
    } catch {
      toast.error(t('toast.connectionError'))
      return false
    }
  }

  return { login, register }
}