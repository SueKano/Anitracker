<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ListFilter, ArrowDownUp, Heart, Star } from 'lucide-vue-next'
import { progressPercent } from '../utils/animeStats'
import type { Anime } from '../types/Anime'
import {translateDay, translateGenre} from "../utils/translateLabels.ts";

const props = defineProps<{ animeList: Anime[] }>()
const emit = defineEmits<{ select: [id: number] }>()
const { t } = useI18n()

type SortKey = 'default' | 'alpha' | 'progress' | 'score'
type MenuKey = 'genre' | 'sort'

const genreFilter = ref<string | null>(null)
const sortKey = ref<SortKey>('default')
const openMenu = ref<MenuKey | null>(null)

const baseFavorites = computed(() =>
  props.animeList
    .filter(anime => anime.favorite).map(anime => ({
      ...anime,
      percent: progressPercent(anime),
      done: anime.total > 0 && anime.progress >= anime.total,
    }))
)

const genreOptions = computed(() => {
  const counts: Record<string, number> = {}
  for (const anime of baseFavorites.value) {
    for (const genre of anime.genres) {
      counts[genre] = (counts[genre] ?? 0) + 1
    }
  }
  return Object.entries(counts)
    .sort(([nameA, countA], [nameB, countB]) => countB - countA || nameA.localeCompare(nameB))
    .map(([name]) => name)
})

const favorites = computed(() => {
  let list = baseFavorites.value

  if (genreFilter.value) {
    const genre = genreFilter.value
    list = list.filter(anime => anime.genres.includes(genre))
  }

  const sorted = [...list]
  if (sortKey.value === 'alpha') sorted.sort((first, second) => first.title.localeCompare(second.title))
  else if (sortKey.value === 'progress') sorted.sort((first, second) => second.percent - first.percent)
  else if (sortKey.value === 'score') sorted.sort((first, second) => second.score - first.score || first.title.localeCompare(second.title))

  return sorted
})

function toggleMenu(menu: MenuKey) {
  openMenu.value = openMenu.value === menu ? null : menu
}

function selectGenre(genre: string | null) {
  genreFilter.value = genre
  openMenu.value = null
}

function selectSort(key: SortKey) {
  sortKey.value = key
  openMenu.value = null
}

const sortOptions: { key: SortKey }[] = [
  { key: 'default' },
  { key: 'alpha' },
  { key: 'progress' },
  { key: 'score' },
]

const currentSortLabel = computed(() => t('common.sort.' + sortKey.value))
const currentGenreLabel = computed(() => genreFilter.value ? translateGenre(genreFilter.value) : t('favorites.genreDefault'))
</script>

<template>
  <div class="favs">
    <div class="page-head">
      <h1 class="page-title">{{ t('favorites.title') }}</h1>

      <div v-if="baseFavorites.length > 0" class="head-actions">
        <div class="menu-wrap">
          <button class="menu-btn" :class="{ active: openMenu === 'genre' || genreFilter !== null }" :disabled="genreOptions.length === 0"
            @click="toggleMenu('genre')" :aria-label="t('favorites.filterGenreAria')">
            <ListFilter :stroke-width="1.8" />
            <span>{{ currentGenreLabel }}</span>
          </button>
          <Transition name="pop">
            <div v-if="openMenu === 'genre'" class="menu-list">
              <button class="menu-item" :class="{ active: genreFilter === null }" @click="selectGenre(null)">{{ t('favorites.all') }}</button>
              <div class="menu-divider" />
              <button v-for="genre in genreOptions" :key="genre" class="menu-item" :class="{ active: genreFilter === genre }" @click="selectGenre(genre)">
                {{ translateGenre(genre) }}
              </button>
            </div>
          </Transition>
        </div>

        <div class="menu-wrap">
          <button class="menu-btn" :class="{ active: openMenu === 'sort' }" @click="toggleMenu('sort')" :aria-label="t('common.sortAria')">
            <ArrowDownUp :stroke-width="1.8" />
            <span>{{ currentSortLabel }}</span>
          </button>
          <Transition name="pop">
            <div v-if="openMenu === 'sort'" class="menu-list">
              <button v-for="option in sortOptions" :key="option.key" class="menu-item" :class="{ active: sortKey === option.key }"
                      @click="selectSort(option.key)">
                {{ t('common.sort.' + option.key) }}
              </button>
            </div>
          </Transition>
        </div>
      </div>
    </div>

    <div v-if="favorites.length > 0" class="grid">
      <div v-for="anime in favorites" :key="anime.id" class="tile" @click="emit('select', anime.id)">
        <div class="tile-cover">
          <img :src="anime.cover" :alt="anime.title" loading="lazy" />
          <span v-if="anime.score > 0" class="score-badge">
            <Star fill="currentColor" :stroke-width="0" />
            {{ anime.score }}
          </span>
          <span v-if="anime.done" class="done-badge">{{ t('common.completed') }}</span>
          <span v-else-if="anime.airing && !anime.isAdult" class="airing-badge"><i />{{ translateDay(anime.dayOfWeek) }}</span>
        </div>
        <p class="tile-title">{{ anime.title }}</p>
        <div class="tile-progress">
          <span class="tile-ep">{{ anime.progress }}<small> / {{ anime.total }}</small></span>
          <div class="bar"><div class="bar-fill" :class="{ done: anime.done }" :style="{ width: anime.percent + '%' }" /></div>
        </div>
      </div>
    </div>

    <div v-else-if="baseFavorites.length > 0" class="empty empty--filtered">
      <span>{{ t('favorites.noMatches') }}</span>
      <p>{{ t('favorites.tryAnotherFilter') }}</p>
    </div>

    <div v-else class="empty">
      <Heart :stroke-width="1.2" />
      <span>{{ t('favorites.emptyTitle') }}</span>
      <p>{{ t('favorites.emptyHint') }}</p>
    </div>

    <div v-if="openMenu !== null" class="menu-overlay" @click="openMenu = null" />
  </div>
</template>

<style scoped>
@import '../styles/FavoritesPage.css';
</style>