import { ref, watch } from 'vue'
import type { Anime } from '../types/Anime'
import { useToast } from './useToast'
import { mapAnimeFromApi } from '../utils/mapAnimeFromApi'

export function useSeriesSearch() {
  const toast = useToast()
  const searchQuery = ref('')
  const searchFocused = ref(false)
  const searchResults = ref<Anime[]>([])
  const searchLoading = ref(false)
  const searchHasMore = ref(false)
  const searchPage = ref(0)
  let searchDebounce: ReturnType<typeof setTimeout> | null = null

  async function fetchResults(query: string, page: number, append: boolean) {
    searchLoading.value = true
    try {
      const response = await fetch(`/api/series/search?animeName=${encodeURIComponent(query)}&page=${page}`, { credentials: 'include' })
      if (!response.ok) {
        toast.error('Necesitas estar logeado para poder buscar series')
        return
      }
      const data = await response.json()
      const mapped = (data.series ?? []).map(mapAnimeFromApi)
      searchResults.value = append ? [...searchResults.value, ...mapped] : mapped
      searchHasMore.value = data.hasMore ?? false
    } catch {
      if (!append) searchResults.value = []
      toast.error('Error al buscar animes')
    } finally {
      searchLoading.value = false
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
    if (!query || query.length < 2) {
      searchResults.value = []
      searchPage.value = 0
      searchHasMore.value = false
      return
    }
    searchDebounce = setTimeout(() => {
      searchPage.value = 0
      void fetchResults(query, 0, false)
    }, 350)
  })

  return {searchQuery, searchFocused, searchResults, searchLoading, searchHasMore, loadMoreResults, clearSearch}
}