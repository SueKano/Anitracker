<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import CalendarPage from './CalendarPage.vue'
import FavoritesPage from './FavoritesPage.vue'
import AnimeDetail from './AnimeDetail.vue'
import LoginPage from './LoginPage.vue'
import UserProfilePage from './UserProfilePage.vue'
import RecapWidget from './RecapWidget.vue'
import { useSession } from '../composables/useSession'
import { useUserSeries } from '../composables/useUserSeries'
import { useSeriesSearch } from '../composables/useSeriesSearch'
import { useRecap } from '../composables/useRecap'
import { useToast } from '../composables/useToast'
import type { Anime } from '../types/Anime'
import { translateDay } from '../utils/translateLabels'
import { Search, User, MoreVertical, Trash2, Home, Calendar, Heart, Sparkles } from 'lucide-vue-next'

const { isLoggedIn, currentUsername, currentProfileImage, checkSession, setUser, clearSession } = useSession()
const { animeList, pendingEpisodeIds, fetchUserSeries, addEpisode, deleteAnime, setFavorite, clearList, progressPercent, availableEpisodes } = useUserSeries()
const { searchQuery, searchFocused, searchResults, searchLoading, searchHasMore, loadMoreResults, clearSearch } = useSeriesSearch()
const { recap, fetchRecap } = useRecap()
const toast = useToast()

const isDecember = computed(() => new Date().getMonth() === 11)
const showRecap = ref(false)

async function openRecap() {
  await fetchRecap(new Date().getFullYear())
  if (!recap.value) {
    toast.warning('Se necesitan 8 series para poder crear tu recap')
    return
  }
  showRecap.value = true
}

const activeFilter = ref<'watching' | 'completed' | 'all'>('watching')
const activeTab = ref<'home' | 'calendar' | 'favorites' | 'profile'>('home')

const selectedAnime = ref<Anime | null>(null)
const isNewAnime = ref(false)
const openMenuId = ref<number | null>(null)

const filteredList = computed(() => {
  if (activeFilter.value === 'all') return animeList.value
  if (activeFilter.value === 'completed') return animeList.value.filter(a => a.isCompleted)
  return animeList.value.filter(a => !a.isCompleted)
})

function toggleMenu(id: number) { openMenuId.value = openMenuId.value === id ? null : id }
function selectSearchResult(result: Anime) {
  const alreadyTracked = animeList.value.find(a => a.id === result.id)
  selectedAnime.value = alreadyTracked ?? { ...result }
  isNewAnime.value = !alreadyTracked
  clearSearch()
}

function openDetail(id: number) {
  selectedAnime.value = animeList.value.find(a => a.id === id) ?? null
  isNewAnime.value = false
}

