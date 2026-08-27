<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import CalendarPage from './CalendarPage.vue'
import FavoritesPage from './FavoritesPage.vue'
import AnimeDetail from './AnimeDetail.vue'
import LoginPage from './LoginPage.vue'
import UserProfilePage from './UserProfilePage.vue'
import RecapWidget from './RecapWidget.vue'
import ScoreModal from './ScoreModal.vue'
import WhatsNewModal from './WhatsNewModal.vue'
import ImportListPage from './ImportListPage.vue'
import { useSession } from '../composables/useSession'
import { useUserSeries } from '../composables/useUserSeries'
import { useSeries } from '../composables/useSeries'
import { useSeriesSearch } from '../composables/useSeriesSearch'
import { useRecap } from '../composables/useRecap'
import { useAdultGate } from '../composables/useAdultGate'
import { useWhatsNew } from '../composables/useWhatsNew'
import { useToast } from '../composables/useToast'
import type { Anime } from '../types/Anime'
import { translateDay, resolveAiringStatus } from '../utils/translateLabels'
import { Search, User, MoreVertical, Trash2, Home, Calendar, Heart, Sparkles, ArrowDownUp, Download } from 'lucide-vue-next'
import { episodesBehind } from '../utils/animeStats'

const { isLoggedIn, currentUsername, currentProfileImage, isAdmin, checkSession, setUser, clearSession } = useSession()
const { animeList, listLoaded, pendingEpisodeIds, justCompletedAnime, fetchUserSeries, addEpisode, deleteAnime, setFavorite, applyScore, markRewatch, setAiredEpisode, clearList, progressPercent, availableEpisodes } = useUserSeries()
const { setScore } = useSeries()
const { searchQuery, searchFocused, searchResults, searchLoading, searchHasMore, searchLimited, loadMoreResults, clearSearch } = useSeriesSearch()
const { recap, fetchRecap } = useRecap()
const { modalOpen, ensureAdultAccess, confirmAdult, cancelAdult } = useAdultGate()
const { whatsNewOpen, openWhatsNewIfUnseen, dismissWhatsNew } = useWhatsNew()
const toast = useToast()
const { t } = useI18n()

const isDecember = computed(() => new Date().getMonth() === 11)
const showRecap = ref(false)
const importOpen = ref(false)

const searchScrollEl = ref<HTMLElement | null>(null)
const searchAtBottom = ref(false)

const activeFilter = ref<'watching' | 'completed' | 'all'>('watching')

type SortKey = 'default' | 'alpha' | 'pending'
const sortOptions: SortKey[] = ['default', 'alpha', 'pending']
const sortKey = ref<SortKey>('default')
const sortMenuOpen = ref(false)
const activeTab = ref<'home' | 'calendar' | 'favorites' | 'profile'>('home')

const selectedAnimeId = ref<number | null>(null)
const selectedNewAnime = ref<Anime | null>(null)
const isNewAnime = ref(false)
const openMenuId = ref<number | null>(null)

const REFRESH_INTERVAL_MS = 5 * 60 * 1000
let refreshIntervalId: ReturnType<typeof setInterval> | undefined

function updateSearchAtBottom() {
  const el = searchScrollEl.value
  if (!el) {
    searchAtBottom.value = false
    return
  }
  const hasOverflow = el.scrollHeight > el.clientHeight
  const nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 24
  searchAtBottom.value = !hasOverflow || nearBottom
}

watch(searchQuery, () => {
  if (searchScrollEl.value) searchScrollEl.value.scrollTop = 0
  searchAtBottom.value = false
})

watch(searchResults, () => { void nextTick(updateSearchAtBottom) })

watch(activeTab, tab => {
  if (tab === 'home' && isLoggedIn.value) fetchUserSeries()
})

async function saveJustCompletedScore(score: number) {
  const completed = justCompletedAnime.value
  if (!completed) return
  if (!(await setScore(completed.id, score))) return
  applyScore(completed.id, score)
  justCompletedAnime.value = null
}

async function openRecap() {
  const result = await fetchRecap(new Date().getFullYear())
  if (result === 'insufficient') toast.warning(t('toast.recapNeed8'))
  if (result === 'ok') showRecap.value = true
}

const selectedAnime = computed<Anime | null>(() => {
  if (isNewAnime.value) return selectedNewAnime.value
  if (selectedAnimeId.value === null) return null
  return animeList.value.find(anime => anime.id === selectedAnimeId.value) ?? null
})

