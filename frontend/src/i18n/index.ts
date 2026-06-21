import { createI18n } from 'vue-i18n'
import es from './locales/es.json'
import en from './locales/en.json'

export const SUPPORTED_LOCALES = ['es', 'en'] as const
export type Locale = (typeof SUPPORTED_LOCALES)[number]

const DEFAULT_LOCALE: Locale = 'en'
const STORAGE_KEY = 'locale'

function isSupported(value: string): value is Locale {
  return (SUPPORTED_LOCALES as readonly string[]).includes(value)
}

function detectLocale(): Locale {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored && isSupported(stored)) return stored

  const fromBrowser = navigator.language.slice(0, 2).toLowerCase()
  if (isSupported(fromBrowser)) return fromBrowser

  return DEFAULT_LOCALE
}

export const i18n = createI18n({
  legacy: false,
  locale: detectLocale(),
  fallbackLocale: DEFAULT_LOCALE,
  messages: { es, en },
})

export function t(key: string, named?: Record<string, unknown>): string {
  return named ? i18n.global.t(key, named) : i18n.global.t(key)
}

export function errorMessage(errorCode: string): string {
  const key = `errors.${errorCode}`
  return i18n.global.te(key) ? i18n.global.t(key) : t('errors.unknown')
}

export function setLocale(locale: Locale) {
  i18n.global.locale.value = locale
  localStorage.setItem(STORAGE_KEY, locale)
  applyDocumentLocale(locale)
}

export function applyDocumentLocale(locale: Locale) {
  document.documentElement.lang = locale
  document.title = t('meta.title')

  const description = t('meta.description')
  let tag = document.querySelector('meta[name="description"]')
  if (!tag) {
    tag = document.createElement('meta')
    tag.setAttribute('name', 'description')
    document.head.appendChild(tag)
  }
  tag.setAttribute('content', description)
}

export function currentLocale(): Locale {
  return i18n.global.locale.value as Locale
}