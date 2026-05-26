<script setup lang="ts">
import { computed, ref } from 'vue'
import { Sparkles, SquarePen, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import EditProfilePage from './EditProfilePage.vue'
import PrivacyPolicyPage from './PrivacyPolicyPage.vue'
import ContactPage from './ContactPage.vue'
import RecapWidget from './RecapWidget.vue'
import { useToast } from '../composables/useToast'
import { useRecap } from '../composables/useRecap'
import { useLastUpdates } from '../composables/useLastUpdates'
import { useAccount } from '../composables/useAccount'
import type { Anime } from '../types/Anime'

const props = defineProps<{ username: string, profileImage: string | null, animeList: Anime[] }>()
const emit = defineEmits<{ back: [], accountDeleted: [], profileUpdated: [username: string], selectAnime: [id: number] }>()

const EPISODE_MINUTES = 23
const MINUTES_PER_DAY = 60 * 24

const toast = useToast()
const { recap, fetchRecap } = useRecap()
const { lastUpdatesView } = useLastUpdates()
const { deleteAccount } = useAccount()

const showRecap = ref(false)
const confirmDialog = ref<HTMLDialogElement | null>(null)
const editSection = ref<'profile' | 'password' | null>(null)
const infoSection = ref<'privacy' | 'contact' | null>(null)

const isDecember = computed(() => new Date().getMonth() === 11)

const episodesWatched = computed(() =>
  props.animeList.reduce((sum, anime) => sum + anime.progress, 0))

const favoritesCount = computed(() =>
  props.animeList.filter(anime => anime.favorite).length)

const daysWatched = computed(() =>
  (episodesWatched.value * EPISODE_MINUTES / MINUTES_PER_DAY).toFixed(1)
)

const genreDistribution = computed(() => {
  const genreCounts: Record<string, number> = {}
  let totalGenres = 0
  for (const anime of props.animeList) {
    if (!anime.genre) continue
    for (const genre of anime.genre.split(', ')) {
      genreCounts[genre] = (genreCounts[genre] ?? 0) + 1
      totalGenres++
    }
  }
  if (!totalGenres) return []
  return Object.entries(genreCounts).sort(([, countA], [, countB]) => countB - countA).slice(0, 5)
    .map(([genre, count]) => ({ genre, percent: Math.round((count / totalGenres) * 100) }))
})

async function openRecap() {
  await fetchRecap(new Date().getFullYear())
  if (!recap.value) {
    toast.warning('Se necesitan 8 series para poder crear tu recap')
    return
  }
  showRecap.value = true
}

async function confirmDeleteAccount() {
  if (await deleteAccount()) emit('accountDeleted')
}
</script>

<template>
  <RecapWidget v-if="showRecap && recap" :recap="recap" @close="showRecap = false"/>
  <EditProfilePage v-if="editSection" :username="username" :profile-image="profileImage" :initial-section="editSection" @back="editSection = null"
                   @profile-updated="emit('profileUpdated', $event); editSection = null"/>
  <PrivacyPolicyPage v-else-if="infoSection === 'privacy'" @back="infoSection = null" @contact="infoSection = 'contact'"/>
  <ContactPage v-else-if="infoSection === 'contact'" @back="infoSection = null"/>

  <div v-else class="profile">
    <div class="profile-header">
      <button class="btn-icon" aria-label="Volver" @click="emit('back')">
        <ChevronLeft :stroke-width="2.2" />
      </button>
      <span class="profile-title">PERFIL</span>
      <button class="btn-icon" aria-label="Editar perfil" @click="editSection = 'profile'">
        <SquarePen :stroke-width="2" />
      </button>
    </div>

    <div class="profile-identity">
      <div class="profile-avatar">
        <img v-if="profileImage" :src="profileImage" alt="Avatar" class="profile-avatar-img" />
        <span v-else class="profile-avatar-letter">{{ username.charAt(0).toUpperCase() }}</span>
      </div>
      <h1 class="profile-username">{{ username }}</h1>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-label">EP. VISTOS</span>
        <span class="stat-value">{{ episodesWatched }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">SERIES</span>
        <span class="stat-value">{{ animeList.length }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">FAVORITOS</span>
        <span class="stat-value">{{ favoritesCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">DÍAS VISTOS</span>
        <span class="stat-value">{{ daysWatched }}</span>
      </div>
    </div>

    <button v-if="isDecember" class="recap-cta" @click="openRecap">
      <Sparkles :stroke-width="1.8" />
      <span>Ver tu recap del año</span>
    </button>

    <div v-if="genreDistribution.length" class="section-block">
      <p class="section-label">GÉNEROS MÁS CONSUMIDOS</p>
      <div class="genre-list">
        <div v-for="genre in genreDistribution" :key="genre.genre" class="genre-row">
          <div class="genre-head">
            <span class="genre-name">{{ genre.genre }}</span>
            <span class="genre-pct">{{ genre.percent }}%</span>
          </div>
          <div class="genre-track">
            <div class="genre-fill" :style="{ width: genre.percent + '%' }"/>
          </div>
        </div>
      </div>
    </div>

    <div class="section-block">
      <p class="section-label">ACTIVIDAD RECIENTE</p>
      <div v-if="lastUpdatesView.length" class="activity-list">
        <div v-for="update in lastUpdatesView" :key="update.id" class="activity-row">
          <img v-if="update.cover" :src="update.cover" :alt="update.title" class="activity-cover" loading="lazy" />
          <p class="activity-text">
            {{ update.prefix }}
            <button class="activity-link" @click="emit('selectAnime', update.id)">{{ update.title }}</button>
            <template v-if="update.suffix"> {{ update.suffix }}</template>
          </p>
        </div>
      </div>
      <div v-else class="activity-empty">
        <span>Sin actividad reciente</span>
      </div>
    </div>

    <div class="section-block">
      <p class="section-label">CUENTA</p>
      <div class="action-list">
        <button class="action-row" @click="editSection = 'password'">
          <span class="action-row-text">Cambiar contraseña</span>
          <ChevronRight />
        </button>
        <button class="action-row" @click="infoSection = 'privacy'">
          <span class="action-row-text">Política de privacidad</span>
          <ChevronRight />
        </button>
        <button class="action-row" @click="infoSection = 'contact'">
          <span class="action-row-text">Contacto y donaciones</span>
          <ChevronRight />
        </button>
      </div>
    </div>

    <button class="delete-btn" @click="confirmDialog?.showModal()">
      Borrar cuenta
    </button>
    <dialog ref="confirmDialog" class="confirm-dialog" @click.self="confirmDialog?.close()">
      <p class="confirm-text">¿Estás seguro de que quieres borrar tu cuenta?</p>
      <div class="confirm-actions">
        <button class="confirm-cancel" @click="confirmDialog?.close()">Cancelar</button>
        <button class="confirm-delete" @click="confirmDeleteAccount">Borrar</button>
      </div>
    </dialog>
  </div>
</template>

<style scoped>
@import '../styles/ProfilePage.css';
</style>