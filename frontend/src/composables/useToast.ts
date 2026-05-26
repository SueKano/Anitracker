import { reactive } from 'vue'

interface Toast {
  id: number
  message: string
  type: 'error' | 'success' | 'warning'
}

const state = reactive<{ toasts: Toast[] }>({ toasts: [] })
let nextId = 0

export function useToast() {
  function show(message: string, type: Toast['type'] = 'error', duration = 3000) {
    const id = nextId++
    state.toasts.push({ id, message, type })
    setTimeout(() => {
      const i = state.toasts.findIndex(t => t.id === id)
      if (i !== -1) state.toasts.splice(i, 1)
    }, duration)
  }

  return {
    toasts: state.toasts,
    error: (msg: string) => show(msg, 'error'),
    success: (msg: string) => show(msg, 'success'),
    warning: (msg: string) => show(msg, 'warning'),
  }
}