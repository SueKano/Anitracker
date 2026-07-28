<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Calendar } from 'lucide-vue-next'
import { translateDay, translateDayAbbr } from '../utils/translateLabels.ts'
import { episodesBehind } from '../utils/animeStats'
import type { Anime } from '../types/Anime'

const props = defineProps<{ animeList: Anime[] }>()
const emit = defineEmits<{ select: [id: number] }>()
const { t } = useI18n()

const DAYS = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']

function jsToIdx(weekday: number) { return weekday === 0 ? 6 : weekday - 1 }

const today = ref(new Date())
const todayIdx = computed(() => jsToIdx(today.value.getDay()))

const weekDates = computed(() => {
  const monday = new Date(today.value)
  monday.setDate(today.value.getDate() - todayIdx.value)

  return Array.from({ length: 7 }, (_, index) => {
    const date = new Date(monday)
    date.setDate(monday.getDate() + index)
    return date.getDate()
  })
})

const selected = ref(todayIdx.value)
const selectedDayKey = computed(() => DAYS[selected.value])

function refreshToday() {
  if (document.visibilityState !== 'visible') return
  const previousTodayIdx = todayIdx.value
  today.value = new Date()
  if (selected.value === previousTodayIdx) selected.value = todayIdx.value
}

onMounted(() => document.addEventListener('visibilitychange', refreshToday))
onUnmounted(() => document.removeEventListener('visibilitychange', refreshToday))

const animeByDay = computed(() => {
  const map = new Map<string, Anime[]>()
  for (const anime of props.animeList) {
    if (!anime.airing || !anime.dayOfWeek) continue
    const key = anime.dayOfWeek.toUpperCase()
    const list = map.get(key) ?? []
    list.push(anime)
    map.set(key, list)
  }
  return map
})

const daysView = computed(() => DAYS.map((day, i) => ({
  abbr: translateDayAbbr(day),
  key: day,
  date: weekDates.value[i],
  hasAnime: animeByDay.value.has(day),
})))

const dayAnime = computed(() =>
  (animeByDay.value.get(selectedDayKey.value) ?? [])
    .map(anime => ({ ...anime, behind: episodesBehind(anime) }))
    .sort((first, second) => second.behind - first.behind)
)
</script>

<template>
  <div class="cal">
    <h1 class="page-title">{{ t('calendar.title') }}</h1>
    <div class="strip">
      <button v-for="(day, i) in daysView" :key="day.key" class="day-btn" :class="{ active: selected === i, today: todayIdx === i }" @click="selected = i">
        <span class="day-name">{{ day.abbr }}</span>
        <span class="day-num">{{ day.date }}</span>
        <span v-if="day.hasAnime" class="day-dot"/>
      </button>
    </div>
    <div class="section-head">
      <span class="section-label">{{ translateDay(selectedDayKey) }}</span>
      <span class="section-count">{{ dayAnime.length }} {{ t('calendar.series', dayAnime.length) }}</span>
    </div>
    <div v-if="dayAnime.length > 0" class="grid">
      <div v-for="anime in dayAnime" :key="anime.id" class="tile" @click="emit('select', anime.id)">
        <div class="tile-cover">
          <img :src="anime.cover" :alt="anime.title" loading="lazy" />
          <div v-if="anime.behind > 0" class="behind-badge">
            {{ t('calendar.behind', { count: anime.behind }) }}
          </div>
          <div v-else-if="anime.aired > 0" class="uptodate-badge">{{ t('calendar.upToDate') }}</div>
        </div>
        <p class="tile-title" :title="anime.title">{{ anime.title }}</p>
      </div>
    </div>

    <div v-else class="empty">
      <Calendar :stroke-width="1.2" />
      <span>{{ t('calendar.empty') }}</span>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/CalendarPage.css';
</style>