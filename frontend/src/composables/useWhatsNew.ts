import { ref } from 'vue'

export const WHATS_NEW_VERSION = '3.0'

const STORAGE_KEY = 'whatsNewSeen'

const whatsNewOpen = ref(false)

export function useWhatsNew() {
  function openWhatsNewIfUnseen() {
    if (localStorage.getItem(STORAGE_KEY) !== WHATS_NEW_VERSION) whatsNewOpen.value = true
  }

  function dismissWhatsNew() {
    localStorage.setItem(STORAGE_KEY, WHATS_NEW_VERSION)
    whatsNewOpen.value = false
  }

  return { whatsNewOpen, openWhatsNewIfUnseen, dismissWhatsNew }
}