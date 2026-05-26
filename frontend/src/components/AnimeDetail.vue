<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Heart } from 'lucide-vue-next'
import { useSeries } from '../composables/useSeries'
import {translateSeason, translateFormat, translateSource, translateDay, translateGenres} from '../utils/translateLabels.ts'
import { progressPercent as computeProgressPercent, episodesBehind } from '../utils/animeStats'
import type { Anime } from '../types/Anime'

const props = defineProps<{ anime: Anime; isNew?: boolean }>()
const emit = defineEmits<{ close: [], goToHome: [], favoriteChanged: [id: number, isFavourite: boolean] }>()

const { fetchAnilistDetails, createUserSeries, toggleFavourite } = useSeries()

const details = ref<Anime>({ ...props.anime })
const detailsLoading = ref(false)

const progressPercent = computed(() => computeProgressPercent(details.value))
const behind = computed(() => episodesBehind(details.value))

const seasonInfo = computed(() => {
  const season = details.value.season
  const year = details.value.seasonYear

  if (season) return { label: 'TEMPORADA', value: `${translateSeason(season)} ${year}` }
  if (!season && year > 0) return { label: 'AÑO', value: year }
  return { label: 'TEMPORADA', value: '—' }
})

const infoCells = computed(() => [
  { label: seasonInfo.value.label, value: seasonInfo.value.value },
  { label: 'FORMATO', value: translateFormat(details.value.format) },
  { label: 'GÉNERO',  value: translateGenres(details.value.genre) },
  { label: 'ORIGEN',  value: translateSource(details.value.source) || '—' },
])

onMounted(() => {
  if (props.isNew) void loadAnilistDetails()
})

async function loadAnilistDetails() {
  detailsLoading.value = true
  const anime = await fetchAnilistDetails(props.anime.id)
  if (anime) {
    details.value = anime
  }
  detailsLoading.value = false
}

async function addToList() {
  if (await createUserSeries(props.anime.id)) emit('goToHome')
}

async function addSeriesToFavourites() {
  const isFavourite = await toggleFavourite(props.anime.id)
  if (isFavourite === null) return
  details.value.favorite = isFavourite
  emit('favoriteChanged', props.anime.id, isFavourite)
}
</script>

<template>
  <div class="detail">
    <div class="detail-header">
      <button class="btn-back" @click="emit('close')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
        <span>ATRÁS</span>
      </button>
      <span v-if="details.isTracked && details.airing && behind > 0" class="badge-behind">
        {{ behind }} EP. PENDIENTES
      </span>
    </div>

    <div class="hero">
      <img :src="details.cover" :alt="details.title" class="hero-img" />
      <div class="hero-fade" />
    </div>

    <div class="title-block">
      <h1 class="title">{{ details.title }}</h1>
      <p v-if="details.dayOfWeek" class="subtitle">{{ translateDay(details.dayOfWeek) }}</p>
    </div>

    <div class="stats-row">
      <div class="stat">
        <span class="stat-value">{{ progressPercent }}%</span>
        <span class="stat-label">PROGRESO</span>
      </div>
      <div class="stat">
        <span class="stat-value">{{ details.progress }}/{{ details.total }}</span>
        <span class="stat-label">EPISODIOS</span>
      </div>
      <div class="stat">
        <span class="stat-value" :class="details.airing ? 'stat-value--airing' : ''">
          {{ details.airing ? 'En emisión' : 'Finalizado' }}
        </span>
        <span class="stat-label">ESTADO</span>
      </div>
    </div>

    <div class="actions">
      <button v-if="isNew" class="action-btn action-btn--primary" :disabled="detailsLoading" @click="addToList()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        AÑADIR ANIME
      </button>
      <button v-if="!isNew" class="action-btn" :class="{ 'action-btn--active': details.favorite }" @click="addSeriesToFavourites()">
        <Heart :fill="details.favorite ? 'currentColor' : 'none'" />
        AÑADIR A FAVORITOS
      </button>
    </div>


    <div class="section">
      <h2 class="section-title">Información</h2>
      <div class="info-grid" :class="{ 'info-grid--loading': detailsLoading }">
        <div v-for="cell in infoCells" :key="cell.label" class="info-cell">
          <span class="info-label">{{ cell.label }}</span>
          <span class="info-value">{{ detailsLoading ? '···' : cell.value }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/AnimeDetail.css';
</style>