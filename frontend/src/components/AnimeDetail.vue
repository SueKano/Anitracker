<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Heart, Pencil } from 'lucide-vue-next'
import { useSeries } from '../composables/useSeries'
import {translateSeason, translateFormat, translateSource, translateDay, translateGenres, resolveAiringStatus} from '../utils/translateLabels.ts'
import { progressPercent as computeProgressPercent, episodesBehind } from '../utils/animeStats'
import type { Anime } from '../types/Anime'

const props = defineProps<{ anime: Anime; isNew?: boolean; isAdmin?: boolean }>()
const emit = defineEmits<{ close: [], goToHome: [], favoriteChanged: [id: number, isFavourite: boolean], adultEpisodeChanged: [id: number, aired: number] }>()

const { fetchAnilistDetails, createUserSeries, toggleFavourite, updateAdultEpisode } = useSeries()
const { t } = useI18n()

const details = ref<Anime>({ ...props.anime })
const detailsLoading = ref(false)

const adminEditOpen = ref(false)
const adminEpisodeInput = ref(1)

function openAdminEdit() {
  adminEpisodeInput.value = details.value.aired
  adminEditOpen.value = true
}

async function saveAdminEdit() {
  const success = await updateAdultEpisode(details.value.id, adminEpisodeInput.value)
  if (success) {
    details.value.aired = adminEpisodeInput.value
    details.value.total = adminEpisodeInput.value
    emit('adultEpisodeChanged', details.value.id, adminEpisodeInput.value)
    adminEditOpen.value = false
  }
}

const progressPercent = computed(() => computeProgressPercent(details.value))
const behind = computed(() => episodesBehind(details.value))

const seasonInfo = computed(() => {
  const season = details.value.season
  const year = details.value.seasonYear

  if (season) return { label: t('detail.seasonLabel'), value: `${translateSeason(season)} ${year}` }
  if (!season && year > 0) return { label: t('detail.yearLabel'), value: year }
  return { label: t('detail.seasonLabel'), value: '—' }
})

const infoCells = computed(() => [
  { label: seasonInfo.value.label, value: seasonInfo.value.value },
  { label: t('detail.formatLabel'), value: translateFormat(details.value.format) },
  { label: t('detail.genreLabel'),  value: translateGenres(details.value.genre) },
  { label: t('detail.originLabel'), value: translateSource(details.value.source) || '—' },
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
  <div v-if="adminEditOpen" class="admin-modal-overlay" @click.self="adminEditOpen = false">
    <div class="admin-modal">
      <h3 class="admin-modal-title">{{ details.title }}</h3>
      <label class="admin-modal-label">{{ t('detail.airedEpisodeLabel') }}</label>
      <input v-model.number="adminEpisodeInput" type="number" min="1" max="12" class="admin-modal-input" @keyup.enter="saveAdminEdit" />
      <div class="admin-modal-actions">
        <button class="admin-modal-cancel" @click="adminEditOpen = false">{{ t('common.cancel') }}</button>
        <button class="admin-modal-save" @click="saveAdminEdit">{{ t('common.save') }}</button>
      </div>
    </div>
  </div>

  <div class="detail">
    <div class="detail-header">
      <button class="btn-back" @click="emit('close')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
        <span>{{ t('detail.back') }}</span>
      </button>
      <span v-if="details.isTracked && behind > 0" class="badge-behind">
        {{ t('detail.pendingEpisodes', { count: behind }) }}
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
        <span class="stat-label">{{ t('detail.progress') }}</span>
      </div>
      <div class="stat">
        <span class="stat-value">{{ details.progress }}/{{ details.total }}</span>
        <span class="stat-label">{{ t('detail.episodes') }}</span>
      </div>
      <div class="stat">
        <span class="stat-value" :class="details.airing ? 'stat-value--airing' : ''">
          {{ t(`detail.${resolveAiringStatus(details)}`) }}
        </span>
        <span class="stat-label">{{ t('detail.statusLabel') }}</span>
      </div>
    </div>

    <div class="actions">
      <button v-if="isNew" class="action-btn action-btn--primary" :disabled="detailsLoading" @click="addToList()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        {{ t('detail.addAnime') }}
      </button>
      <button v-if="!isNew" class="action-btn" :class="{ 'action-btn--active': details.favorite }" @click="addSeriesToFavourites()">
        <Heart :fill="details.favorite ? 'currentColor' : 'none'" />
        {{ t('detail.addFavorite') }}
      </button>
      <button v-if="isAdmin && details.isAdult && details.airing" class="action-btn" @click="openAdminEdit()">
        <Pencil :stroke-width="2" />
        {{ t('detail.editAiredEpisode') }}
      </button>
    </div>

    <div class="section">
      <h2 class="section-title">{{ t('detail.info') }}</h2>
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