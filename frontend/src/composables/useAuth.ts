import { useToast } from './useToast'

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
        toast.error('Las credenciales proporcionadas no son correctas')
        return false
      }
      return true
    } catch {
      toast.error('Error de conexión con el servidor')
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
        toast.error('Error al crear la cuenta')
        return false
      }
      toast.success('Cuenta creada correctamente')
      return true
    } catch {
      toast.error('Error de conexión con el servidor')
      return false
    }
  }

  return { login, register }
}