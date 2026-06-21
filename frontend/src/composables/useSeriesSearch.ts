import { ref, watch } from 'vue'
import type { Anime } from '../types/Anime'
import { useToast } from './useToast'
import { mapAnimeFromApi } from '../utils/mapAnimeFromApi'
import { t } from '../i18n'

export function useSeriesSearch() {
  const toast = useToast()
  const searchQuery = ref('')
  const searchFocused = ref(false)
  const searchResults = ref<Anime[]>([])
  const searchLoading = ref(false)
  const searchHasMore = ref(false)
  const searchLimited = ref(false)
  const searchPage = ref(0)
  let searchDebounce: ReturnType<typeof setTimeout> | null = null
  let searchController: AbortController | null = null

  async function fetchResults(query: string, page: number, append: boolean) {
    searchController?.abort()
    const controller = new AbortController()
    searchController = controller

    searchLoading.value = true
    try {
      const response = await fetch(`/api/series/search?animeName=${encodeURIComponent(query)}&page=${page}`, { credentials: 'include', signal: controller.signal })
      if (!response.ok) {
        toast.error(t('toast.needLoginSearch'))
        return
      }
      const data = await response.json()
      const mapped = (data.series ?? []).map(mapAnimeFromApi)
      searchResults.value = append ? [...searchResults.value, ...mapped] : mapped
      searchHasMore.value = data.hasMore ?? false
      searchLimited.value = data.limited ?? false

    } catch (error) {
      if ((error as Error).name === 'AbortError') return
      if (!append) searchResults.value = []
      toast.error(t('toast.searchError'))
    } finally {
      if (searchController === controller) searchLoading.value = false
    }
  }

  async function loadMoreResults() {
    searchPage.value++
    await fetchResults(searchQuery.value, searchPage.value, true)
  }

  function clearSearch() {
    searchQuery.value = ''
    searchFocused.value = false
    searchResults.value = []
  }

  watch(searchQuery, (query) => {
    if (searchDebounce) clearTimeout(searchDebounce)
    searchPage.value = 0
    searchHasMore.value = false
    searchLimited.value = false
    if (!query || query.length < 3) {
      searchResults.value = []
      return
    }
    searchDebounce = setTimeout(() => {
      void fetchResults(query, 0, false)
    }, 500)
  })

  return { searchQuery, searchFocused, searchResults, searchLoading, searchHasMore, searchLimited, loadMoreResults, clearSearch }
}