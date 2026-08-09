<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Sparkles, SquarePen, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { setLocale, SUPPORTED_LOCALES, type Locale } from '../i18n'
import EditProfilePage from './EditProfilePage.vue'
import PrivacyPolicyPage from './PrivacyPolicyPage.vue'
import ContactPage from './ContactPage.vue'
import ImportListPage from './ImportListPage.vue'
import RecapWidget from './RecapWidget.vue'
import { useToast } from '../composables/useToast'
import { useRecap } from '../composables/useRecap'
import { useLastUpdates } from '../composables/useLastUpdates'
import { useAccount } from '../composables/useAccount'
import type { Anime } from '../types/Anime'
import { translateGenre } from '../utils/translateLabels'

const props = defineProps<{ username: string, profileImage: string | null, animeList: Anime[] }>()
const emit = defineEmits<{ back: [], accountDeleted: [], profileUpdated: [username: string], selectAnime: [id: number], imported: [] }>()

const EPISODE_MINUTES = 22
const MINUTES_PER_DAY = 60 * 24
const ACTIVITY_PREVIEW_COUNT = 5

const toast = useToast()
const { recap, fetchRecap } = useRecap()
const { lastUpdatesView, activityWindowDays } = useLastUpdates()
const { deleteAccount, updateAvatar } = useAccount()
const { t, locale } = useI18n()

const locales = SUPPORTED_LOCALES
function changeLocale(value: Locale) {
  setLocale(value)
}

const activityPage = ref(0)
const activityPageCount = computed(() => Math.max(1, Math.ceil(lastUpdatesView.value.length / ACTIVITY_PREVIEW_COUNT)))
const visibleUpdates = computed(() => {
  const start = activityPage.value * ACTIVITY_PREVIEW_COUNT
  return lastUpdatesView.value.slice(start, start + ACTIVITY_PREVIEW_COUNT)
})

function changeActivityPage(offset: number) {
  activityPage.value = Math.min(Math.max(activityPage.value + offset, 0), activityPageCount.value - 1)
}

const showRecap = ref(false)
const confirmDialog = ref<HTMLDialogElement | null>(null)
const editSection = ref<'profile' | 'password' | null>(null)
const infoSection = ref<'privacy' | 'contact' | null>(null)
const importOpen = ref(false)
const avatarInput = ref<HTMLInputElement | null>(null)

const isDecember = computed(() => new Date().getMonth() === 11)
const episodesWatched = computed(() => props.animeList.reduce((sum, anime) => sum + anime.episodesWatched, 0))
const favoritesCount = computed(() => props.animeList.filter(anime => anime.favorite).length)
const averageScore = computed(() => {
  const scored = props.animeList.filter(anime => anime.score > 0)
  if (!scored.length) return '—'
  return (scored.reduce((sum, anime) => sum + anime.score, 0) / scored.length).toFixed(1)
})
const completedCount = computed(() => props.animeList.filter(anime => anime.isCompleted).length)
const daysWatched = computed(() => (episodesWatched.value * EPISODE_MINUTES / MINUTES_PER_DAY).toFixed(1))

const genreDistribution = computed(() => {
  const genreCounts: Record<string, number> = {}
  let totalGenres = 0
  for (const anime of props.animeList) {
    for (const genre of anime.genres) {
      genreCounts[genre] = (genreCounts[genre] ?? 0) + 1
      totalGenres++
    }
  }
  if (!totalGenres) return []
  return Object.entries(genreCounts).sort(([, countA], [, countB]) => countB - countA).slice(0, 5)
    .map(([genre, count]) => ({ genre, percent: Math.round((count / totalGenres) * 100) }))
})

async function openRecap() {
  const result = await fetchRecap(new Date().getFullYear())
  if (result === 'insufficient') toast.warning(t('toast.recapNeed8'))
  if (result === 'ok') showRecap.value = true
}

async function confirmDeleteAccount() {
  if (await deleteAccount()) emit('accountDeleted')
}

async function onAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return
  if (await updateAvatar(props.username, file)) {
    emit('profileUpdated', props.username)
  }
}
</script>