async function handleLoginSuccess(username: string) {
  setUser(username)
  activeTab.value = 'home'
  await fetchUserSeries()
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

function handleVisibilityChange() {
  if (document.visibilityState === 'visible' && isLoggedIn.value) fetchUserSeries()
}

onMounted(async () => {
  await checkSession()
  if (isLoggedIn.value) await fetchUserSeries()
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

const tabs: { id: typeof activeTab.value, label: string, icon: string }[] = [
  { id: 'home',      label: 'Inicio',     icon: 'home' },
  { id: 'calendar',  label: 'Calendario', icon: 'calendar' },
  { id: 'favorites', label: 'Favoritos',  icon: 'heart' },
  { id: 'profile',   label: 'Perfil',     icon: 'profile'}
]

const filters = [
  { key: 'watching' as const,  label: 'Viendo' },
  { key: 'completed' as const, label: 'Completados' },
  { key: 'all' as const,       label: 'Todos' },
]
</script>

<template>
  <RecapWidget v-if="showRecap && recap" :recap="recap" @close="showRecap = false"/>
  <AnimeDetail v-if="selectedAnime" :anime="selectedAnime" :is-new="isNewAnime" @close="selectedAnime = null; isNewAnime = false"
               @favorite-changed="setFavorite" @goToHome="selectedAnime = null; isNewAnime = false; activeTab = 'home'; fetchUserSeries()"/>

  <header v-show="activeTab === 'home'" class="header">
    <div class="header-row">
      <span class="brand">ATRK</span>
      <div class="search-wrap">
        <div class="search" :class="{ focused: searchFocused || searchQuery.length > 0 }">
          <Search :stroke-width="2.2" />
          <input v-model="searchQuery" type="text" placeholder="Search anime..." @focus="searchFocused = true" @blur="searchFocused = false" />
        </div>
        <Transition name="pop">
          <div v-if="searchFocused && (searchResults.length > 0 || searchLoading)" class="search-dropdown">
            <div v-if="searchLoading" class="search-loading">Buscando...</div>
            <button v-for="r in searchResults" :key="r.id" class="search-item" @mousedown.prevent="selectSearchResult(r)">
              <img :src="r.cover" :alt="r.title" class="search-item-cover" />
              <div class="search-item-info">
                <span class="search-item-title">{{ r.title }}</span>
                <span class="search-item-meta">
                  {{ r.airingStatus === 'NOT_YET_RELEASED' ? 'Próximamente' : (r.airing ? 'En emisión' : 'Completado') }}
                  · {{ r.total }} ep.
                </span>
              </div>
            </button>
            <button v-if="searchHasMore && !searchLoading" class="search-load-more" @mousedown.prevent="loadMoreResults">
              Ver más resultados
            </button>
          </div>
        </Transition>
      </div>

      <button class="avatar-btn" aria-label="Perfil" @click="activeTab = 'profile'">
        <img v-if="isLoggedIn && currentProfileImage" :src="currentProfileImage" alt="Avatar" class="avatar-img" />
        <span v-else-if="isLoggedIn" class="avatar-letter">{{ currentUsername.charAt(0).toUpperCase() }}</span>
        <User v-else :stroke-width="1.8" />
      </button>
    </div>

    <div class="filter-row">
      <button v-for="filter in filters" :key="filter.key" class="filter-btn" :class="{ active: activeFilter === filter.key }" @click="activeFilter = filter.key">
        {{ filter.label }}
      </button>
    </div>
  </header>

  <CalendarPage  v-if="activeTab === 'calendar'"  :anime-list="animeList" @select="openDetail" />
  <FavoritesPage v-if="activeTab === 'favorites'" :anime-list="animeList" @select="openDetail" />
  <UserProfilePage v-if="activeTab === 'profile' && isLoggedIn" :username="currentUsername" :profile-image="currentProfileImage" :anime-list="animeList"
                   @back="activeTab = 'home'" @account-deleted="handleAccountDeleted" @profile-updated="currentUsername = $event; checkSession()"
                   @select-anime="openDetail" />
  <LoginPage v-if="activeTab === 'profile' && !isLoggedIn" @login-success="handleLoginSuccess" @register-success="handleRegisterSuccess"
             @google-login="handleGoogleLogin"/>

  <main v-show="activeTab === 'home'" class="list">
    <button v-if="isDecember" class="recap-cta" @click="openRecap">
      <Sparkles :stroke-width="1.8" />
      <span>Ver tu recap del año</span>
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
            <div class="card-bar-fill" :class="{ done: anime.progress >= anime.total }" :style="{ width: progressPercent(anime) + '%' }"/>
          </div>
        </div>
        <div class="menu-wrap">
          <button class="btn-dots" @click.stop="toggleMenu(anime.id)" aria-label="Opciones">
            <MoreVertical />
          </button>
          <Transition name="pop">
            <div v-if="openMenuId === anime.id" class="dropdown">
              <button class="drop-item drop-danger" @click.stop="deleteAnime(anime.id); openMenuId = null">
                <Trash2 :stroke-width="2" />
                Eliminar
              </button>
            </div>
          </Transition>
        </div>
      </li>
    </TransitionGroup>

    <div v-if="filteredList.length === 0" class="empty">
      <svg viewBox="0 0 40 40" fill="none">
        <circle cx="20" cy="20" r="16" stroke="var(--text-3)" stroke-width="1" stroke-dasharray="3 3"/>
        <path d="M14 20h12M14 26h8" stroke="var(--text-3)" stroke-width="1" stroke-linecap="round"/>
      </svg>
      <p>Tu lista está vacía</p>
    </div>
  </main>

  <div v-if="openMenuId !== null" class="overlay" @click="openMenuId = null" />
  <nav class="nav">
    <button v-for="tab in tabs" :key="tab.id" class="nav-btn" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id">
      <Home v-if="tab.icon === 'home'" :stroke-width="1.8" />
      <Calendar v-if="tab.icon === 'calendar'" :stroke-width="1.8" />
      <Heart v-if="tab.icon === 'heart'" :stroke-width="1.8" />
      <User v-if="tab.icon === 'profile'" :stroke-width="1.8" />
      <span>{{ tab.label }}</span>
    </button>
  </nav>
</template>

<style scoped>
@import '../styles/HomePage.css';
</style>
