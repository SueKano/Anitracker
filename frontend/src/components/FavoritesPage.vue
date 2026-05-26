<script setup lang="ts">
import { ref, computed } from 'vue'
import { ListFilter, ArrowDownUp, Heart } from 'lucide-vue-next'
import { progressPercent } from '../utils/animeStats'
import type { Anime } from '../types/Anime'
import {translateDay} from "../utils/translateLabels.ts";

const props = defineProps<{ animeList: Anime[] }>()
const emit = defineEmits<{ select: [id: number] }>()

type SortKey = 'default' | 'alpha' | 'progress'
type MenuKey = 'genre' | 'sort'

const genreFilter = ref<string | null>(null)
const sortKey = ref<SortKey>('default')
const openMenu = ref<MenuKey | null>(null)

const baseFavorites = computed(() =>
  props.animeList
    .filter(anime => anime.favorite)
    .map(anime => ({
      ...anime,
      genres: anime.genre ? anime.genre.split(', ') : [],
      percent: progressPercent(anime),
      done: anime.progress >= anime.total,
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

const sortOptions: { key: SortKey, label: string }[] = [
  { key: 'default', label: 'Por defecto' },
  { key: 'alpha', label: 'Alfabético' },
  { key: 'progress', label: 'Progreso' },
]

const currentSortLabel = computed(() => sortOptions.find(option => option.key === sortKey.value)?.label ?? '')
const currentGenreLabel = computed(() => genreFilter.value ?? 'Género')
</script>

<template>
  <div class="favs">
    <div class="page-head">
      <h1 class="page-title">Favoritos</h1>

      <div v-if="baseFavorites.length > 0" class="head-actions">
        <div class="menu-wrap">
          <button class="menu-btn" :class="{ active: openMenu === 'genre' || genreFilter !== null }" :disabled="genreOptions.length === 0"
            @click="toggleMenu('genre')" aria-label="Filtrar por género">
            <ListFilter :stroke-width="1.8" />
            <span>{{ currentGenreLabel }}</span>
          </button>
          <Transition name="pop">
            <div v-if="openMenu === 'genre'" class="menu-list">
              <button class="menu-item" :class="{ active: genreFilter === null }" @click="selectGenre(null)">Todos</button>
              <div class="menu-divider" />
              <button v-for="genre in genreOptions" :key="genre" class="menu-item" :class="{ active: genreFilter === genre }" @click="selectGenre(genre)">
                {{ genre }}
              </button>
            </div>
          </Transition>
        </div>

        <div class="menu-wrap">
          <button class="menu-btn" :class="{ active: openMenu === 'sort' }" @click="toggleMenu('sort')" aria-label="Ordenar">
            <ArrowDownUp :stroke-width="1.8" />
            <span>{{ currentSortLabel }}</span>
          </button>
          <Transition name="pop">
            <div v-if="openMenu === 'sort'" class="menu-list">
              <button v-for="option in sortOptions" :key="option.key" class="menu-item" :class="{ active: sortKey === option.key }"
                      @click="selectSort(option.key)">
                {{ option.label }}
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
          <span v-if="anime.airing" class="airing-badge"><i />{{ translateDay(anime.dayOfWeek) }}</span>
          <span v-else class="done-badge">Completado</span>
        </div>
        <p class="tile-title">{{ anime.title }}</p>
        <div class="tile-progress">
          <span class="tile-ep">{{ anime.progress }}<small> / {{ anime.total }}</small></span>
          <div class="bar"><div class="bar-fill" :class="{ done: anime.done }" :style="{ width: anime.percent + '%' }" /></div>
        </div>
      </div>
    </div>

    <div v-else-if="baseFavorites.length > 0" class="empty empty--filtered">
      <span>Sin coincidencias</span>
      <p>Pruebe con otro filtro</p>
    </div>

    <div v-else class="empty">
      <Heart :stroke-width="1.2" />
      <span>Aún no tienes favoritos</span>
      <p>Márcalos desde el detalle de cada anime</p>
    </div>

    <div v-if="openMenu !== null" class="menu-overlay" @click="openMenu = null" />
  </div>
</template>

<style scoped>
@import '../styles/FavoritesPage.css';
</style>