const filterPredicates = {
  watching: (anime: Anime) => !anime.isCompleted || anime.isRewatching,
  completed: (anime: Anime) => anime.isCompleted && !anime.isRewatching,
  all: () => true,
}

const filteredList = computed(() => {
  const list = animeList.value.filter(filterPredicates[activeFilter.value])
  if (sortKey.value === 'alpha') list.sort((first, second) => first.title.localeCompare(second.title))
  else if (sortKey.value === 'pending') list.sort((first, second) => episodesBehind(second) - episodesBehind(first))

  return list
})

const currentSortLabel = computed(() => t('common.sort.' + sortKey.value))

function selectSort(key: SortKey) {
  sortKey.value = key
  sortMenuOpen.value = false
}

const filterCounts = computed(() => ({
  watching: animeList.value.filter(filterPredicates.watching).length,
  completed: animeList.value.filter(filterPredicates.completed).length,
  all: animeList.value.length,
}))

function toggleMenu(id: number) { openMenuId.value = openMenuId.value === id ? null : id }
async function selectSearchResult(result: Anime) {
  if (!(await ensureAdultAccess(result.isAdult))) return
  const alreadyTracked = animeList.value.find(anime => anime.id === result.id)
  isNewAnime.value = !alreadyTracked
  selectedNewAnime.value = alreadyTracked ? null : { ...result }
  selectedAnimeId.value = alreadyTracked ? alreadyTracked.id : null
  clearSearch()
}

async function openDetail(id: number) {
  const anime = animeList.value.find(a => a.id === id)
  if (!anime) return
  if (!(await ensureAdultAccess(anime.isAdult))) return
  isNewAnime.value = false
  selectedNewAnime.value = null
  selectedAnimeId.value = id
}

function closeDetail() {
  selectedAnimeId.value = null
  selectedNewAnime.value = null
  isNewAnime.value = false
}

function handleAddedToHome() {
  closeDetail()
  activeTab.value = 'home'
  fetchUserSeries()
}

function handleLoginSuccess(username: string) {
  setUser(username)
  activeTab.value = 'home'
  openWhatsNewIfUnseen()
}

async function handleRegisterSuccess(username: string){
  setUser(username)
  activeTab.value= 'home'
}

function handleAccountDeleted() {
  clearSession()
  clearList()
  activeTab.value = 'home'
}

function handleGoogleLogin() {
  window.location.href = '/api/auth/google'
}

function refreshIfLoggedIn() {
  if (isLoggedIn.value) fetchUserSeries()
}

function refreshIfVisible() {
  if (document.visibilityState === 'visible') refreshIfLoggedIn()
}

function refreshIfRestoredFromCache(event: PageTransitionEvent) {
  if (event.persisted) refreshIfLoggedIn()
}

onMounted(async () => {
  document.addEventListener('visibilitychange', refreshIfVisible)
  document.addEventListener('resume', refreshIfLoggedIn)
  window.addEventListener('focus', refreshIfLoggedIn)
  window.addEventListener('pageshow', refreshIfRestoredFromCache)
  window.addEventListener('online', refreshIfLoggedIn)
  refreshIntervalId = setInterval(refreshIfVisible, REFRESH_INTERVAL_MS)

  await checkSession()
  if (isLoggedIn.value) {
    await fetchUserSeries()
    openWhatsNewIfUnseen()
  }
})

onUnmounted(() => {
  document.removeEventListener('visibilitychange', refreshIfVisible)
  document.removeEventListener('resume', refreshIfLoggedIn)
  window.removeEventListener('focus', refreshIfLoggedIn)
  window.removeEventListener('pageshow', refreshIfRestoredFromCache)
  window.removeEventListener('online', refreshIfLoggedIn)
  clearInterval(refreshIntervalId)
})

const tabs: { id: typeof activeTab.value, icon: string }[] = [
  { id: 'home',      icon: 'home' },
  { id: 'calendar',  icon: 'calendar' },
  { id: 'favorites', icon: 'heart' },
  { id: 'profile',   icon: 'profile'}
]

const filters = [
  { key: 'watching' as const },
  { key: 'completed' as const },
  { key: 'all' as const },
]
</script>

