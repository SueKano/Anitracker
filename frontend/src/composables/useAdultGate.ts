import { ref } from 'vue'

const STORAGE_KEY = 'adultConfirmed'

const modalOpen = ref(false)
let pendingResolve: ((granted: boolean) => void) | null = null

function settle(granted: boolean) {
  modalOpen.value = false
  pendingResolve?.(granted)
  pendingResolve = null
}

export function useAdultGate() {
  function ensureAdultAccess(isAdult: boolean): Promise<boolean> {
    if (!isAdult || localStorage.getItem(STORAGE_KEY) === 'true') return Promise.resolve(true)
    pendingResolve?.(false)
    modalOpen.value = true
    return new Promise<boolean>(resolve => { pendingResolve = resolve })
  }

  function confirmAdult() {
    localStorage.setItem(STORAGE_KEY, 'true')
    settle(true)
  }

  function cancelAdult() {
    settle(false)
  }

  return { modalOpen, ensureAdultAccess, confirmAdult, cancelAdult }
}