<template>
  <RecapWidget v-if="showRecap && recap" :recap="recap" @close="showRecap = false"/>
  <EditProfilePage v-if="editSection" :username="username" :initial-section="editSection" @back="editSection = null"
                   @profile-updated="emit('profileUpdated', $event); editSection = null"/>
  <PrivacyPolicyPage v-else-if="infoSection === 'privacy'" @back="infoSection = null" @contact="infoSection = 'contact'"/>
  <ContactPage v-else-if="infoSection === 'contact'" @back="infoSection = null"/>
  <ImportListPage v-else-if="importOpen" @back="importOpen = false" @imported="emit('imported')"/>

  <div v-else class="profile">
    <div class="profile-header">
      <button class="btn-icon" :aria-label="t('profile.backAria')" @click="emit('back')">
        <ChevronLeft :stroke-width="2.2" />
      </button>
      <span class="profile-title">{{ t('profile.title') }}</span>
      <button class="btn-icon" :aria-label="t('profile.editAria')" @click="editSection = 'profile'">
        <SquarePen :stroke-width="2" />
      </button>
    </div>

    <div class="profile-identity">
      <button class="profile-avatar" type="button" :aria-label="t('profile.changePhotoAria')" @click="avatarInput?.click()">
        <img v-if="profileImage" :src="profileImage" alt="Avatar" class="profile-avatar-img" />
        <span v-else class="profile-avatar-letter">{{ username.charAt(0).toUpperCase() }}</span>
      </button>
      <input ref="avatarInput" type="file" accept="image/*" hidden @change="onAvatarChange" />
      <h1 class="profile-username">{{ username }}</h1>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-label">{{ t('profile.stats.episodesWatched') }}</span>
        <span class="stat-value">{{ episodesWatched }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('profile.stats.series') }}</span>
        <span class="stat-value">{{ animeList.length }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('profile.stats.favorites') }}</span>
        <span class="stat-value">{{ favoritesCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('profile.stats.daysWatched') }}</span>
        <span class="stat-value">{{ daysWatched }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('profile.stats.averageScore') }}</span>
        <span class="stat-value">{{ averageScore }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('profile.stats.completed') }}</span>
        <span class="stat-value">{{ completedCount }}</span>
      </div>
    </div>

    <button v-if="isDecember" class="recap-cta" @click="openRecap">
      <Sparkles :stroke-width="1.8" />
      <span>{{ t('common.recapCta') }}</span>
    </button>

    <div v-if="genreDistribution.length" class="section-block">
      <p class="section-label">{{ t('profile.topGenres') }}</p>
      <div class="genre-list">
        <div v-for="genre in genreDistribution" :key="genre.genre" class="genre-row">
          <div class="genre-head">
            <span class="genre-name">{{ translateGenre(genre.genre) }}</span>
            <span class="genre-pct">{{ genre.percent }}%</span>
          </div>
          <div class="genre-track">
            <div class="genre-fill" :style="{ width: genre.percent + '%' }"/>
          </div>
        </div>
      </div>
    </div>

    <div class="section-block">
      <p class="section-label">{{ t('profile.recentActivity') }}</p>
      <div v-if="lastUpdatesView.length" class="activity-list">
        <div class="activity-rows">
          <div v-for="update in visibleUpdates" :key="update.id" class="activity-row">
            <img v-if="update.cover" :src="update.cover" :alt="update.title" class="activity-cover" loading="lazy" />
            <p class="activity-text">
              {{ update.prefix }}
              <button class="activity-link" @click="emit('selectAnime', update.id)">{{ update.title }}</button>
              <template v-if="update.suffix"> {{ update.suffix }}</template>
            </p>
          </div>
        </div>
        <div v-if="activityPageCount > 1" class="activity-pager">
          <button class="pager-btn" :disabled="activityPage === 0" :aria-label="t('profile.prevActivityAria')" @click="changeActivityPage(-1)">
            <ChevronLeft :stroke-width="1.8" />
          </button>
          <span class="pager-count">{{ activityPage + 1 }} / {{ activityPageCount }}</span>
          <button class="pager-btn" :disabled="activityPage === activityPageCount - 1" :aria-label="t('profile.nextActivityAria')" @click="changeActivityPage(1)">
            <ChevronRight :stroke-width="1.8" />
          </button>
        </div>
      </div>
      <div v-else class="activity-empty">
        <span>{{ t('profile.noActivity', { days: activityWindowDays }) }}</span>
      </div>
    </div>

    <div class="section-block">
      <p class="section-label">{{ t('profile.language') }}</p>
      <div class="lang-switch">
        <button v-for="local in locales" :key="local" class="lang-btn" :class="{ active: locale === local }"
                @click="changeLocale(local)">
          {{ local.toUpperCase() }}
        </button>
      </div>
    </div>

    <div class="section-block">
      <p class="section-label">{{ t('profile.data') }}</p>
      <div class="action-list">
        <button class="action-row" @click="importOpen = true">
          <span class="action-row-text">{{ t('profile.importAnilist') }}</span>
          <ChevronRight />
        </button>
      </div>
    </div>

    <div class="section-block">
      <p class="section-label">{{ t('profile.account') }}</p>
      <div class="action-list">
        <button class="action-row" @click="editSection = 'password'">
          <span class="action-row-text">{{ t('profile.changePassword') }}</span>
          <ChevronRight />
        </button>
        <button class="action-row" @click="infoSection = 'privacy'">
          <span class="action-row-text">{{ t('profile.privacyPolicy') }}</span>
          <ChevronRight />
        </button>
        <button class="action-row" @click="infoSection = 'contact'">
          <span class="action-row-text">{{ t('profile.contactDonations') }}</span>
          <ChevronRight />
        </button>
      </div>
    </div>

    <button class="delete-btn" @click="confirmDialog?.showModal()">
      {{ t('profile.deleteAccount') }}
    </button>
    <dialog ref="confirmDialog" class="confirm-dialog" @click.self="confirmDialog?.close()">
      <p class="confirm-text">{{ t('profile.confirmDelete') }}</p>
      <div class="confirm-actions">
        <button class="confirm-cancel" @click="confirmDialog?.close()">{{ t('profile.cancel') }}</button>
        <button class="confirm-delete" @click="confirmDeleteAccount">{{ t('profile.delete') }}</button>
      </div>
    </dialog>
  </div>
</template>

<style scoped>
@import '../styles/ProfilePage.css';
</style>