<template>
  <div v-if="modalOpen" class="adult-gate-overlay" @click.self="cancelAdult">
    <div class="adult-gate">
      <h3 class="adult-gate-title">{{ t('adultGate.title') }}</h3>
      <p class="adult-gate-text">{{ t('adultGate.message') }}</p>
      <div class="adult-gate-actions">
        <button class="adult-gate-cancel" @click="cancelAdult">{{ t('adultGate.cancel') }}</button>
        <button class="adult-gate-confirm" @click="confirmAdult">{{ t('adultGate.confirm') }}</button>
      </div>
    </div>
  </div>

  <WhatsNewModal v-if="whatsNewOpen" @close="dismissWhatsNew"/>
  <ScoreModal v-if="justCompletedAnime" :title="justCompletedAnime.title" :initial-score="0"
              @save="saveJustCompletedScore" @close="justCompletedAnime = null"/>

  <RecapWidget v-if="showRecap && recap" :recap="recap" @close="showRecap = false"/>
  <AnimeDetail v-if="selectedAnime" :anime="selectedAnime" :is-new="isNewAnime" :is-admin="isAdmin" @close="closeDetail"
               @favorite-changed="setFavorite" @rewatch-started="markRewatch" @score-changed="applyScore"
               @adult-episode-changed="setAiredEpisode" @goToHome="handleAddedToHome"/>

  <ImportListPage v-if="activeTab === 'home' && importOpen" @back="importOpen = false" @imported="fetchUserSeries"/>

  <header v-show="activeTab === 'home' && !importOpen" class="header">
    <div class="header-row">
      <span class="brand">ATRK</span>
      <div class="search-wrap">
        <div class="search" :class="{ focused: searchFocused || searchQuery.length > 0 }">
          <Search :stroke-width="2.2" />
          <input v-model="searchQuery" type="text" :placeholder="t('home.searchPlaceholder')" @focus="searchFocused = true" @blur="searchFocused = false" />
        </div>
        <Transition name="pop">
          <div v-if="searchFocused && (searchResults.length > 0 || searchLoading || searchLimited)" class="search-dropdown">
            <div ref="searchScrollEl" class="search-results-scroll" @scroll="updateSearchAtBottom">
              <div v-if="searchLoading" class="search-loading">{{ t('home.searching') }}</div>
              <button v-for="r in searchResults" :key="r.id" class="search-item" @mousedown.prevent="selectSearchResult(r)">
                <img :src="r.cover" :alt="r.title" class="search-item-cover" />
                <div class="search-item-info">
                  <span class="search-item-title">{{ r.title }}</span>
                  <span class="search-item-meta">
                    {{ t(`home.status.${resolveAiringStatus(r)}`) }}
                    · {{ t('home.episodesShort', { count: r.total }) }}
                  </span>
                </div>
              </button>
            </div>
            <button v-if="searchHasMore && !searchLoading && searchAtBottom" class="search-load-more" @mousedown.prevent="loadMoreResults">
              {{ t('home.loadMore') }}
            </button>
            <div v-if="searchLimited && !searchLoading" class="search-limited">{{ t('home.searchLimited') }}</div>
          </div>
        </Transition>
      </div>

      <button class="avatar-btn" :aria-label="t('home.profileAria')" @click="activeTab = 'profile'">
        <img v-if="isLoggedIn && currentProfileImage" :src="currentProfileImage" alt="Avatar" class="avatar-img" />
        <span v-else-if="isLoggedIn" class="avatar-letter">{{ currentUsername.charAt(0).toUpperCase() }}</span>
        <User v-else :stroke-width="1.8" />
      </button>
    </div>

    <div class="filter-row">
      <div class="filter-scroll">
        <button v-for="filter in filters" :key="filter.key" class="filter-btn" :class="{ active: activeFilter === filter.key }" @click="activeFilter = filter.key">
          {{ t('home.filters.' + filter.key) }}
          <span v-if="activeFilter === filter.key" class="filter-count">({{ filterCounts[filter.key] }})</span>
        </button>
      </div>

      <div v-if="animeList.length > 0" class="sort-wrap">
        <div v-if="sortMenuOpen" class="menu-overlay" @click="sortMenuOpen = false" />
        <button class="menu-btn" :class="{ active: sortMenuOpen || sortKey !== 'default', 'has-sort': sortKey !== 'default' }" :aria-label="t('common.sortAria')"
                @click="sortMenuOpen = !sortMenuOpen">
          <ArrowDownUp :stroke-width="1.8" />
          <span>{{ currentSortLabel }}</span>
        </button>
        <Transition name="pop">
          <div v-if="sortMenuOpen" class="menu-list">
            <button v-for="option in sortOptions" :key="option" class="menu-item" :class="{ active: sortKey === option }"
                    @click="selectSort(option)">
              {{ t('common.sort.' + option) }}
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </header>

  <CalendarPage  v-if="activeTab === 'calendar'"  :anime-list="animeList" @select="openDetail" />
  <FavoritesPage v-if="activeTab === 'favorites'" :anime-list="animeList" @select="openDetail" />
  <UserProfilePage v-if="activeTab === 'profile' && isLoggedIn" :username="currentUsername" :profile-image="currentProfileImage" :anime-list="animeList"
                   @back="activeTab = 'home'" @account-deleted="handleAccountDeleted" @profile-updated="currentUsername = $event; checkSession()"
                   @select-anime="openDetail" @imported="fetchUserSeries" />
  <LoginPage v-if="activeTab === 'profile' && !isLoggedIn" @login-success="handleLoginSuccess" @register-success="handleRegisterSuccess"
             @google-login="handleGoogleLogin"/>

  <main v-show="activeTab === 'home' && !importOpen" class="list">
    <button v-if="listLoaded && isLoggedIn && !animeList.length" class="import-cta" @click="importOpen = true">
      <Download :stroke-width="1.8" />
      <span>{{ t('common.importCta') }}</span>
    </button>
    <button v-else-if="isLoggedIn && isDecember" class="recap-cta" @click="openRecap">
      <Sparkles :stroke-width="1.8" />
      <span>{{ t('common.recapCta') }}</span>
    </button>

    <TransitionGroup name="card" tag="ul" class="cards">
      <li v-for="anime in filteredList" :key="anime.id" class="card" @click="openDetail(anime.id)">
        <img class="poster" :src="anime.cover" :alt="anime.title" loading="lazy" />
        <div class="card-body">
          <div class="card-head">
            <h3 class="card-title">{{ anime.title }}</h3>
            <button class="btn-plus" :disabled="anime.progress >= availableEpisodes(anime) || pendingEpisodeIds.has(anime.id)" @click.stop="addEpisode(anime)">
              +1
            </button>
          </div>

          <span class="card-ep">
            EP {{ anime.progress }} / {{ anime.total }}
            <template v-if="anime.airing && anime.dayOfWeek"> · {{ translateDay(anime.dayOfWeek) }}</template>
          </span>
          <div class="card-bar">
            <div class="card-bar-fill" :class="{ done: anime.total > 0 && anime.progress >= anime.total }" :style="{ width: progressPercent(anime) + '%' }"/>
          </div>
        </div>
        <div class="menu-wrap">
          <button class="btn-dots" @click.stop="toggleMenu(anime.id)" :aria-label="t('home.optionsAria')">
            <MoreVertical />
          </button>
          <Transition name="pop">
            <div v-if="openMenuId === anime.id" class="dropdown">
              <button class="drop-item drop-danger" @click.stop="deleteAnime(anime.id); openMenuId = null">
                <Trash2 :stroke-width="2" />
                {{ t('home.delete') }}
              </button>
            </div>
          </Transition>
        </div>
      </li>
    </TransitionGroup>

    <div v-if="listLoaded && filteredList.length === 0" class="empty">
      <svg viewBox="0 0 40 40" fill="none">
        <circle cx="20" cy="20" r="16" stroke="var(--text-3)" stroke-width="1" stroke-dasharray="3 3"/>
        <path d="M14 20h12M14 26h8" stroke="var(--text-3)" stroke-width="1" stroke-linecap="round"/>
      </svg>
      <p>{{ t('home.empty') }}</p>
    </div>
  </main>

  <div v-if="openMenuId !== null" class="overlay" @click="openMenuId = null" />
  <nav class="nav">
    <button v-for="tab in tabs" :key="tab.id" class="nav-btn" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id">
      <Home v-if="tab.icon === 'home'" :stroke-width="1.8" />
      <Calendar v-if="tab.icon === 'calendar'" :stroke-width="1.8" />
      <Heart v-if="tab.icon === 'heart'" :stroke-width="1.8" />
      <User v-if="tab.icon === 'profile'" :stroke-width="1.8" />
      <span>{{ t('nav.' + tab.id) }}</span>
    </button>
  </nav>
</template>

<style scoped>
@import '../styles/HomePage.css';
</style>
